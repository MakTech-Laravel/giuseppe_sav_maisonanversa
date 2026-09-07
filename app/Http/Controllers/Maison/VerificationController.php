<?php

namespace App\Http\Controllers\Maison;

use App\Http\Controllers\Controller;
use App\Models\EditionPiece;
use App\Models\FoundingCircleClaim;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VerificationController extends Controller
{
    public function __invoke(Request $request, string $locale, string $token): Response
    {
        $piece = EditionPiece::query()
            ->where('verification_token', $token)
            ->with([
                'order.user',
                'product',
                'foundingCircleClaims' => fn ($query) => $query
                    ->approved()
                    ->with('user')
                    ->latest('reviewed_at'),
            ])
            ->firstOrFail();

        $total = (int) ($piece->product?->edition_total ?? 0);
        $claim = $piece->approvedClaim();
        $holder = $this->holder($piece, $claim);

        return Inertia::render('maison/verify', [
            'piece' => [
                'productName' => $piece->product?->translated('name'),
                'editionTotal' => $total,
                'editionNumber' => $piece->formattedNumber(),
                'status' => $piece->status->value,
                'statusLabel' => $piece->status->label(),
                'allocatedAt' => $piece->allocated_at?->translatedFormat('j F Y'),
                'holder' => $holder,
            ],
        ]);
    }

    /**
     * @return array{
     *     name: string,
     *     username: string|null,
     *     isFoundingCircle: bool,
     *     memberSince: string|null,
     *     source: 'order'|'claim'
     * }|null
     */
    private function holder(EditionPiece $piece, ?FoundingCircleClaim $claim): ?array
    {
        $user = $piece->order?->user ?? $claim?->user;
        $name = filled($piece->order?->name)
            ? (string) $piece->order->name
            : ($user?->name ?? null);

        if ($name === null && $user === null) {
            return null;
        }

        $memberSince = $claim?->reviewed_at
            ?? $piece->allocated_at
            ?? $user?->created_at;

        return [
            'name' => $name ?? (string) $user?->name,
            'username' => $user?->username,
            'isFoundingCircle' => $user instanceof User ? $user->isFoundingCircle() : false,
            'memberSince' => $memberSince?->translatedFormat('j F Y'),
            'source' => $piece->order !== null ? 'order' : 'claim',
        ];
    }
}
