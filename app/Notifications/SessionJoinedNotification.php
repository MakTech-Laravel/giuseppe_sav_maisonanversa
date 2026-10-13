<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Mail\SessionJoinedMail;
use App\Models\CommunitySession;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class SessionJoinedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public CommunitySession $session,
        public string $memberName,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): SessionJoinedMail
    {
        return (new SessionJoinedMail(
            session: $this->session,
            memberName: $this->memberName,
            locale: $notifiable->locale ?? null,
        ))->to($notifiable->routeNotificationFor('mail'));
    }

    /**
     * The matching in-app inbox entry (the bell), sent through NotificationService.
     */
    public function sendInApp(User $host): void
    {
        $locale = $host->locale ?: config('maison.default_locale');

        notify_user(
            $host,
            NotificationType::Community,
            __('Sessie-aanmelding'),
            __(':name heeft zich aangemeld voor uw sessie op :when.', [
                'name' => $this->memberName,
                'when' => $this->session->starts_at->translatedFormat('j F Y H:i'),
            ]),
            [
                'session_id' => $this->session->id,
                'link_url' => route('community.sessions.show', ['locale' => $locale, 'communitySession' => $this->session->id]),
                'link_label' => __('Bekijk sessie'),
            ],
        );
    }
}
