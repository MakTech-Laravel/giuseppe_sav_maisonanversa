<?php

namespace App\Support;

use App\Enums\RegisterVisibility;
use App\Models\FoundingCircleRegisterEntry;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Collection;

class FoundingCircleRegisterPresenter
{
    /**
     * @return list<array{
     *     number: string,
     *     sequence: int,
     *     state: string,
     *     label_key: string|null,
     *     name: string|null,
     *     year: string|null,
     *     italic: bool,
     *     you: bool
     * }>
     */
    public function ledger(?User $viewer = null): array
    {
        $product = Product::founding();
        $total = (int) ($product?->edition_total ?? 100);
        $archived = $product?->archiveEditionNumberList() ?? [];

        /** @var Collection<int, FoundingCircleRegisterEntry> $entries */
        $entries = FoundingCircleRegisterEntry::query()
            ->with('user')
            ->whereNotNull('edition_number')
            ->get()
            ->keyBy(fn (FoundingCircleRegisterEntry $entry): int => (int) $entry->edition_number);

        $places = [];

        for ($sequence = 1; $sequence <= $total; $sequence++) {
            $entry = $entries->get($sequence);
            $places[] = $this->place($sequence, $entry, in_array($sequence, $archived, true), $viewer);
        }

        return $places;
    }

    /**
     * @return array{number: string, label: string}
     */
    public function latestEntry(FoundingCircleRegisterEntry $entry): array
    {
        $entry->loadMissing('user');
        $place = $this->place((int) $entry->edition_number, $entry, false, null);

        return [
            'number' => $place['number'],
            'label' => $place['name'] ?? __($place['label_key'] ?? 'Privélid'),
        ];
    }

    /**
     * @return list<array{
     *     id: string|null,
     *     number: string,
     *     sequence: int,
     *     state: string,
     *     member_name: string|null,
     *     email: string|null,
     *     visibility: string|null,
     *     visibility_label: string|null,
     *     consent_at: string|null,
     *     hidden: bool,
     *     user_id: int|null
     * }>
     */
    public function adminLedger(?string $search = null): array
    {
        $product = Product::founding();
        $total = (int) ($product?->edition_total ?? 100);
        $archived = $product?->archiveEditionNumberList() ?? [];
        $needle = $search !== null ? mb_strtolower(trim($search)) : '';

        /** @var Collection<int, FoundingCircleRegisterEntry> $entries */
        $entries = FoundingCircleRegisterEntry::query()
            ->with('user')
            ->whereNotNull('edition_number')
            ->get()
            ->keyBy(fn (FoundingCircleRegisterEntry $entry): int => (int) $entry->edition_number);

        $places = [];

        for ($sequence = 1; $sequence <= $total; $sequence++) {
            $entry = $entries->get($sequence);
            $row = [
                'id' => $entry !== null ? (string) $entry->id : null,
                'number' => str_pad((string) $sequence, 3, '0', STR_PAD_LEFT),
                'sequence' => $sequence,
                'state' => $entry !== null ? 'inscribed' : (in_array($sequence, $archived, true) ? 'archive' : 'available'),
                'member_name' => $entry?->user?->name,
                'email' => $entry?->user?->email,
                'visibility' => $entry?->register_visibility->value,
                'visibility_label' => $entry?->register_visibility->label(),
                'consent_at' => $entry?->register_consent_at?->toDateString(),
                'hidden' => (bool) $entry?->register_hidden_by_admin,
                'user_id' => $entry?->user_id,
            ];

            if ($needle !== '') {
                $haystack = mb_strtolower(implode(' ', array_filter([
                    $row['number'],
                    $row['member_name'],
                    $row['email'],
                    $row['state'],
                ])));

                if (! str_contains($haystack, $needle)) {
                    continue;
                }
            }

            $places[] = $row;
        }

        return $places;
    }

    /**
     * Preview of the signed-in member's own row for the listing form.
     *
     * @return array{number: string, full: string, initial: string, private: string, visibility: string, consent: bool}|null
     */
    public function listingPreview(User $user): ?array
    {
        $entry = FoundingCircleRegisterEntry::query()
            ->where('user_id', $user->id)
            ->whereNotNull('edition_number')
            ->first();

        if ($entry === null) {
            return null;
        }

        $number = str_pad((string) $entry->edition_number, 3, '0', STR_PAD_LEFT);

        return [
            'number' => $number,
            'full' => $number.'  '.PersonName::publicLabel($user, RegisterVisibility::Full),
            'initial' => $number.'  '.PersonName::publicLabel($user, RegisterVisibility::Initial),
            'private' => $number.'  '.__('Privélid'),
            'visibility' => $entry->register_visibility->value,
            'consent' => $entry->register_consent_at !== null,
        ];
    }

    /**
     * @return array{
     *     number: string,
     *     sequence: int,
     *     state: string,
     *     label_key: string|null,
     *     name: string|null,
     *     year: string|null,
     *     italic: bool,
     *     you: bool
     * }
     */
    private function place(int $sequence, ?FoundingCircleRegisterEntry $entry, bool $archived, ?User $viewer): array
    {
        $number = str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);

        if ($entry === null) {
            return [
                'number' => $number,
                'sequence' => $sequence,
                'state' => $archived ? 'archive' : 'available',
                'label_key' => $archived ? 'Niet te koop' : 'Beschikbaar',
                'name' => null,
                'year' => null,
                'italic' => false,
                'you' => false,
            ];
        }

        $you = $viewer !== null && (int) $entry->user_id === (int) $viewer->id;
        $year = $entry->joined_at?->format('Y');

        if ($entry->showsPrivateLabel() || $entry->user === null) {
            return [
                'number' => $number,
                'sequence' => $sequence,
                'state' => 'inscribed',
                'label_key' => 'Privélid',
                'name' => null,
                'year' => $year,
                'italic' => true,
                'you' => $you,
            ];
        }

        return [
            'number' => $number,
            'sequence' => $sequence,
            'state' => 'inscribed',
            'label_key' => null,
            'name' => PersonName::publicLabel($entry->user, $entry->register_visibility),
            'year' => $year,
            'italic' => false,
            'you' => $you,
        ];
    }
}
