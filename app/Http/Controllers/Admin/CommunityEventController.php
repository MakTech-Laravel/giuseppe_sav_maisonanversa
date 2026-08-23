<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCommunityEventRequest;
use App\Http\Requests\Admin\UpdateCommunityEventRequest;
use App\Models\CommunityEvent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
        $data = $request->safe()->except(['thumbnail']);

        if ($request->hasFile('thumbnail')) {
            $data['thumbnail'] = $this->storeThumbnail($request->file('thumbnail'));
        }

        $event = CommunityEvent::query()->create($data);

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
                'bookings' => $event->rsvps
                    ->sortBy('created_at')
                    ->values()
                    ->map(fn ($rsvp) => [
                        'id' => (string) $rsvp->id,
                        'user_id' => (string) $rsvp->user_id,
                        'name' => $rsvp->user->name,
                        'email' => $rsvp->user->email,
                        'booked_at' => $rsvp->created_at?->toIso8601String(),
                    ])
                    ->all(),
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
                'thumbnail_url' => $event->thumbnailUrl(),
            ],
        ]);
    }

    public function update(UpdateCommunityEventRequest $request, string $locale, CommunityEvent $event): RedirectResponse
    {
        $data = $request->safe()->except(['thumbnail', 'remove_thumbnail']);

        if ($request->hasFile('thumbnail')) {
            $this->deleteThumbnail($event);
            $data['thumbnail'] = $this->storeThumbnail($request->file('thumbnail'));
        } elseif ($request->boolean('remove_thumbnail')) {
            $this->deleteThumbnail($event);
            $data['thumbnail'] = null;
        }

        $event->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Evenement bijgewerkt.')]);

        return redirect()->route('admin.events.show', [
            'locale' => $locale,
            'event' => $event->id,
        ]);
    }

    public function destroy(Request $request, string $locale, CommunityEvent $event): RedirectResponse
    {
        $this->deleteThumbnail($event);
        $event->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Evenement verwijderd.')]);

        return redirect()->route('admin.events.index', ['locale' => $locale]);
    }

    /**
     * @return array{id: string, title: string, starts_at: string, location: string, capacity: int|null, rsvp_count: int, thumbnail_url: string|null}
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
            'thumbnail_url' => $event->thumbnailUrl(),
        ];
    }

    private function storeThumbnail(UploadedFile $file): string
    {
        return $file->store('community-events', 'public');
    }

    private function deleteThumbnail(CommunityEvent $event): void
    {
        if ($event->thumbnail === null || $event->thumbnail === '') {
            return;
        }

        Storage::disk('public')->delete($event->thumbnail);
    }
}
