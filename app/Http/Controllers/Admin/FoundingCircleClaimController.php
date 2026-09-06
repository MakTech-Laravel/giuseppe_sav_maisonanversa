<?php

namespace App\Http\Controllers\Admin;

use App\Enums\FoundingCircleClaimStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RejectFoundingCircleClaimRequest;
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
        $status = (string) $request->query('status', FoundingCircleClaimStatus::Pending->value);

        if (FoundingCircleClaimStatus::tryFrom($status) === null) {
            $status = 'all';
        }

        $claims = FoundingCircleClaim::query()
            ->with(['user', 'product', 'reviewedBy'])
            ->when(
                $status !== 'all',
                fn ($query) => $query->where('status', $status),
            )
            ->orderByRaw('CASE WHEN status = ? THEN 0 ELSE 1 END', [FoundingCircleClaimStatus::Pending->value])
            ->latest()
            ->get()
            ->map(fn (FoundingCircleClaim $claim): array => $this->payload($claim))
            ->values();

        return Inertia::render('admin/circle/claims', [
            'claims' => $claims,
            'pendingCount' => FoundingCircleClaim::query()->pending()->count(),
            'filters' => [
                'status' => $status,
            ],
            'statusOptions' => [
                ['value' => 'all', 'label' => __('Alle')],
                ...FoundingCircleClaimStatus::options(),
            ],
        ]);
    }

    public function approve(
        Request $request,
        string $locale,
        FoundingCircleClaim $claim,
        FoundingCircleClaimService $claims,
    ): RedirectResponse {
        $claims->approve($claim, $request->user());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Registratie goedgekeurd. Founding Circle-status geactiveerd.'),
        ]);

        return back();
    }

    public function reject(
        RejectFoundingCircleClaimRequest $request,
        string $locale,
        FoundingCircleClaim $claim,
        FoundingCircleClaimService $claims,
    ): RedirectResponse {
        $claims->reject($claim, $request->user(), $request->validated('admin_note'));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Registratie afgewezen.'),
        ]);

        return back();
    }

    /**
     * @return array{
     *     id: string,
     *     user: array{id: string, name: string, email: string},
     *     edition_number: string,
     *     racket_label: string,
     *     product_name: string|null,
     *     status: string,
     *     status_label: string,
     *     source: string,
     *     source_label: string,
     *     admin_note: string|null,
     *     reviewed_by: string|null,
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
            'user' => [
                'id' => (string) $claim->user_id,
                'name' => $claim->user?->name ?? '—',
                'email' => $claim->user?->email ?? '—',
            ],
            'edition_number' => $padded,
            'racket_label' => $padded.'/'.str_pad((string) $total, 3, '0', STR_PAD_LEFT),
            'product_name' => $claim->product?->translated('name'),
            'status' => $claim->status->value,
            'status_label' => $claim->status->label(),
            'source' => $claim->source->value,
            'source_label' => $claim->source->label(),
            'admin_note' => $claim->admin_note,
            'reviewed_by' => $claim->reviewedBy?->name,
            'created_at' => $claim->created_at?->toDateString(),
            'reviewed_at' => $claim->reviewed_at?->toDateString(),
        ];
    }
}
