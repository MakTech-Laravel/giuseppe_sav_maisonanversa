<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCommunityEventRequest;
use App\Http\Requests\Admin\UpdateCommunityEventRequest;
use App\Models\CommunityEvent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CommunityEventController extends Controller
{
    public function index(Request $request, string $locale): Response
    {
        $events = CommunityEvent::query()
            ->withCount('rsvps')
            ->orderBy('starts_at')
            ->get();

        return Inertia::render('admin/events/index', [
            'events' => $events->map(fn (CommunityEvent $event) => $this->summary($event)),
        ]);
    }

    public function create(Request $request, string $locale): Response
    {
        return Inertia::render('admin/events/create');
    }

    public function store(StoreCommunityEventRequest $request, string $locale): RedirectResponse
    {
        $event = CommunityEvent::query()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Evenement aangemaakt.')]);

        return redirect()->route('admin.events.show', [
            'locale' => $locale,
            'event' => $event->id,
        ]);
    }

    public function show(Request $request, string $locale, CommunityEvent $event): Response
    {
        $event->loadCount('rsvps')->load('rsvps.user');

        return Inertia::render('admin/events/show', [
            'event' => [
                ...$this->summary($event),
                'description' => $event->translated('description'),
                'guest_list' => $event->rsvps->map(fn ($rsvp) => [
                    'name' => $rsvp->user->name,
                    'email' => $rsvp->user->email,
                ])->all(),
            ],
        ]);
    }

    public function edit(Request $request, string $locale, CommunityEvent $event): Response
    {
        return Inertia::render('admin/events/edit', [
            'event' => [
                'id' => (string) $event->id,
                'title' => $event->title,
                'description' => $event->description,
                'starts_at' => $event->starts_at?->format('Y-m-d\TH:i'),
                'location' => $event->location,
                'capacity' => $event->capacity,
            ],
        ]);
    }

    public function update(UpdateCommunityEventRequest $request, string $locale, CommunityEvent $event): RedirectResponse
    {
        $event->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Evenement bijgewerkt.')]);

        return redirect()->route('admin.events.show', [
            'locale' => $locale,
            'event' => $event->id,
        ]);
    }

    public function destroy(Request $request, string $locale, CommunityEvent $event): RedirectResponse
    {
        $event->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Evenement verwijderd.')]);

        return redirect()->route('admin.events.index', ['locale' => $locale]);
    }

    /**
     * @return array{id: string, title: string, starts_at: string, location: string, capacity: int|null, rsvp_count: int}
     */
    private function summary(CommunityEvent $event): array
    {
        return [
            'id' => (string) $event->id,
            'title' => $event->translated('title'),
            'starts_at' => $event->starts_at->toIso8601String(),
            'location' => $event->translated('location'),
            'capacity' => $event->capacity,
            'rsvp_count' => (int) ($event->rsvps_count ?? $event->rsvps()->count()),
        ];
    }
}
