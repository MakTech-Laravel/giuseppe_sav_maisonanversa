<?php

namespace App\Support;

final class Money
{
    /**
     * Convert a euro decimal (e.g. "99.99") to Stripe unit_amount cents without float rounding.
     */
    public static function toCents(string $amount): int
    {
        return (int) bcmul($amount, '100', 0);
    }

    /**
     * Format a stored euro decimal for display (99,99 / 249,00).
     */
    public static function format(string $amount): string
    {
        $cents = self::toCents($amount);
        $euros = intdiv($cents, 100);
        $remainder = $cents % 100;

        return number_format($euros, 0, ',', '.').','.str_pad((string) $remainder, 2, '0', STR_PAD_LEFT);
    }
}
