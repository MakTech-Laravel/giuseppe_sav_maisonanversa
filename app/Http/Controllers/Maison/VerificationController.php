<?php

namespace App\Http\Controllers\Maison;

use App\Http\Controllers\Controller;
use App\Models\EditionPiece;
use App\Support\PassportPresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VerificationController extends Controller
{
    public function __invoke(Request $request, string $locale, string $token, PassportPresenter $presenter): Response
    {
        $piece = EditionPiece::query()
            ->where('verification_token', $token)
            ->with('order')
            ->firstOrFail();

        return Inertia::render('maison/verify', [
            'piece' => [
                'editionNumber' => $piece->formattedNumber(),
                'status' => $piece->status->value,
                'notes' => $piece->notes,
                'allocatedAt' => $piece->allocated_at?->toDateString(),
                'owner' => $piece->order?->name,
            ],
        ]);
    }
}
