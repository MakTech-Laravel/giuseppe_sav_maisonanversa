<?php

namespace App\Support;

use App\Models\User;

/**
 * Demo payloads for the member dashboard shells until commerce is wired.
 *
 * User-facing strings use Dutch source keys via __() so locale switching
 * translates them the same way as the public site.
 *
 * @phpstan-type MemberCard array{name: string, username: string, email: string, editionNumber: string, orderStatus: string, reservedAt: string}
 */
final class MemberDemo
{
    /**
     * @return MemberCard
     */
    public static function member(User $user): array
    {
        return [
            'name' => $user->name,
            'username' => (string) $user->username,
            'email' => $user->email,
            'editionNumber' => '047',
            'orderStatus' => __('Gereserveerd'),
            'reservedAt' => __('12 maart 2026'),
        ];
    }

    /**
     * @return list<array{label: string, value: string, hint: string}>
     */
    public static function dashboardStats(User $user): array
    {
        $member = self::member($user);

        return [
            [
                'label' => __('Editie'),
                'value' => 'No.'.$member['editionNumber'],
                'hint' => __('Founding Edition'),
            ],
            [
                'label' => __('Bestelling'),
                'value' => $member['orderStatus'],
                'hint' => __('Verwacht Q1 2027'),
            ],
            [
                'label' => __('Circle'),
                'value' => __('Lid'),
                'hint' => __('Permanente toegang'),
            ],
        ];
    }

    /**
     * @return array{editionNumber: string, status: string, deliveryWindow: string, certificate: string, passport: string}
     */
    public static function heritage(User $user): array
    {
        $member = self::member($user);

        return [
            'editionNumber' => $member['editionNumber'],
            'status' => $member['orderStatus'],
            'deliveryWindow' => 'Q1 2027',
            'certificate' => __('Digitale voorvertoning beschikbaar'),
            'passport' => __('Vier pagina\'s · gekoppeld aan No.:number', [
                'number' => $member['editionNumber'],
            ]),
        ];
    }

    /**
     * @return list<array{id: string, label: string, date: string, amount: string, status: string, status_key: string, method: string}>
     */
    public static function orders(): array
    {
        return [
            [
                'id' => 'MA-2026-0047',
                'label' => __('Heritage No.001 — Founding Edition'),
                'date' => __('12 maart 2026'),
                'amount' => '€249.00',
                'status' => __('Gereserveerd'),
                'status_key' => 'reserved',
                'method' => __('Kaart · ··4242'),
            ],
            [
                'id' => 'MA-2026-DEP-0047',
                'label' => __('Reserveringswaarborg'),
                'date' => __('12 maart 2026'),
                'amount' => '€50.00',
                'status' => __('Betaald'),
                'status_key' => 'paid',
                'method' => __('Kaart · ··4242'),
            ],
        ];
    }

    /**
     * @return array{
     *     id: string,
     *     label: string,
     *     date: string,
     *     amount: string,
     *     status: string,
     *     status_key: string,
     *     method: string,
     *     summary: string,
     *     items: list<array{name: string, qty: int, price: string}>,
     *     billing: array{name: string, email: string, address: string},
     *     timeline: list<array{label: string, at: string, done: bool}>
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
                ? __('Waarborg gehouden op uw Founding Edition-reservering. Verrekend met het saldo wanneer de productie start.')
                : __('Founding Edition-reservering — saldo verschuldigd vóór verzending. Bedragen zijn prototype tot Stripe is aangesloten.'),
            'items' => $isDeposit
                ? [
                    [
                        'name' => __('Reserveringswaarborg'),
                        'qty' => 1,
                        'price' => '€50.00',
                    ],
                ]
                : [
                    [
                        'name' => __('Heritage No.001 — Founding Edition'),
                        'qty' => 1,
                        'price' => '€249.00',
                    ],
                ],
            'billing' => [
                'name' => __('Founding Circle-lid'),
                'email' => __('op bestand bij Maison Anversa'),
                'address' => __('Antwerpen · België (prototype)'),
            ],
            'timeline' => [
                [
                    'label' => __('Bestelling geplaatst'),
                    'at' => $order['date'],
                    'done' => true,
                ],
                [
                    'label' => $isDeposit
                        ? __('Waarborg vastgelegd')
                        : __('Reservering vastgelegd'),
                    'at' => $order['date'],
                    'done' => true,
                ],
                [
                    'label' => __('Productie'),
                    'at' => __('Verwacht Q4 2026'),
                    'done' => false,
                ],
                [
                    'label' => __('Verzending'),
                    'at' => __('Verwacht Q1 2027'),
                    'done' => false,
                ],
            ],
        ];
    }

    /**
     * @return array{editionNumber: string, pages: list<array{title: string, body: string}>}
     */
    public static function passport(User $user): array
    {
        $number = self::member($user)['editionNumber'];

        return [
            'editionNumber' => $number,
            'pages' => [
                [
                    'title' => __('Omslag'),
                    'body' => __('Heritage Passport · Maison Anversa · Editie No.:number', [
                        'number' => $number,
                    ]),
                ],
                [
                    'title' => __('Uw plaats'),
                    'body' => __('Dit genummerde stuk behoort tot de Founding Edition. Het wordt niet heruitgegeven.'),
                ],
                [
                    'title' => __('Verzorging'),
                    'body' => __('Droog bewaren. Reinig leer met een zachte doek. Het huis staat achter het vakmanschap.'),
                ],
                [
                    'title' => __('Circle'),
                    'body' => __('Founding Circle-toegang — sessies, Journal en de kamers van het huis.'),
                ],
            ],
        ];
    }

    /**
     * @return array{number: string, name: string, since: string}
     */
    public static function circleCard(User $user): array
    {
        $member = self::member($user);

        return [
            'number' => $member['editionNumber'],
            'name' => $member['name'],
            'since' => $member['reservedAt'],
        ];
    }

    /**
     * @return array{heritageLetter: bool, productUpdates: bool, events: bool}
     */
    public static function letterPreferences(): array
    {
        return [
            'heritageLetter' => true,
            'productUpdates' => true,
            'events' => false,
        ];
    }
}
