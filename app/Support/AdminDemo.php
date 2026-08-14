<?php

namespace App\Support;

use App\Models\Product;

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

    /**
     * @return list<array{id: string, title: string, date: string, location: string, capacity: string, attendees: string, status: string, status_key: string}>
     */
    public static function events(): array
    {
        return [
            [
                'id' => 'EVT-LAUNCH-001',
                'title' => 'Heritage No.001 Launch Event',
                'date' => '18 April 2026',
                'location' => 'Antwerpen — Maison salon',
                'capacity' => '80',
                'attendees' => '64',
                'status' => 'Upcoming',
                'status_key' => 'upcoming',
            ],
            [
                'id' => 'EVT-COURT-002',
                'title' => 'Founding Circle Court Evening',
                'date' => '9 May 2026',
                'location' => 'Brussel — Private court',
                'capacity' => '24',
                'attendees' => '24',
                'status' => 'Full',
                'status_key' => 'full',
            ],
            [
                'id' => 'EVT-LETTER-003',
                'title' => 'Heritage Letter Reading',
                'date' => '12 February 2026',
                'location' => 'Amsterdam — Member lounge',
                'capacity' => '40',
                'attendees' => '38',
                'status' => 'Past',
                'status_key' => 'past',
            ],
        ];
    }

    /**
     * @return array{
     *     id: string,
     *     title: string,
     *     date: string,
     *     location: string,
     *     capacity: string,
     *     attendees: string,
     *     status: string,
     *     status_key: string,
     *     summary: string,
     *     guest_list: list<array{name: string, email: string, rsvp: string}>
     * }|null
     */
    public static function event(string $eventId): ?array
    {
        $event = collect(self::events())->firstWhere('id', $eventId);

        if ($event === null) {
            return null;
        }

        return [
            ...$event,
            'summary' => 'Prototype event record until the events service is connected. RSVPs and capacity are demo data.',
            'guest_list' => [
                ['name' => 'Thomas J.', 'email' => 'thomas@example.com', 'rsvp' => 'Confirmed'],
                ['name' => 'Amelie V.', 'email' => 'amelie@example.com', 'rsvp' => 'Confirmed'],
                ['name' => 'Marc K.', 'email' => 'marc@example.com', 'rsvp' => 'Waitlist'],
            ],
        ];
    }

    /**
     * @return list<array{id: string, edition: string, name: string, email: string, status: string, status_key: string, joined_at: string}>
     */
    public static function circleMembers(): array
    {
        return [
            [
                'id' => 'FC-007',
                'edition' => '007',
                'name' => 'Thomas J.',
                'email' => 'thomas@example.com',
                'status' => 'Active',
                'status_key' => 'active',
                'joined_at' => '2026-03-12',
            ],
            [
                'id' => 'FC-023',
                'edition' => '023',
                'name' => 'Amelie V.',
                'email' => 'amelie@example.com',
                'status' => 'Active',
                'status_key' => 'active',
                'joined_at' => '2026-03-18',
            ],
            [
                'id' => 'FC-041',
                'edition' => '041',
                'name' => 'Marc K.',
                'email' => 'marc@example.com',
                'status' => 'Reserved',
                'status_key' => 'reserved',
                'joined_at' => '2026-04-02',
            ],
        ];
    }

    /**
     * @return array{
     *     id: string,
     *     edition: string,
     *     name: string,
     *     email: string,
     *     status: string,
     *     status_key: string,
     *     joined_at: string,
     *     summary: string,
     *     benefits: list<string>
     * }|null
     */
    public static function circleMember(string $memberId): ?array
    {
        $member = collect(self::circleMembers())->firstWhere('id', $memberId);

        if ($member === null) {
            return null;
        }

        return [
            ...$member,
            'summary' => 'Founding Circle roster entry for Heritage No.001 (limited to 100 pieces). Demo data until membership is linked to paid editions.',
            'benefits' => [
                'Founding Edition certificate',
                'Private court invitations',
                'Heritage Letter priority',
            ],
        ];
    }

    /**
     * @return array{
     *     product_name: string,
     *     total: int,
     *     reserved: int,
     *     available: int,
     *     rows: list<array{sku: string, label: string, status: string, status_key: string, notes: string}>
     * }
     */
    public static function heritageInventory(): array
    {
        $reserved = 0;
        $total = (int) config('maison.edition.total', 100);

        return [
            'product_name' => Product::founding()?->name ?? 'Heritage No.001 — Founding Edition',
            'total' => $total,
            'reserved' => $reserved,
            'available' => max(0, $total - $reserved),
            'rows' => [
                [
                    'sku' => 'HN001-FE',
                    'label' => 'Heritage No.001 — Founding Edition',
                    'status' => 'On sale',
                    'status_key' => 'on_sale',
                    'notes' => 'Single SKU; edition numbers assigned at checkout.',
                ],
                [
                    'sku' => 'HN001-CERT',
                    'label' => 'Certificate & passport pack',
                    'status' => 'Bundled',
                    'status_key' => 'bundled',
                    'notes' => 'Ships with each reserved edition.',
                ],
                [
                    'sku' => 'HN001-GIFT',
                    'label' => 'Gift presentation sleeve',
                    'status' => 'Optional',
                    'status_key' => 'optional',
                    'notes' => 'Add-on selected during pre-order.',
                ],
            ],
        ];
    }

    /**
     * @return list<array{id: string, author: string, excerpt: string, type: string, status: string, status_key: string, date: string}>
     */
    public static function communityQueue(): array
    {
        return [
            [
                'id' => 'CQ-1001',
                'author' => 'Lisa B.',
                'excerpt' => 'Looking for a doubles partner next Thursday in Antwerpen…',
                'type' => 'post',
                'status' => 'Open',
                'status_key' => 'open',
                'date' => '2026-04-08',
            ],
            [
                'id' => 'CQ-1002',
                'author' => 'Guest',
                'excerpt' => 'Spam link in reply under official launch post.',
                'type' => 'comment',
                'status' => 'Reported',
                'status_key' => 'reported',
                'date' => '2026-04-09',
            ],
            [
                'id' => 'CQ-1003',
                'author' => 'Maison Anversa',
                'excerpt' => 'Official: Court evening seats are now full.',
                'type' => 'post',
                'status' => 'Approved',
                'status_key' => 'approved',
                'date' => '2026-04-10',
            ],
        ];
    }
}
