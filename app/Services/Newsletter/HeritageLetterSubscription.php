<?php

namespace App\Services\Newsletter;

use App\Enums\SubscriberSource;
use App\Enums\SubscriberStatus;
use App\Jobs\SyncSubscriberToBrevo;
use App\Mail\WaitlistConfirmation;
use App\Models\NewsletterSubscriber;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $wasSubscribed = $subscriber->exists && $subscriber->status === SubscriberStatus::Subscribed;

        $subscriber->fill([
            'name' => $data['name'] ?? $subscriber->name,
            'locale' => $locale,
            'source' => $source,
            'status' => SubscriberStatus::Subscribed,
            'consent_at' => now(),
            'consent_ip' => $request->ip(),
            'consent_user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
            'preferences' => blank($subscriber->preferences)
                ? $this->defaultPreferences()
                : $subscriber->preferences,
        ]);

        $this->ensureUnsubscribeToken($subscriber);
        $subscriber->save();

        $this->dispatchSync($subscriber, sendWelcome: ! $wasSubscribed);

        if (in_array($source, [SubscriberSource::Waitlist, SubscriberSource::SoldOut], true)) {
            Mail::to($subscriber->email)
                ->locale($subscriber->locale)
                ->queue(new WaitlistConfirmation($subscriber));
        }

        return $subscriber;
    }

    /**
     * @param  array{heritageLetter: bool, productUpdates: bool, events: bool}  $preferences
     */
    public function applyPreferences(User $user, array $preferences, Request $request, string $locale): ?NewsletterSubscriber
    {
        $preferences = $this->normalizePreferences($preferences);
        $email = Str::lower($user->email);
        $subscriber = NewsletterSubscriber::query()->firstOrNew(['email' => $email]);
        $wantsList = $this->wantsList($preferences);

        if (! $subscriber->exists && ! $wantsList) {
            return null;
        }

        $wasSubscribed = $subscriber->exists && $subscriber->status === SubscriberStatus::Subscribed;

        $subscriber->fill([
            'name' => $user->name,
            'locale' => $user->locale ?? $locale,
            'source' => $subscriber->exists ? $subscriber->source : SubscriberSource::Member,
            'preferences' => $preferences,
            'status' => $wantsList ? SubscriberStatus::Subscribed : SubscriberStatus::Unsubscribed,
        ]);

        if ($wantsList && ! $wasSubscribed) {
            $subscriber->consent_at = now();
            $subscriber->consent_ip = $request->ip();
            $subscriber->consent_user_agent = Str::limit((string) $request->userAgent(), 500, '');
        }

        $this->ensureUnsubscribeToken($subscriber);
        $subscriber->save();

        $this->dispatchSync($subscriber, sendWelcome: $wantsList && ! $wasSubscribed);

        return $subscriber;
    }

    public function unsubscribe(NewsletterSubscriber $subscriber): NewsletterSubscriber
    {
        $subscriber->forceFill([
            'status' => SubscriberStatus::Unsubscribed,
            'preferences' => [
                'heritageLetter' => false,
                'productUpdates' => false,
                'events' => false,
            ],
        ])->save();

        $this->dispatchSync($subscriber);

        return $subscriber;
    }

    public function rebindEmail(string $oldEmail, User $user): void
    {
        $oldEmail = Str::lower($oldEmail);
        $newEmail = Str::lower($user->email);

        if ($oldEmail === $newEmail) {
            return;
        }

        $payload = DB::transaction(function () use ($oldEmail, $newEmail, $user): ?array {
            $from = NewsletterSubscriber::query()->where('email', $oldEmail)->first();

            if ($from === null) {
                return null;
            }

            $to = NewsletterSubscriber::query()->where('email', $newEmail)->first();

            if ($to === null) {
                $from->forceFill([
                    'email' => $newEmail,
                    'name' => $user->name,
                    'locale' => $user->locale ?? $from->locale,
                ])->save();

                return [
                    'subscriber' => $from,
                    'removeEmail' => $oldEmail,
                ];
            }

            $merged = $this->mergePreferences($from->preferences, $to->preferences);
            $wantsList = $this->wantsList($merged);

            $to->fill([
                'name' => $user->name ?: $to->name,
                'locale' => $user->locale ?? $to->locale,
                'status' => $wantsList ? SubscriberStatus::Subscribed : SubscriberStatus::Unsubscribed,
                'preferences' => $merged,
                'consent_at' => $to->consent_at ?? $from->consent_at,
                'consent_ip' => $to->consent_ip ?? $from->consent_ip,
                'consent_user_agent' => $to->consent_user_agent ?? $from->consent_user_agent,
                'brevo_contact_id' => $to->brevo_contact_id ?? $from->brevo_contact_id,
            ])->save();

            $from->delete();

            return [
                'subscriber' => $to,
                'removeEmail' => $oldEmail,
            ];
        });

        if ($payload === null) {
            return;
        }

        $this->dispatchSync($payload['subscriber'], removeEmail: $payload['removeEmail']);
    }

    /**
     * @return array{heritageLetter: bool, productUpdates: bool, events: bool}
     */
    public function defaultPreferences(): array
    {
        return [
            'heritageLetter' => true,
            'productUpdates' => true,
            'events' => false,
        ];
    }

    /**
     * @param  array{heritageLetter?: bool, productUpdates?: bool, events?: bool}  $preferences
     * @return array{heritageLetter: bool, productUpdates: bool, events: bool}
     */
    public function normalizePreferences(array $preferences): array
    {
        return [
            'heritageLetter' => $this->toBool($preferences['heritageLetter'] ?? false),
            'productUpdates' => $this->toBool($preferences['productUpdates'] ?? false),
            'events' => $this->toBool($preferences['events'] ?? false),
        ];
    }

    private function toBool(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @param  array{heritageLetter?: bool, productUpdates?: bool, events?: bool}  $preferences
     */
    public function wantsList(array $preferences): bool
    {
        return ($preferences['heritageLetter'] ?? false)
            || ($preferences['productUpdates'] ?? false)
            || ($preferences['events'] ?? false);
    }

    /**
     * @param  array<string, mixed>|null  $left
     * @param  array<string, mixed>|null  $right
     * @return array{heritageLetter: bool, productUpdates: bool, events: bool}
     */
    private function mergePreferences(?array $left, ?array $right): array
    {
        $left = $this->normalizePreferences($left ?? []);
        $right = $this->normalizePreferences($right ?? []);

        return [
            'heritageLetter' => $left['heritageLetter'] || $right['heritageLetter'],
            'productUpdates' => $left['productUpdates'] || $right['productUpdates'],
            'events' => $left['events'] || $right['events'],
        ];
    }

    private function ensureUnsubscribeToken(NewsletterSubscriber $subscriber): void
    {
        if (blank($subscriber->unsubscribe_token)) {
            $subscriber->unsubscribe_token = (string) Str::uuid();
        }
    }

    private function dispatchSync(
        NewsletterSubscriber $subscriber,
        bool $sendWelcome = false,
        ?string $removeEmail = null,
    ): void {
        SyncSubscriberToBrevo::dispatch($subscriber, $sendWelcome, $removeEmail);
    }
}
