<?php

namespace App\Notifications;

use App\Models\CommunitySession;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
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
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Iemand heeft zich aangemeld voor uw sessie'))
            ->line(__(':name heeft zich aangemeld voor uw sessie op :when.', [
                'name' => $this->memberName,
                'when' => $this->session->starts_at->translatedFormat('j F Y H:i'),
            ]))
            ->line($this->session->location);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => __('Sessie-aanmelding'),
            'body' => __(':name heeft zich aangemeld voor uw sessie op :when.', [
                'name' => $this->memberName,
                'when' => $this->session->starts_at->translatedFormat('j F Y H:i'),
            ]),
            'session_id' => $this->session->id,
            'member_name' => $this->memberName,
        ];
    }
}
