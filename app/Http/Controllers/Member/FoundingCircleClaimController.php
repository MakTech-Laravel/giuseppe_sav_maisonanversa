<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Requests\Member\StoreFoundingCircleClaimRequest;
use App\Models\FoundingCircleClaim;
use App\Services\FoundingCircle\FoundingCircleClaimService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FoundingCircleClaimController extends Controller
{
    public function index(Request $request, string $locale): Response
    {
        $claims = FoundingCircleClaim::query()
            ->where('user_id', $request->user()->id)
            ->with('product')
            ->latest()
            ->get()
            ->map(fn (FoundingCircleClaim $claim): array => $this->payload($claim))
            ->values();

        return Inertia::render('member/racket-registration', [
            'claims' => $claims,
            'canSubmit' => ! FoundingCircleClaim::query()
                ->where('user_id', $request->user()->id)
                ->pending()
                ->exists(),
        ]);
    }

    public function store(
        StoreFoundingCircleClaimRequest $request,
        string $locale,
        FoundingCircleClaimService $claims,
    ): RedirectResponse {
        $claims->createManual($request->user(), $request->validated('serial'));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Registratie ontvangen. We controleren uw serienummer.'),
        ]);

        return back();
    }

    /**
     * @return array{
     *     id: string,
     *     edition_number: string,
     *     racket_label: string,
     *     product_name: string|null,
     *     status: string,
     *     status_label: string,
     *     source: string,
     *     source_label: string,
     *     admin_note: string|null,
     *     created_at: string|null,
     *     reviewed_at: string|null
     * }
     */
    private function payload(FoundingCircleClaim $claim): array
    {
        $total = (int) ($claim->product?->edition_total ?? 100);
        $padded = $claim->paddedEditionNumber();

        return [
            'id' => (string) $claim->id,
            'edition_number' => $padded,
            'racket_label' => $padded.'/'.str_pad((string) $total, 3, '0', STR_PAD_LEFT),
            'product_name' => $claim->product?->translated('name'),
            'status' => $claim->status->value,
            'status_label' => $claim->status->label(),
            'source' => $claim->source->value,
            'source_label' => $claim->source->label(),
            'admin_note' => $claim->admin_note,
            'created_at' => $claim->created_at?->toDateString(),
            'reviewed_at' => $claim->reviewed_at?->toDateString(),
        ];
    }
}
