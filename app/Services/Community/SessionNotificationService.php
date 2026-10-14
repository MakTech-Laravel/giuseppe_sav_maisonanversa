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

/**
 * Session notifications from the client brief: every notice is an in-app
 * notification plus a mail, rendered in the recipient's own locale.
 *
 * Whoever triggered the action never hears about it. Members can switch the
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

    public function joined(CommunitySession $session, User $joiner): void
    {
        $session->loadMissing('host');

        if ($session->host !== null) {
            $this->deliver(SessionNotice::NewPlayer, new Collection([$session->host]), $session, $joiner, $joiner);
        }

        $this->deliver(SessionNotice::JoinConfirmed, new Collection([$joiner]), $session, $joiner);

        if ($this->freshPlayerCount($session) >= $session->capacity) {
            $this->deliver(SessionNotice::SessionFull, $this->everyone($session), $session, $joiner, $joiner);
            $this->deliver(SessionNotice::AdminFull, $this->admins(), $session, $joiner, $joiner);
        }
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
        $previousLocale = app()->getLocale();

        try {
            foreach ($recipients->groupBy(fn (User $user): string => MailLocale::resolve($user->locale)) as $locale => $group) {
                app()->setLocale($locale);

                $sessionUrl = self::sessionUrl($session, $notice->isForAdmins(), $locale);

                notify_users(
                    $group,
                    NotificationType::Community,
                    $notice->subject(),
                    $notice->inAppMessage(
                        $subject->name,
                        $session->starts_at->translatedFormat('l j F Y, H:i'),
                        $freePlaces,
                    ),
                    [
                        'session_id' => $session->id,
                        'notice' => $notice->value,
                        'link_url' => $sessionUrl,
                        'link_label' => __('Bekijk sessie'),
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
            SessionNotice::Cancelled, SessionNotice::Removed => route('community.sessions.index', ['locale' => $locale]),
            default => self::sessionUrl($session, $notice->isForAdmins(), $locale),
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
    private function admins(): Collection
    {
        return User::query()->where('type', UserType::Admin)->get();
    }

    private function freshPlayerCount(CommunitySession $session): int
    {
        return $session->participants()->count();
    }
}
