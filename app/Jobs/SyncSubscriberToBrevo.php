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

    public function __construct(public NewsletterSubscriber $subscriber) {}

    public function handle(BrevoContacts $brevo): void
    {
        $id = $brevo->upsertHeritageLetterContact($this->subscriber);

        $this->subscriber->forceFill([
            'brevo_contact_id' => $id ?? $this->subscriber->brevo_contact_id,
            'synced_at' => now(),
            'status' => SubscriberStatus::Subscribed,
        ])->save();

        if (config('services.brevo.welcome_via') === 'local') {
            Mail::to($this->subscriber->email)
                ->locale($this->subscriber->locale)
                ->queue(new HeritageLetterWelcome($this->subscriber));
        }
    }
}
