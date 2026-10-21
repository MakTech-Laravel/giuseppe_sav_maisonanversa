<?php

namespace App\Services\Returns;

use App\Models\Order;
use App\Models\OrderReturn;
use Illuminate\Support\Facades\URL;

class GuestReturnLink
{
    public function canIssue(Order $order): bool
    {
        return $order->user_id === null
            && filled($order->email)
            && app(ReturnEligibility::class)->canRequestReturn($order);
    }

    public function createUrl(Order $order, ?string $locale = null): ?string
    {
        if (! $this->canIssue($order)) {
            return null;
        }

        $locale = $locale ?: ($order->locale ?: config('app.locale'));

        return URL::temporarySignedRoute(
            'maison.guest-returns.create',
            now()->addHours((int) config('maison.returns.guest_link_ttl_hours')),
            [
                'locale' => $locale,
                'order' => $order->id,
                'email' => $order->email,
            ],
        );
    }

    public function showUrl(OrderReturn $return, ?string $locale = null): string
    {
        $return->loadMissing('order');
        $locale = $locale ?: ($return->order?->locale ?: config('app.locale'));

        return URL::temporarySignedRoute(
            'maison.guest-returns.show',
            now()->addHours((int) config('maison.returns.guest_link_ttl_hours')),
            [
                'locale' => $locale,
                'orderReturn' => $return->id,
                'email' => $return->order?->email,
            ],
        );
    }

    public function trackingUrl(OrderReturn $return, ?string $locale = null): string
    {
        $return->loadMissing('order');
        $locale = $locale ?: ($return->order?->locale ?: config('app.locale'));

        return URL::temporarySignedRoute(
            'maison.guest-returns.tracking',
            now()->addHours((int) config('maison.returns.guest_link_ttl_hours')),
            [
                'locale' => $locale,
                'orderReturn' => $return->id,
                'email' => $return->order?->email,
            ],
        );
    }

    public function emailMatches(Order $order, ?string $email): bool
    {
        if (! filled($order->email) || ! filled($email)) {
            return false;
        }

        return hash_equals(
            mb_strtolower(trim((string) $order->email)),
            mb_strtolower(trim($email)),
        );
    }

    public function sessionKey(Order $order): string
    {
        return 'guest_return_order.'.$order->id;
    }
}
