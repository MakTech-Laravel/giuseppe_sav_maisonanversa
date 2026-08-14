<?php

namespace App\Services\Brevo;

use App\Contracts\BrevoContacts;
use App\Models\NewsletterSubscriber;
use Illuminate\Support\Facades\Log;

class NullBrevoContacts implements BrevoContacts
{
    public function upsertHeritageLetterContact(NewsletterSubscriber $subscriber): ?string
    {
        Log::info('Brevo contact upsert skipped (no API key).', [
            'email' => $subscriber->email,
            'locale' => $subscriber->locale,
        ]);

        return null;
    }

    public function unsubscribeHeritageLetterContact(NewsletterSubscriber $subscriber): void
    {
        Log::info('Brevo contact unsubscribe skipped (no API key).', [
            'email' => $subscriber->email,
        ]);
    }
}
