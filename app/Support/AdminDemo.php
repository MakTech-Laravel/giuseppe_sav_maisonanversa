<?php

namespace App\Support;

/**
 * Demo payloads for admin ops shells until commerce / community backends exist.
 */
final class AdminDemo
{
    /**
     * @return list<array{id: string, customer: string, label: string, date: string, amount: string, status: string, status_key: string}>
     */
    public static function orders(): array
    {
        return [
            [
                'id' => 'MA-2026-0047',
                'customer' => 'Founding Circle member',
                'label' => 'Heritage No.001 — Founding Edition',
                'date' => '12 March 2026',
                'amount' => '€249.00',
                'status' => 'Reserved',
                'status_key' => 'reserved',
            ],
            [
                'id' => 'MA-2026-DEP-0047',
                'customer' => 'Founding Circle member',
                'label' => 'Reservation deposit',
                'date' => '12 March 2026',
                'amount' => '€50.00',
                'status' => 'Paid',
                'status_key' => 'paid',
            ],
        ];
    }

    /**
     * @return array{
     *     id: string,
     *     customer: string,
     *     label: string,
     *     date: string,
     *     amount: string,
     *     status: string,
     *     status_key: string,
     *     summary: string,
     *     items: list<array{name: string, qty: int, price: string}>
     * }|null
     */
    public static function order(string $orderId): ?array
    {
        $order = collect(self::orders())->firstWhere('id', $orderId);

        if ($order === null) {
            return null;
        }

        $isDeposit = str_contains($orderId, 'DEP');

        return [
            ...$order,
            'summary' => $isDeposit
                ? 'Deposit held against a Founding Edition reservation. Prototype data until Stripe is connected.'
                : 'Founding Edition reservation — balance due before shipping. Prototype data until Stripe is connected.',
            'items' => $isDeposit
                ? [['name' => 'Reservation deposit', 'qty' => 1, 'price' => '€50.00']]
                : [['name' => 'Heritage No.001 — Founding Edition', 'qty' => 1, 'price' => '€249.00']],
        ];
    }

    /**
     * @return list<array{email: string, name: string, status: string, joined_at: string}>
     */
    public static function letterSubscribers(): array
    {
        return [
            [
                'email' => 'member@example.com',
                'name' => 'Sample subscriber',
                'status' => 'Active',
                'joined_at' => '2026-03-12',
            ],
        ];
    }
}
