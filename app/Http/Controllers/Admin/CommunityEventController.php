<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CommunityEventStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCommunityEventRequest;
use App\Http\Requests\Admin\UpdateCommunityEventRequest;
use App\Http\Requests\Admin\UpdateCommunityEventStatusRequest;
use App\Models\CommunityEvent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class CommunityEventController extends Controller
{
    private const PER_PAGE = 15;

    public function index(Request $request, string $locale): Response
    {
        $filters = $this->indexFilters($request);

        $events = CommunityEvent::query()
            ->withCount('rsvps')
            ->when($filters['search'] !== '', function ($query) use ($filters): void {
                $search = $filters['search'];
                $query->where(function ($inner) use ($search): void {
                    $inner->where('title', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when(
                $filters['status'] !== '',
                fn ($query) => $query->status($filters['status']),
            )
            ->when(
                $filters['status'] === CommunityEventStatus::Closed->value,
                fn ($query) => $query->orderByDesc('starts_at'),
                fn ($query) => $query->orderBy('starts_at'),
            )
            ->paginate(self::PER_PAGE)
            ->withQueryString()
            ->through(fn (CommunityEvent $event): array => $this->summary($event));

        return Inertia::render('admin/events/index', [
            'events' => $events,
            'filters' => $filters,
        ]);
    }

    public function create(Request $request, string $locale): Response
    {
        return Inertia::render('admin/events/create');
    }

    public function store(StoreCommunityEventRequest $request, string $locale): RedirectResponse
    {
        $data = $request->safe()->except(['thumbnail']);
        $data['status'] = CommunityEventStatus::Opening;

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

    public function updateStatus(
        UpdateCommunityEventStatusRequest $request,
        string $locale,
        CommunityEvent $event,
    ): RedirectResponse {
        $event->update([
            'status' => $request->enum('status', CommunityEventStatus::class),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Status bijgewerkt.')]);

        return back();
    }

    public function destroy(Request $request, string $locale, CommunityEvent $event): RedirectResponse
    {
        $this->deleteThumbnail($event);
        $event->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Evenement verwijderd.')]);

        return redirect()->route('admin.events.index', ['locale' => $locale]);
    }

    /**
     * @return array{search: string, status: string}
     */
    private function indexFilters(Request $request): array
    {
        $search = trim((string) $request->query('search', ''));
        $status = trim((string) $request->query('status', ''));

        if (CommunityEventStatus::tryFrom($status) === null) {
            $status = '';
        }

        return [
            'search' => $search,
            'status' => $status,
        ];
    }

    /**
     * @return array{id: string, title: string, starts_at: string, location: string, capacity: int|null, rsvp_count: int, thumbnail_url: string|null, status: string}
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
            'status' => $event->status->value,
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
