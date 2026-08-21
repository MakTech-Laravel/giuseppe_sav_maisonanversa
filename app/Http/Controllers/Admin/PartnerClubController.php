<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PartnerClub;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PartnerClubController extends Controller
{
    public function index(Request $request, string $locale): Response
    {
        $clubs = PartnerClub::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return Inertia::render('admin/partner-clubs/index', [
            'clubs' => $clubs->map(fn (PartnerClub $club): array => [
                'id' => (string) $club->id,
                'city' => $club->translated('city'),
                'country' => $club->translated('country'),
                'status' => $club->status,
                'sort_order' => $club->sort_order,
                'is_published' => $club->is_published,
            ]),
        ]);
    }

    public function create(Request $request, string $locale): Response
    {
        return Inertia::render('admin/partner-clubs/create');
    }

    public function store(Request $request, string $locale): RedirectResponse
    {
        $club = PartnerClub::query()->create($this->validated($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Partner club aangemaakt.')]);

        return redirect()->route('admin.partner-clubs.edit', [
            'locale' => $locale,
            'partnerClub' => $club->id,
        ]);
    }

    public function edit(Request $request, string $locale, PartnerClub $partnerClub): Response
    {
        return Inertia::render('admin/partner-clubs/edit', [
            'club' => [
                'id' => (string) $partnerClub->id,
                'city' => $partnerClub->city,
                'country' => $partnerClub->country,
                'status' => $partnerClub->status,
                'sort_order' => $partnerClub->sort_order,
                'is_published' => $partnerClub->is_published,
            ],
        ]);
    }

    public function update(Request $request, string $locale, PartnerClub $partnerClub): RedirectResponse
    {
        $partnerClub->update($this->validated($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Partner club bijgewerkt.')]);

        return redirect()->route('admin.partner-clubs.edit', [
            'locale' => $locale,
            'partnerClub' => $partnerClub->id,
        ]);
    }

    public function destroy(Request $request, string $locale, PartnerClub $partnerClub): RedirectResponse
    {
        $partnerClub->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Partner club verwijderd.')]);

        return redirect()->route('admin.partner-clubs.index', ['locale' => $locale]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'city' => ['required', 'string', 'max:255'],
            'country' => ['required', 'string', 'max:255'],
            'status' => ['required', 'string', 'in:in_discussion,open,active'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_published' => ['required', 'boolean'],
        ]);
    }
}
