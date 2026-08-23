<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CommunityEventStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCommunityEventRequest;
use App\Http\Requests\Admin\TranslateCommunityEventColumnRequest;
use App\Http\Requests\Admin\UpdateCommunityEventRequest;
use App\Http\Requests\Admin\UpdateCommunityEventStatusRequest;
use App\Http\Requests\Admin\UpdateCommunityEventTranslationsRequest;
use App\Jobs\TranslateModelJob;
use App\Models\CommunityEvent;
use App\Services\Translation\DeepLTranslator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class CommunityEventController extends Controller
{
    private const PER_PAGE = 15;

    /** @var list<string> */
    private const TRANSLATION_TARGET_LOCALES = ['en', 'fr'];

    /** @var list<string> */
    private const TRANSLATION_COLUMNS = ['title', 'description', 'location'];

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
        $event->loadCount('rsvps')->load(['rsvps.user', 'translations']);

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
            'locales' => config('maison.locales'),
            'defaultLocale' => config('maison.default_locale'),
            'translations' => $this->translationBundle($event),
            'translationStatus' => $this->translationStatus($event),
        ]);
    }

    public function edit(Request $request, string $locale, CommunityEvent $event): Response
    {
        $event->loadMissing('translations');

        return Inertia::render('admin/events/edit', [
            'event' => [
                'id' => (string) $event->id,
                'starts_at' => $event->starts_at?->format('Y-m-d\TH:i'),
                'capacity' => $event->capacity,
                'thumbnail_url' => $event->thumbnailUrl(),
            ],
            'source' => [
                'title' => $event->title,
                'description' => $event->description ?? '',
                'location' => $event->location ?? '',
            ],
            'defaultLocale' => config('maison.default_locale'),
            'translations' => $this->translationBundle($event),
        ]);
    }

    public function update(UpdateCommunityEventRequest $request, string $locale, CommunityEvent $event): RedirectResponse
    {
        $defaultLocale = (string) config('maison.default_locale');
        $data = $request->safe()->except(['thumbnail', 'remove_thumbnail']);

        if ($request->hasFile('thumbnail')) {
            $this->deleteThumbnail($event);
            $data['thumbnail'] = $this->storeThumbnail($request->file('thumbnail'));
        } elseif ($request->boolean('remove_thumbnail')) {
            $this->deleteThumbnail($event);
            $data['thumbnail'] = null;
        }

        $sharedData = [
            'starts_at' => $data['starts_at'],
            'capacity' => $data['capacity'] ?? null,
        ];

        if (array_key_exists('thumbnail', $data)) {
            $sharedData['thumbnail'] = $data['thumbnail'];
        }

        $toast = ['type' => 'success', 'message' => __('Evenement bijgewerkt.')];

        if ($locale === $defaultLocale) {
            $sourceChanged = $event->title !== $data['title']
                || (string) ($event->description ?? '') !== (string) ($data['description'] ?? '')
                || (string) ($event->location ?? '') !== (string) ($data['location'] ?? '');

            $event->update([
                ...$sharedData,
                'title' => $data['title'],
                'description' => $data['description'] ?? '',
                'location' => $data['location'] ?? '',
            ]);

            if ($sourceChanged) {
                $event->translations()->delete();

                if (app(DeepLTranslator::class)->configured()) {
                    $event->dispatchDeepLTranslation();
                } else {
                    $toast = [
                        'type' => 'warning',
                        'message' => __('Evenement opgeslagen. DeepL is niet geconfigureerd — vertalingen worden niet automatisch gegenereerd.'),
                    ];
                }
            }
        } else {
            $event->update($sharedData);

            if (in_array($locale, self::TRANSLATION_TARGET_LOCALES, true)) {
                $this->replaceLocaleTranslations($event, $locale, [
                    'title' => $data['title'],
                    'description' => (string) ($data['description'] ?? ''),
                    'location' => (string) ($data['location'] ?? ''),
                ]);
            }
        }

        Inertia::flash('toast', $toast);

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

    public function updateTranslations(
        UpdateCommunityEventTranslationsRequest $request,
        string $locale,
        CommunityEvent $event,
    ): RedirectResponse {
        $data = $request->validated();
        $targetLocale = $data['target_locale'];

        $this->replaceLocaleTranslations($event, $targetLocale, [
            'title' => $data['title'],
            'description' => $data['description'],
            'location' => $data['location'],
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Vertalingen opgeslagen.')]);

        return redirect()->route('admin.events.show', [
            'locale' => $locale,
            'event' => $event->id,
        ]);
    }

    public function translateColumn(
        TranslateCommunityEventColumnRequest $request,
        string $locale,
        CommunityEvent $event,
        DeepLTranslator $translator,
    ): RedirectResponse {
        if (! $translator->configured()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('DeepL is niet geconfigureerd. Voeg DEEPL_API_KEY toe aan .env.'),
            ]);

            return back();
        }

        $targetLocale = $request->validated('target_locale');
        $column = $request->validated('column');

        $event->translations()
            ->where('locale', $targetLocale)
            ->where('column', $column)
            ->delete();

        TranslateModelJob::dispatchSync(
            CommunityEvent::class,
            (int) $event->id,
            $targetLocale,
            $column,
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Vertaling bijgewerkt.')]);

        return back();
    }

    public function translate(string $locale, CommunityEvent $event, DeepLTranslator $translator): RedirectResponse
    {
        if (! $translator->configured()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('DeepL is niet geconfigureerd. Voeg DEEPL_API_KEY toe aan .env.'),
            ]);

            return back();
        }

        $event->dispatchDeepLTranslation();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Vertalingen worden bijgewerkt.')]);

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

    /**
     * @return array<string, array{title: string, description: string, location: string}>
     */
    private function translationBundle(CommunityEvent $event): array
    {
        $bundle = [];

        foreach (config('maison.locales') as $targetLocale) {
            $bundle[$targetLocale] = [
                'title' => $event->translated('title', $targetLocale),
                'description' => $event->translated('description', $targetLocale),
                'location' => $event->translated('location', $targetLocale),
            ];
        }

        return $bundle;
    }

    /**
     * @return array<string, array{title: bool, description: bool, location: bool}>
     */
    private function translationStatus(CommunityEvent $event): array
    {
        $status = [];

        foreach (self::TRANSLATION_TARGET_LOCALES as $targetLocale) {
            $status[$targetLocale] = [
                'title' => $event->translations->contains(
                    fn ($translation): bool => $translation->locale === $targetLocale
                        && $translation->column === 'title',
                ),
                'description' => $event->translations->contains(
                    fn ($translation): bool => $translation->locale === $targetLocale
                        && $translation->column === 'description',
                ),
                'location' => $event->translations->contains(
                    fn ($translation): bool => $translation->locale === $targetLocale
                        && $translation->column === 'location',
                ),
            ];
        }

        return $status;
    }

    /**
     * @param  array{title: string, description: string, location: string}  $values
     */
    private function replaceLocaleTranslations(
        CommunityEvent $event,
        string $targetLocale,
        array $values,
    ): void {
        $event->translations()
            ->where('locale', $targetLocale)
            ->whereIn('column', self::TRANSLATION_COLUMNS)
            ->delete();

        foreach (self::TRANSLATION_COLUMNS as $column) {
            $event->translations()->create([
                'locale' => $targetLocale,
                'column' => $column,
                'value' => $values[$column],
                'source_hash' => $event->translationSourceHash($column),
            ]);
        }
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
