<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCommunityCourtRequest;
use App\Http\Requests\Admin\UpdateCommunityCourtRequest;
use App\Models\CommunityCourt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CommunityCourtController extends Controller
{
    public function index(Request $request, string $locale): Response
    {
        $courts = CommunityCourt::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return Inertia::render('admin/courts/index', [
            'courts' => $courts->map(fn (CommunityCourt $court) => $this->summary($court)),
        ]);
    }

    public function create(Request $request, string $locale): Response
    {
        return Inertia::render('admin/courts/create');
    }

    public function store(StoreCommunityCourtRequest $request, string $locale): RedirectResponse
    {
        $court = CommunityCourt::query()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Club Corner aangemaakt.')]);

        return redirect()->route('admin.courts.show', [
            'locale' => $locale,
            'court' => $court->id,
        ]);
    }

    public function show(Request $request, string $locale, CommunityCourt $court): Response
    {
        return Inertia::render('admin/courts/show', [
            'court' => [
                ...$this->summary($court),
                'body' => $court->translated('body'),
                'lat' => $court->lat !== null ? (float) $court->lat : null,
                'lng' => $court->lng !== null ? (float) $court->lng : null,
                'sort_order' => $court->sort_order,
                'is_published' => $court->is_published,
            ],
        ]);
    }

    public function edit(Request $request, string $locale, CommunityCourt $court): Response
    {
        return Inertia::render('admin/courts/edit', [
            'court' => [
                'id' => (string) $court->id,
                'title' => $court->title,
                'body' => $court->body,
                'location' => $court->location,
                'lat' => $court->lat !== null ? (string) $court->lat : '',
                'lng' => $court->lng !== null ? (string) $court->lng : '',
                'sort_order' => $court->sort_order,
                'is_published' => $court->is_published,
            ],
        ]);
    }

    public function update(UpdateCommunityCourtRequest $request, string $locale, CommunityCourt $court): RedirectResponse
    {
        $court->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Club Corner bijgewerkt.')]);

        return redirect()->route('admin.courts.show', [
            'locale' => $locale,
            'court' => $court->id,
        ]);
    }

    public function destroy(Request $request, string $locale, CommunityCourt $court): RedirectResponse
    {
        $court->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Club Corner verwijderd.')]);

        return redirect()->route('admin.courts.index', ['locale' => $locale]);
    }

    /**
     * @return array{id: string, title: string, location: string, sort_order: int, is_published: bool}
     */
    private function summary(CommunityCourt $court): array
    {
        return [
            'id' => (string) $court->id,
            'title' => $court->translated('title'),
            'location' => $court->translated('location'),
            'sort_order' => $court->sort_order,
            'is_published' => $court->is_published,
        ];
    }
}
