<?php

namespace App\Services\Newsletter;

use App\Enums\SubscriberSource;
use App\Enums\SubscriberStatus;
use App\Jobs\SyncSubscriberToBrevo;
use App\Mail\WaitlistConfirmation;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class HeritageLetterSubscription
{
    /**
     * @param  array{email: string, name?: string|null, source: string}  $data
     */
    public function subscribe(array $data, Request $request, string $locale): NewsletterSubscriber
    {
        $source = SubscriberSource::from($data['source']);
        $email = Str::lower($data['email']);

        $subscriber = NewsletterSubscriber::query()->firstOrNew(['email' => $email]);

        $subscriber->fill([
            'name' => $data['name'] ?? $subscriber->name,
            'locale' => $locale,
            'source' => $source,
            'status' => SubscriberStatus::Subscribed,
            'consent_at' => now(),
            'consent_ip' => $request->ip(),
            'consent_user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
            'preferences' => $subscriber->preferences ?? [
                'heritageLetter' => true,
                'productUpdates' => true,
                'events' => false,
            ],
        ]);

        if (blank($subscriber->unsubscribe_token)) {
            $subscriber->unsubscribe_token = (string) Str::uuid();
        }

        $subscriber->save();

        SyncSubscriberToBrevo::dispatch($subscriber);

        if (in_array($source, [SubscriberSource::Waitlist, SubscriberSource::SoldOut], true)) {
            Mail::to($subscriber->email)
                ->locale($subscriber->locale)
                ->queue(new WaitlistConfirmation($subscriber));
        }

        return $subscriber;
    }
}
