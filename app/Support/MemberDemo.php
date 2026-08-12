<?php

namespace App\Support;

use App\Models\User;

/**
 * Demo payloads for the member dashboard shells until commerce is wired.
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
            'orderStatus' => 'Reserved',
            'reservedAt' => '12 March 2026',
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
                'label' => 'Edition',
                'value' => 'No.'.$member['editionNumber'],
                'hint' => 'Founding Edition',
            ],
            [
                'label' => 'Order',
                'value' => $member['orderStatus'],
                'hint' => 'Expected Q1 2027',
            ],
            [
                'label' => 'Circle',
                'value' => 'Member',
                'hint' => 'Permanent access',
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
            'certificate' => 'Digital preview available',
            'passport' => 'Four pages · linked to No.'.$member['editionNumber'],
        ];
    }

    /**
     * @return list<array{id: string, label: string, date: string, amount: string, status: string, method: string}>
     */
    public static function orders(): array
    {
        return [
            [
                'id' => 'MA-2026-0047',
                'label' => 'Heritage No.001 — Founding Edition',
                'date' => '12 March 2026',
                'amount' => '€249.00',
                'status' => 'Reserved',
                'method' => 'Card · ··4242',
            ],
            [
                'id' => 'MA-2026-DEP-0047',
                'label' => 'Reservation deposit',
                'date' => '12 March 2026',
                'amount' => '€50.00',
                'status' => 'Paid',
                'method' => 'Card · ··4242',
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
                    'title' => 'Cover',
                    'body' => 'Heritage Passport · Maison Anversa · Edition No.'.$number,
                ],
                [
                    'title' => 'Your place',
                    'body' => 'This numbered piece belongs to the Founding Edition. It will not be reissued.',
                ],
                [
                    'title' => 'Care',
                    'body' => 'Store dry. Clean leather with a soft cloth. The house stands behind the craft.',
                ],
                [
                    'title' => 'Circle',
                    'body' => 'Founding Circle access — sessions, Journal, and the rooms of the house.',
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
