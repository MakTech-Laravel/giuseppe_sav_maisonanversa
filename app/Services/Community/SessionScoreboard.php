<?php

namespace App\Services\Community;

use App\Enums\SessionMatchFormat;
use App\Enums\SessionMatchType;
use App\Enums\SessionScoreStatus;
use App\Models\CommunitySession;
use App\Models\CommunitySessionScore;
use App\Models\User;
use App\Support\SessionFeed;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The scoreboard of a held session. Every write locks the session row and
 * rechecks the state inside the transaction, so two requests racing past
 * the policy cannot both win.
 *
 * Rules: a player's submission waits for the host; the host's own
 * submission is official at once. At most one Approved row per session;
 * approving another supersedes it. Lock stops player submissions, unlock
 * reopens them, finish closes the session for good.
 */
class SessionScoreboard
{
    public function __construct(private readonly SessionNotificationService $notifier) {}

    /**
     * @param  array{match_type: SessionMatchType, format: SessionMatchFormat, team_a: list<int>, team_b: list<int>, sets: list<array{a: int, b: int, tiebreak_a: int|null, tiebreak_b: int|null}>}  $data
     */
    public function submit(CommunitySession $session, User $submitter, array $data): CommunitySessionScore
    {
        $isHost = $session->isHostedBy($submitter);

        $score = DB::transaction(function () use ($session, $submitter, $data, $isHost): CommunitySessionScore {
            $locked = $this->lockSession($session);

            abort_if(! $locked->isHeld() || $locked->isScoreboardLocked(), 422, __('Het scorebord is gesloten.'));
            abort_if(! $isHost && ! $locked->isScoreWindowOpen(), 422, __('De termijn om een score in te dienen is verstreken.'));

            $locked->scores()
                ->where('submitted_by', $submitter->id)
                ->where('status', SessionScoreStatus::Pending->value)
                ->update(['status' => SessionScoreStatus::Superseded->value]);

            if ($isHost) {
                $this->supersedeOfficial($locked);
            }

            return $locked->scores()->create([
                'submitted_by' => $submitter->id,
                'match_type' => $data['match_type'],
                'format' => $data['format'],
                'team_a' => $data['team_a'],
                'team_b' => $data['team_b'],
                'sets' => $data['sets'],
                'status' => $isHost ? SessionScoreStatus::Approved : SessionScoreStatus::Pending,
                'reviewed_by' => $isHost ? $submitter->id : null,
                'reviewed_at' => $isHost ? now() : null,
            ]);
        });

        if ($isHost) {
            $this->notifier->scoreApproved($session, $submitter);
        } else {
            $this->notifier->scoreSubmitted($session, $submitter);
        }

        return $score;
    }

    public function approve(CommunitySession $session, CommunitySessionScore $score, User $host): void
    {
        DB::transaction(function () use ($session, $score, $host): void {
            $locked = $this->lockSession($session);
            $fresh = $this->pendingScore($locked, $score);

            $this->supersedeOfficial($locked);

            $fresh->update([
                'status' => SessionScoreStatus::Approved,
                'reviewed_by' => $host->id,
                'reviewed_at' => now(),
            ]);
        });

        $this->notifier->scoreApproved($session, $host);
    }

    public function reject(CommunitySession $session, CommunitySessionScore $score, User $host): void
    {
        $submitter = DB::transaction(function () use ($session, $score, $host): ?User {
            $locked = $this->lockSession($session);
            $fresh = $this->pendingScore($locked, $score);

            $fresh->update([
                'status' => SessionScoreStatus::Rejected,
                'reviewed_by' => $host->id,
                'reviewed_at' => now(),
            ]);

            return $fresh->submitter;
        });

        if ($submitter !== null) {
            $this->notifier->scoreRejected($session, $submitter, $host);
        }
    }

    public function lock(CommunitySession $session): void
    {
        DB::transaction(function () use ($session): void {
            $locked = $this->lockSession($session);

            abort_if(! $locked->isHeld(), 422, __('Deze sessie is al afgesloten.'));

            if (! $locked->isScoreboardLocked()) {
                $locked->update(['scoreboard_locked_at' => now()]);
            }
        });
    }

    public function unlock(CommunitySession $session): void
    {
        DB::transaction(function () use ($session): void {
            $locked = $this->lockSession($session);

            abort_if(! $locked->isHeld(), 422, __('Deze sessie is al afgesloten.'));

            $locked->update(['scoreboard_locked_at' => null]);
        });
    }

    /**
     * Closing needs a locked scoreboard, an official score and nothing left
     * waiting for review. A session missing any of those simply stays held.
     * A session with too few confirmed players for any match closes without
     * a result.
     */
    public function finish(CommunitySession $session, User $host): void
    {
        DB::transaction(function () use ($session): void {
            $locked = $this->lockSession($session);

            abort_if(! $locked->isHeld(), 422, __('Deze sessie is al afgesloten.'));
            abort_if(! $locked->isScoreboardLocked(), 422, __('Vergrendel eerst het scorebord.'));
            abort_if(
                self::canRecordMatch($locked) && ! $locked->officialScore()->exists(),
                422,
                __('Keur eerst een score goed.'),
            );
            abort_if(
                $locked->scores()->where('status', SessionScoreStatus::Pending->value)->exists(),
                422,
                __('Behandel eerst alle ingediende scores.'),
            );

            $locked->update(['finished_at' => now()]);
        });

        $this->notifier->finished($session->fresh(), $host);
    }

    public static function canFinish(CommunitySession $session): bool
    {
        return $session->isHeld()
            && $session->isScoreboardLocked()
            && (! self::canRecordMatch($session) || $session->officialScore()->exists())
            && ! $session->scores()->where('status', SessionScoreStatus::Pending->value)->exists();
    }

    /**
     * Enough confirmed players for at least one match type of the sport.
     */
    public static function canRecordMatch(CommunitySession $session): bool
    {
        return $session->participantsCount() >= $session->sport->minimumPlayers();
    }

    /**
     * Scoreboard payload for the session page; null before the game ends.
     *
     * @return array<string, mixed>|null
     */
    public function present(CommunitySession $session, ?User $viewer): ?array
    {
        if ($session->isCancelled() || ! $session->isPast()) {
            return null;
        }

        $session->loadMissing(['participants.user', 'scores.submitter']);

        $isHost = $viewer !== null && $session->isHostedBy($viewer);
        $players = $session->participants->pluck('user')->filter()->keyBy('id');
        $official = $session->scores->first(
            fn (CommunitySessionScore $score): bool => $score->status === SessionScoreStatus::Approved,
        );

        $submissions = $session->scores
            ->filter(fn (CommunitySessionScore $score): bool => in_array(
                $score->status,
                [SessionScoreStatus::Pending, SessionScoreStatus::Rejected],
                true,
            ))
            ->filter(fn (CommunitySessionScore $score): bool => $isHost
                || ($viewer !== null && $score->submitted_by === $viewer->id))
            ->sortByDesc('id')
            ->values();

        $lifecycle = $session->lifecycle();

        return [
            'lifecycle' => $lifecycle->value,
            'lifecycle_label' => $lifecycle->label(),
            'is_locked' => $session->isScoreboardLocked(),
            'is_finished' => $session->isFinished(),
            'window_closes_at' => $session->scoreWindowClosesAt()?->toIso8601String(),
            'window_open' => $session->isScoreWindowOpen(),
            'official' => $official ? $this->scoreArray($official, $players) : null,
            'submissions' => $submissions
                ->map(fn (CommunitySessionScore $score): array => $this->scoreArray($score, $players))
                ->all(),
            'players' => $players
                ->map(fn (User $user): array => SessionFeed::player($user, $session->host_id))
                ->values()
                ->all(),
            'sport' => $session->sport->value,
            'sport_label' => $session->sport->label(),
            'match_types' => array_values(array_filter(
                SessionMatchType::options(),
                fn (array $option): bool => in_array(
                    SessionMatchType::from($option['value']),
                    $session->sport->matchTypes(),
                    true,
                ),
            )),
            'formats' => SessionMatchFormat::options(),
            'default_format' => SessionMatchFormat::BestOfThree->value,
            'min_players' => $session->sport->minimumPlayers(),
            'can_record_match' => self::canRecordMatch($session),
            'can_submit' => $viewer !== null && $viewer->can('submitScore', $session),
            'can_review' => $viewer !== null && $viewer->can('reviewScore', $session),
            'can_lock' => $isHost && $session->isHeld() && ! $session->isScoreboardLocked(),
            'can_unlock' => $isHost && $session->isHeld() && $session->isScoreboardLocked(),
            'can_finish' => $isHost && self::canFinish($session),
        ];
    }

    /**
     * @param  Collection<int, User>  $players
     * @return array<string, mixed>
     */
    private function scoreArray(CommunitySessionScore $score, Collection $players): array
    {
        $team = fn (array $ids): array => collect($ids)
            ->map(fn (int|string $id): ?User => $players->get((int) $id))
            ->filter()
            ->map(fn (User $user): array => SessionFeed::player($user))
            ->values()
            ->all();

        return [
            'id' => $score->id,
            'status' => $score->status->value,
            'status_label' => $score->status->label(),
            'match_type' => $score->match_type->value,
            'match_type_label' => $score->match_type->label(),
            'format' => $score->format->value,
            'format_label' => $score->format->label(),
            'team_a' => $team($score->team_a),
            'team_b' => $team($score->team_b),
            'sets' => array_map(
                fn (array $set, int $index): array => [
                    'a' => (int) $set['a'],
                    'b' => (int) $set['b'],
                    'tiebreak_a' => $set['tiebreak_a'] ?? null,
                    'tiebreak_b' => $set['tiebreak_b'] ?? null,
                    'is_super_tiebreak' => $score->format->isSuperTiebreak($index),
                ],
                array_values($score->sets),
                array_keys(array_values($score->sets)),
            ),
            'sets_line' => $score->setsLine(),
            'winner' => $score->winner(),
            'submitted_by' => $score->submitter?->name,
            'submitted_at' => $score->created_at?->toIso8601String(),
            'reviewed_at' => $score->reviewed_at?->toIso8601String(),
        ];
    }

    private function lockSession(CommunitySession $session): CommunitySession
    {
        return CommunitySession::query()
            ->whereKey($session->id)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function pendingScore(CommunitySession $session, CommunitySessionScore $score): CommunitySessionScore
    {
        $fresh = $session->scores()->whereKey($score->id)->lockForUpdate()->firstOrFail();

        abort_if(! $session->isHeld(), 422, __('Deze sessie is al afgesloten.'));
        abort_if($fresh->status !== SessionScoreStatus::Pending, 422, __('Deze score is al behandeld.'));

        return $fresh;
    }

    private function supersedeOfficial(CommunitySession $session): void
    {
        $session->scores()
            ->where('status', SessionScoreStatus::Approved->value)
            ->update(['status' => SessionScoreStatus::Superseded->value]);
    }
}
