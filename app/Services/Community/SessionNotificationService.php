<?php

namespace App\Services\Community;

use App\Enums\NotificationType;
use App\Enums\SessionNotice;
use App\Enums\UserType;
use App\Mail\SessionNoticeMail;
use App\Models\CommunitySession;
use App\Models\User;
use App\Support\MailLocale;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

/**
 * Session notifications from the client brief: every notice is an in-app
 * notification plus a mail, rendered in the recipient's own locale.
 *
 * Whoever triggered the action never hears about it, except the organiser
 * closing the session: that result record reaches everyone. Members can switch the
 * mails off (`users.session_emails`); in-app notifications always go out.
 * Admins only hear about creation, a full session and cancellation.
 * Call these after the write has committed, so the player list is current.
 */
class SessionNotificationService
{
    public function created(CommunitySession $session, User $host): void
    {
        $this->deliver(SessionNotice::AdminCreated, $this->admins(), $session, $host, $host);
    }

    /**
     * A member asked to join; only the organiser decides, so only they hear.
     */
    public function requested(CommunitySession $session, User $requester): void
    {
        $session->loadMissing('host');

        $this->deliver(SessionNotice::JoinRequested, $this->hostOnly($session), $session, $requester, $requester);
    }

    /**
     * Instant join on an open session: the organiser hears NewPlayer; a join
     * that fills the session also fires SessionFull (E3) for everyone.
     */
    public function joined(CommunitySession $session, User $player): void
    {
        $session->loadMissing('host');

        $this->deliver(SessionNotice::NewPlayer, $this->hostOnly($session), $session, $player, $player);

        if ($this->freshPlayerCount($session) >= $session->capacity) {
            $this->deliver(SessionNotice::SessionFull, $this->everyone($session), $session, $player, $player);
            $this->deliver(SessionNotice::AdminFull, $this->admins(), $session, $player, $player);
        }
    }

    /**
     * The organiser approved a request: the player is confirmed (E2), and
     * the approval that fills the session tells everyone (E3).
     */
    public function approved(CommunitySession $session, User $player, User $host): void
    {
        $this->deliver(SessionNotice::JoinConfirmed, new Collection([$player]), $session, $player, $host);

        if ($this->freshPlayerCount($session) >= $session->capacity) {
            $this->deliver(SessionNotice::SessionFull, $this->everyone($session), $session, $player, $player);
            $this->deliver(SessionNotice::AdminFull, $this->admins(), $session, $player, $player);
        }
    }

    public function rejected(CommunitySession $session, User $requester, User $host): void
    {
        $this->deliver(SessionNotice::RequestRejected, new Collection([$requester]), $session, $requester, $host);
    }

    public function withdrawn(CommunitySession $session, User $requester): void
    {
        $session->loadMissing('host');

        $this->deliver(SessionNotice::RequestWithdrawn, $this->hostOnly($session), $session, $requester, $requester);
    }

    public function scoreSubmitted(CommunitySession $session, User $submitter): void
    {
        $session->loadMissing('host');

        $this->deliver(SessionNotice::ScoreSubmitted, $this->hostOnly($session), $session, $submitter, $submitter);
    }

    public function scoreApproved(CommunitySession $session, User $host): void
    {
        $this->deliver(SessionNotice::ScoreApproved, $this->everyone($session), $session, $host, $host);
    }

    public function scoreRejected(CommunitySession $session, User $submitter, User $host): void
    {
        $this->deliver(SessionNotice::ScoreRejected, new Collection([$submitter]), $session, $host, $host);
    }

    /**
     * The result record goes to both teams and the organiser — the one
     * notice where the organiser hears about their own action.
     */
    public function finished(CommunitySession $session, User $host): void
    {
        $this->deliver(SessionNotice::SessionFinished, $this->everyone($session), $session, $host);
    }

    public function left(CommunitySession $session, User $leaver, bool $wasFull): void
    {
        $this->spotOpened($session, $leaver, $leaver, $wasFull);
    }

    public function removed(CommunitySession $session, User $removed, User $actor, bool $wasFull): void
    {
        $this->deliver(SessionNotice::Removed, new Collection([$removed]), $session, $actor, $actor);

        $this->spotOpened($session, $removed, $actor, $wasFull, except: $removed);
    }

    public function cancelled(CommunitySession $session, User $actor): void
    {
        $this->deliver(SessionNotice::Cancelled, $this->everyone($session), $session, $actor, $actor);
        $this->deliver(SessionNotice::AdminCancelled, $this->admins(), $session, $actor, $actor);
    }

    /**
     * 48 hours ahead with free places left: the organiser decides whether to
     * share the session or cancel it. Nothing is cancelled automatically.
     */
    public function openSpots(CommunitySession $session): void
    {
        $session->loadMissing('host');

        if ($session->host === null) {
            return;
        }

        $this->deliver(SessionNotice::OpenSpotsReminder, $this->hostOnly($session), $session, $session->host);
    }

    /**
     * The session ended: every player (organiser included) gets the match card.
     */
    public function matchCardReady(CommunitySession $session): void
    {
        $this->deliver(SessionNotice::MatchCardReady, $this->everyone($session), $session, new User(['name' => '']));
    }

    public function remind(CommunitySession $session): void
    {
        $session->loadMissing('host');

        $this->deliver(
            SessionNotice::Reminder,
            $this->everyone($session),
            $session,
            $session->host ?? new User(['name' => '']),
        );
    }

    /**
     * E4: the organiser always hears; the other players only when the
     * session had been full, because only then could they have been waiting.
     */
    private function spotOpened(
        CommunitySession $session,
        User $leaver,
        User $actor,
        bool $wasFull,
        ?User $except = null,
    ): void {
        $session->loadMissing('host');

        $recipients = $wasFull
            ? $this->everyone($session)
            : new Collection(array_filter([$session->host]));

        if ($except !== null) {
            $recipients = $recipients->reject(fn (User $user): bool => $user->id === $except->id);
        }

        $this->deliver(SessionNotice::SpotOpened, $recipients, $session, $leaver, $actor);
    }

    /**
     * @param  Collection<int, User>  $recipients
     */
    private function deliver(
        SessionNotice $notice,
        Collection $recipients,
        CommunitySession $session,
        User $subject,
        ?User $actor = null,
    ): void {
        $recipients = $recipients
            ->filter()
            ->reject(fn (User $user): bool => $actor !== null && $user->id === $actor->id)
            ->unique('id')
            ->values();

        if ($recipients->isEmpty()) {
            return;
        }

        $session->loadMissing(['club', 'host']);
        $players = $session->players()->orderBy('community_session_participants.created_at')->get();
        $freePlaces = max(0, $session->capacity - $players->count());
        $resultLine = $notice->carriesResult()
            ? (string) $session->officialScore()->first()?->setsLine()
            : '';
        $previousLocale = app()->getLocale();

        try {
            foreach ($recipients->groupBy(fn (User $user): string => MailLocale::resolve($user->locale)) as $locale => $group) {
                app()->setLocale($locale);

                $sessionUrl = self::sessionUrl($session, $notice->isForAdmins(), $locale);
                $timingNote = $notice->startsSoonLine($session->starts_at);
                $inAppMessage = $notice->inAppMessage(
                    $subject->name,
                    $session->starts_at->translatedFormat('l j F Y, H:i'),
                    $freePlaces,
                    $resultLine,
                    (string) $session->club?->name,
                );
                $isMatchCard = $notice === SessionNotice::MatchCardReady;

                \notify_users(
                    $group,
                    NotificationType::Community,
                    $notice->subject(),
                    $timingNote !== null ? $inAppMessage.' '.$timingNote : $inAppMessage,
                    [
                        'session_id' => $session->id,
                        'notice' => $notice->value,
                        'link_url' => $sessionUrl,
                        'link_label' => $isMatchCard ? __('Deel uw kaart') : __('Bekijk sessie'),
                    ],
                    $actor,
                );

                foreach ($group as $user) {
                    if (! $notice->isForAdmins() && ! $user->session_emails) {
                        continue;
                    }

                    Mail::to($user)->send(new SessionNoticeMail(
                        session: $session,
                        notice: $notice,
                        subjectName: $subject->name,
                        players: $players
                            ->reject(fn (User $player): bool => $notice === SessionNotice::JoinConfirmed && $player->id === $user->id)
                            ->pluck('name')
                            ->values()
                            ->all(),
                        freePlaces: $freePlaces,
                        sessionUrl: $sessionUrl,
                        ctaUrl: self::ctaUrl($session, $notice, $locale),
                        locale: $locale,
                        timingNote: $timingNote,
                        secondaryCtaUrl: self::secondaryCtaUrl($session, $notice, $locale),
                    ));
                }
            }
        } finally {
            app()->setLocale($previousLocale);
        }
    }

    /**
     * Admins land on the moderation view, members on the public session page.
     */
    public static function sessionUrl(CommunitySession $session, bool $forAdmin, string $locale): string
    {
        return $forAdmin
            ? route('admin.community-sessions.show', ['locale' => $locale, 'communitySession' => $session->id])
            : route('community.sessions.show', ['locale' => $locale, 'communitySession' => $session->id]);
    }

    private static function ctaUrl(CommunitySession $session, SessionNotice $notice, string $locale): string
    {
        return match ($notice) {
            SessionNotice::Cancelled, SessionNotice::Removed, SessionNotice::RequestRejected => route('community.sessions.index', ['locale' => $locale]),
            default => self::sessionUrl($session, $notice->isForAdmins(), $locale),
        };
    }

    /**
     * The organiser's cancel button opens the cancel dialog on the session
     * page; the match card mail downloads the Story PNG through a signed
     * link, so players can save it without signing in first.
     */
    private static function secondaryCtaUrl(CommunitySession $session, SessionNotice $notice, string $locale): ?string
    {
        return match ($notice) {
            SessionNotice::OpenSpotsReminder => route('community.sessions.show', [
                'locale' => $locale,
                'communitySession' => $session->id,
                'cancel' => 1,
            ]),
            SessionNotice::MatchCardReady => URL::temporarySignedRoute('maison.sessions.card', now()->addDays(30), [
                'locale' => $locale,
                'shareToken' => $session->share_token,
                'format' => 'story',
                'download' => 1,
            ]),
            default => null,
        };
    }

    /**
     * Every player plus the organiser.
     *
     * @return Collection<int, User>
     */
    private function everyone(CommunitySession $session): Collection
    {
        $session->loadMissing('host');

        $members = $session->players()->get();

        if ($session->host !== null) {
            $members->push($session->host);
        }

        return $members;
    }

    /**
     * @return Collection<int, User>
     */
    private function hostOnly(CommunitySession $session): Collection
    {
        return new Collection(array_filter([$session->host]));
    }

    /**
     * @return Collection<int, User>
     */
    private function admins(): Collection
    {
        return User::query()->where('type', UserType::Admin)->get();
    }

    private function freshPlayerCount(CommunitySession $session): int
    {
        return $session->participants()->count();
    }
}
