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

    /**
     * Resolve a guest checkout order from the public MA-YYYY-#### reference
     * and matching email. Returns null when the pair does not match or the
     * order is not eligible for a guest return link.
     */
    public function findEligibleGuestOrder(string $reference, string $email): ?Order
    {
        if (! preg_match('/^MA-(\d{4})-(\d+)$/i', trim($reference), $matches)) {
            return null;
        }

        $order = Order::query()
            ->whereKey((int) $matches[2])
            ->whereNull('user_id')
            ->first();

        if ($order === null || ! $this->emailMatches($order, $email)) {
            return null;
        }

        if ((string) $order->created_at?->format('Y') !== $matches[1]) {
            return null;
        }

        return $this->canIssue($order) ? $order : null;
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
