<?php

namespace App\Jobs;

use App\Contracts\BrevoContacts;
use App\Enums\SubscriberStatus;
use App\Mail\HeritageLetterWelcome;
use App\Models\NewsletterSubscriber;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SyncSubscriberToBrevo implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    /** @var list<int> */
    public array $backoff = [30, 60, 120];

    public function __construct(
        public NewsletterSubscriber $subscriber,
        public bool $sendWelcome = false,
        public ?string $removeEmail = null,
    ) {}

    public function handle(BrevoContacts $brevo): void
    {
        $this->subscriber->refresh();

        if (filled($this->removeEmail) && $this->removeEmail !== $this->subscriber->email) {
            $previous = new NewsletterSubscriber(['email' => $this->removeEmail]);
            $brevo->unsubscribeHeritageLetterContact($previous);
        }

        if ($this->subscriber->status === SubscriberStatus::Unsubscribed) {
            $brevo->unsubscribeHeritageLetterContact($this->subscriber);
            $this->subscriber->forceFill([
                'synced_at' => now(),
            ])->save();

            return;
        }

        $id = $brevo->upsertHeritageLetterContact($this->subscriber);

        $this->subscriber->forceFill([
            'brevo_contact_id' => $id ?? $this->subscriber->brevo_contact_id,
            'synced_at' => now(),
        ])->save();

        if ($this->sendWelcome && config('services.brevo.welcome_via') === 'local') {
            Mail::to($this->subscriber->email)
                ->locale($this->subscriber->locale)
                ->queue(new HeritageLetterWelcome($this->subscriber));
        }
    }
}
