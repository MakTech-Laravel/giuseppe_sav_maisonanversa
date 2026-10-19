<?php

namespace App\Services\Community;

use App\Enums\SessionParticipantStatus;
use App\Events\SessionChatActivity;
use App\Events\SessionMessageCreated;
use App\Models\CommunitySession;
use App\Models\CommunitySessionChatRead;
use App\Models\CommunitySessionMessage;
use App\Models\CommunitySessionMessageAttachment;
use App\Models\CommunitySessionParticipant;
use App\Models\User;
use App\Support\SessionFeed;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Persisted session chat: open while the session is still ahead, read-only
 * after close, and hard-deleted seven days past closed_at.
 */
class SessionChatService
{
    public const RETENTION_DAYS = 7;

    public const MAX_IMAGES = 4;

    public function isOpen(CommunitySession $session): bool
    {
        return ! $session->isCancelled() && ! $session->isPast();
    }

    public function closedAt(CommunitySession $session): ?CarbonInterface
    {
        if ($this->isOpen($session)) {
            return null;
        }

        return $session->cancelled_at ?? $session->ends_at;
    }

    public function messagesPurgeAt(CommunitySession $session): ?CarbonInterface
    {
        $closedAt = $this->closedAt($session);

        return $closedAt?->copy()->addDays(self::RETENTION_DAYS);
    }

    /**
     * True while the session is open or still inside the 7-day post-close window.
     * Past that, messages are pruned and the chat disappears from lists.
     */
    public function isWithinRetention(CommunitySession $session): bool
    {
        if ($this->isOpen($session)) {
            return true;
        }

        $closedAt = $this->closedAt($session);

        return $closedAt !== null
            && $closedAt->gt(now()->subDays(self::RETENTION_DAYS));
    }

    /**
     * Inverse of pruneExpired(): keep open sessions and those closed less than
     * RETENTION_DAYS ago.
     *
     * @param  Builder<CommunitySession>  $query
     * @return Builder<CommunitySession>
     */
    public function scopeWithinRetention(Builder $query): Builder
    {
        $cutoff = now()->subDays(self::RETENTION_DAYS);

        return $query->where(function (Builder $retention) use ($cutoff): void {
            $retention->where(function (Builder $notCancelled) use ($cutoff): void {
                $notCancelled->whereNull('cancelled_at')
                    ->where('ends_at', '>', $cutoff);
            })->orWhere(function (Builder $cancelled) use ($cutoff): void {
                $cancelled->whereNotNull('cancelled_at')
                    ->where('cancelled_at', '>', $cutoff);
            });
        });
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listFor(CommunitySession $session): array
    {
        return $session->messages()
            ->with(['user', 'attachments'])
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->map(fn (CommunitySessionMessage $message): array => $this->present($message))
            ->values()
            ->all();
    }

    /**
     * Ensure staff have a Confirmed roster row for chat. Does not consume a
     * play seat (`for_chat_only`) and skips capacity / registration checks.
     */
    public function ensureStaffChatParticipant(CommunitySession $session, User $staff): void
    {
        $existing = $session->participantRecords()
            ->where('user_id', $staff->id)
            ->first();

        if ($existing !== null && $existing->isConfirmed()) {
            return;
        }

        if ($existing !== null) {
            $existing->forceFill([
                'status' => SessionParticipantStatus::Confirmed,
                'for_chat_only' => true,
                'decided_at' => now(),
            ])->save();

            return;
        }

        CommunitySessionParticipant::query()->create([
            'community_session_id' => $session->id,
            'user_id' => $staff->id,
            'status' => SessionParticipantStatus::Confirmed,
            'for_chat_only' => true,
            'decided_at' => now(),
        ]);
    }

    /**
     * @param  list<UploadedFile>  $files
     */
    public function sendAsStaff(CommunitySession $session, User $staff, ?string $body, array $files = []): CommunitySessionMessage
    {
        if (! $this->isOpen($session)) {
            throw ValidationException::withMessages([
                'body' => __('Deze chat is gesloten.'),
            ]);
        }

        $this->ensureStaffChatParticipant($session, $staff);

        return $this->send($session, $staff, $body, $files);
    }

    /**
     * @param  list<UploadedFile>  $files
     */
    public function send(CommunitySession $session, User $author, ?string $body, array $files = []): CommunitySessionMessage
    {
        $trimmed = $body !== null ? trim($body) : '';
        $trimmed = $trimmed === '' ? null : $trimmed;

        if ($trimmed === null && $files === []) {
            throw ValidationException::withMessages([
                'body' => __('Schrijf een bericht of voeg een afbeelding toe.'),
            ]);
        }

        if (! $this->isOpen($session)) {
            throw ValidationException::withMessages([
                'body' => __('Deze chat is gesloten.'),
            ]);
        }

        $message = DB::transaction(function () use ($session, $author, $trimmed, $files): CommunitySessionMessage {
            $message = $session->messages()->create([
                'user_id' => $author->id,
                'body' => $trimmed,
                'is_system' => $this->authorSendsAsSystem($session, $author),
            ]);

            foreach ($files as $file) {
                $path = $file->store("session-chat/{$session->id}", 'public');

                $message->attachments()->create([
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime' => (string) $file->getMimeType(),
                    'size' => $file->getSize() ?: 0,
                ]);
            }

            return $message->load(['user', 'attachments']);
        });

        $this->markRead($author, $session);

        // Persist succeeds even when Reverb is down; live Echo still works when it is up.
        $this->safeBroadcast(fn () => SessionMessageCreated::dispatch($message));
        $this->safeBroadcast(fn () => $this->notifyRecipientsOfActivity($session, $author));

        return $message;
    }

    public function markRead(User $user, CommunitySession $session): void
    {
        CommunitySessionChatRead::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'community_session_id' => $session->id,
            ],
            ['last_read_at' => now()],
        );
    }

    public function unreadCountFor(User $user, CommunitySession $session): int
    {
        $lastReadAt = CommunitySessionChatRead::query()
            ->where('user_id', $user->id)
            ->where('community_session_id', $session->id)
            ->value('last_read_at');

        return CommunitySessionMessage::query()
            ->where('community_session_id', $session->id)
            ->where('user_id', '!=', $user->id)
            ->when(
                $lastReadAt !== null,
                fn (Builder $query) => $query->where('created_at', '>', $lastReadAt),
            )
            ->count();
    }

    /**
     * @param  Collection<int, int|string>|list<int|string>  $sessionIds
     * @return array<string, int>
     */
    public function unreadCountsBySession(User $user, Collection|array $sessionIds): array
    {
        $ids = collect($sessionIds)
            ->map(fn (int|string $id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return [];
        }

        $lastReads = CommunitySessionChatRead::query()
            ->where('user_id', $user->id)
            ->whereIn('community_session_id', $ids)
            ->pluck('last_read_at', 'community_session_id');

        $counts = [];

        foreach ($ids as $sessionId) {
            $lastReadAt = $lastReads->get($sessionId);

            $counts[(string) $sessionId] = CommunitySessionMessage::query()
                ->where('community_session_id', $sessionId)
                ->where('user_id', '!=', $user->id)
                ->when(
                    $lastReadAt !== null,
                    fn (Builder $query) => $query->where('created_at', '>', $lastReadAt),
                )
                ->count();
        }

        return $counts;
    }

    public function unreadTotal(User $user): int
    {
        $sessionIds = $this->chatSessionIdsFor($user);

        if ($sessionIds->isEmpty()) {
            return 0;
        }

        return (int) array_sum($this->unreadCountsBySession($user, $sessionIds));
    }

    /**
     * Newest unread message timestamp across the member's session chats, or null
     * when everything is read. Used by the bulk digest watermark.
     */
    public function newestUnreadAt(User $user): ?CarbonInterface
    {
        $sessionIds = $this->chatSessionIdsFor($user);

        if ($sessionIds->isEmpty()) {
            return null;
        }

        $lastReads = CommunitySessionChatRead::query()
            ->where('user_id', $user->id)
            ->whereIn('community_session_id', $sessionIds)
            ->pluck('last_read_at', 'community_session_id');

        $newest = null;

        foreach ($sessionIds as $sessionId) {
            $lastReadAt = $lastReads->get($sessionId);

            $createdAt = CommunitySessionMessage::query()
                ->where('community_session_id', $sessionId)
                ->where('user_id', '!=', $user->id)
                ->when(
                    $lastReadAt !== null,
                    fn (Builder $query) => $query->where('created_at', '>', $lastReadAt),
                )
                ->max('created_at');

            if ($createdAt === null) {
                continue;
            }

            $at = Carbon::parse($createdAt);

            if ($newest === null || $at->gt($newest)) {
                $newest = $at;
            }
        }

        return $newest;
    }

    /**
     * Session with the highest unread count (for digest deep-links), or null.
     */
    public function sessionIdWithMostUnread(User $user): ?int
    {
        $sessionIds = $this->chatSessionIdsFor($user);

        if ($sessionIds->isEmpty()) {
            return null;
        }

        $counts = $this->unreadCountsBySession($user, $sessionIds);
        $bestId = null;
        $bestCount = 0;

        foreach ($counts as $sessionId => $count) {
            if ($count > $bestCount) {
                $bestCount = $count;
                $bestId = (int) $sessionId;
            }
        }

        return $bestCount > 0 ? $bestId : null;
    }

    /**
     * @return Collection<int, int>
     */
    public function chatSessionIdsFor(User $user): Collection
    {
        $query = CommunitySession::query()
            ->where(function (Builder $query) use ($user): void {
                $query->where('host_id', $user->id)
                    ->orWhereHas(
                        'participants',
                        fn (Builder $participant) => $participant->where('user_id', $user->id),
                    );
            });

        return $this->scopeWithinRetention($query)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->values();
    }

    private function notifyRecipientsOfActivity(CommunitySession $session, User $author): void
    {
        $recipientIds = $session->participants()
            ->pluck('user_id')
            ->push($session->host_id)
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->reject(fn (int $id): bool => $id === (int) $author->id)
            ->values();

        foreach ($recipientIds as $recipientId) {
            $recipient = User::query()->find($recipientId);

            if ($recipient === null) {
                continue;
            }

            $this->safeBroadcast(fn () => SessionChatActivity::dispatch(
                $recipient->id,
                (int) $session->id,
                $this->unreadTotal($recipient),
            ));
        }
    }

    /**
     * Reverb / Pusher being unreachable must not roll the chat HTTP request into a 500.
     */
    private function safeBroadcast(callable $callback): void
    {
        try {
            $callback();
        } catch (BroadcastException $e) {
            report($e);
        }
    }

    /**
     * Staff chat-only seats post as system messages; play seats and members do not.
     */
    public function authorSendsAsSystem(CommunitySession $session, User $author): bool
    {
        return $session->participantRecords()
            ->where('user_id', $author->id)
            ->where('status', SessionParticipantStatus::Confirmed->value)
            ->where('for_chat_only', true)
            ->exists();
    }

    /**
     * @return array<string, mixed>
     */
    public function present(CommunitySessionMessage $message): array
    {
        $message->loadMissing(['user', 'attachments', 'session']);

        return [
            'id' => (string) $message->id,
            'author' => SessionFeed::player($message->user, $message->session?->host_id),
            'body' => $message->body,
            'attachments' => $message->attachments
                ->map(fn (CommunitySessionMessageAttachment $attachment): array => [
                    'id' => (string) $attachment->id,
                    'url' => $attachment->url(),
                    'name' => $attachment->original_name,
                ])
                ->values()
                ->all(),
            'sent_at' => $message->created_at?->toIso8601String(),
            'is_system' => (bool) $message->is_system,
        ];
    }

    /**
     * Hard-delete messages (and storage files) for sessions closed ≥ 7 days ago.
     */
    public function pruneExpired(): int
    {
        $cutoff = now()->subDays(self::RETENTION_DAYS);
        $deleted = 0;

        CommunitySession::query()
            ->where(function ($query) use ($cutoff): void {
                $query->where(function ($cancelled) use ($cutoff): void {
                    $cancelled->whereNotNull('cancelled_at')
                        ->where('cancelled_at', '<=', $cutoff);
                })->orWhere(function ($ended) use ($cutoff): void {
                    $ended->whereNull('cancelled_at')
                        ->where('ends_at', '<=', $cutoff);
                });
            })
            ->whereHas('messages')
            ->each(function (CommunitySession $session) use (&$deleted): void {
                $deleted += $this->deleteSessionMessages($session);
            });

        return $deleted;
    }

    public function deleteSessionMessages(CommunitySession $session): int
    {
        $messages = $session->messages()->with('attachments')->get();
        $count = $messages->count();

        if ($count === 0) {
            return 0;
        }

        $paths = $messages
            ->flatMap(fn (CommunitySessionMessage $message) => $message->attachments->pluck('path'))
            ->filter()
            ->values()
            ->all();

        if ($paths !== []) {
            Storage::disk('public')->delete($paths);
        }

        Storage::disk('public')->deleteDirectory("session-chat/{$session->id}");

        $session->messages()->delete();

        return $count;
    }
}
