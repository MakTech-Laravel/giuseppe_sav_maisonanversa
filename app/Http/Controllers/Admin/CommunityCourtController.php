<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCommunityCourtRequest;
use App\Http\Requests\Admin\TranslateCommunityCourtRequest;
use App\Http\Requests\Admin\UpdateCommunityCourtRequest;
use App\Http\Requests\Admin\UpdateCommunityCourtTranslationsRequest;
use App\Jobs\TranslateModelJob;
use App\Models\CommunityCourt;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CommunityCourtController extends Controller
{
    /** @var list<int> */
    public const PER_PAGE_OPTIONS = [10, 15, 25, 50, 100];

    public const PER_PAGE_DEFAULT = 15;

    /** @var list<string> */
    private const TRANSLATION_COLUMNS = ['title', 'body', 'location'];

    /**
     * @return list<string>
     */
    private function translationLocales(): array
    {
        return config('maison.locales');
    }

    public function index(Request $request, string $locale): Response
    {
        $filters = $this->filters($request);

        $courts = CommunityCourt::query()
            ->when($filters['search'] !== '', function (Builder $query) use ($filters): void {
                $search = $filters['search'];
                $query->where(function (Builder $inner) use ($search): void {
                    $inner->where('title', 'like', "%{$search}%")
                        ->orWhere('body', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%");
                });
            })
            ->when($filters['status'] === 'published', function (Builder $query): void {
                $query->where('is_published', true);
            })
            ->when($filters['status'] === 'draft', function (Builder $query): void {
                $query->where('is_published', false);
            })
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate($filters['per_page'])
            ->withQueryString()
            ->through(fn (CommunityCourt $court): array => $this->summary($court, $locale));

        return Inertia::render('admin/courts/index', [
            'courts' => $courts,
            'filters' => $filters,
            'perPageOptions' => self::PER_PAGE_OPTIONS,
        ]);
    }

    public function create(Request $request, string $locale): Response
    {
        return Inertia::render('admin/courts/create');
    }

    public function store(StoreCommunityCourtRequest $request, string $locale): RedirectResponse
    {
        $court = CommunityCourt::query()->create($request->validated());

        TranslateModelJob::dispatchSync(CommunityCourt::class, $court->id);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Club Corner aangemaakt.')]);

        return redirect()->route('admin.courts.show', [
            'locale' => $locale,
            'court' => $court->id,
        ]);
    }

    public function show(Request $request, string $locale, CommunityCourt $court): Response
    {
        $court->loadMissing('translations');

        return Inertia::render('admin/courts/show', [
            'court' => [
                ...$this->summary($court, $locale),
                'body' => $court->translated('body', $locale),
                'lat' => $court->lat !== null ? (float) $court->lat : null,
                'lng' => $court->lng !== null ? (float) $court->lng : null,
            ],
            'locales' => config('maison.locales'),
            'translations' => $this->translationBundle($court),
            'translationStatus' => $this->translationStatus($court),
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

        TranslateModelJob::dispatchSync(CommunityCourt::class, $court->id);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Club Corner bijgewerkt.')]);

        return redirect()->route('admin.courts.show', [
            'locale' => $locale,
            'court' => $court->id,
        ]);
    }

    public function updateTranslations(
        UpdateCommunityCourtTranslationsRequest $request,
        string $locale,
        CommunityCourt $court,
    ): RedirectResponse {
        $data = $request->validated();

        foreach ($this->translationLocales() as $targetLocale) {
            foreach (self::TRANSLATION_COLUMNS as $column) {
                $court->translations()->updateOrCreate(
                    [
                        'locale' => $targetLocale,
                        'column' => $column,
                    ],
                    [
                        'value' => $data[$targetLocale][$column] ?? '',
                        'source_hash' => $court->translationSourceHash($column),
                    ],
                );
            }
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Vertalingen opgeslagen.')]);

        return redirect()->route('admin.courts.show', [
            'locale' => $locale,
            'court' => $court->id,
        ]);
    }

    public function translate(
        TranslateCommunityCourtRequest $request,
        string $locale,
        CommunityCourt $court,
    ): RedirectResponse {
        $targetLocale = $request->validated('target_locale');

        if (filled($targetLocale)) {
            $court->translations()
                ->where('locale', $targetLocale)
                ->whereIn('column', self::TRANSLATION_COLUMNS)
                ->delete();

            TranslateModelJob::dispatchSync(CommunityCourt::class, $court->id, [$targetLocale]);
        } else {
            $court->translations()
                ->whereIn('column', self::TRANSLATION_COLUMNS)
                ->delete();

            TranslateModelJob::dispatchSync(CommunityCourt::class, $court->id);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Vertalingen worden bijgewerkt.')]);

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
     * @return array{search: string, status: string, per_page: int}
     */
    private function filters(Request $request): array
    {
        $search = trim((string) $request->query('search', ''));
        $status = trim((string) $request->query('status', ''));

        if (! in_array($status, ['published', 'draft'], true)) {
            $status = '';
        }

        return [
            'search' => $search,
            'status' => $status,
            'per_page' => $this->perPage($request),
        ];
    }

    private function perPage(Request $request): int
    {
        $value = $request->query('per_page');

        if (is_numeric($value)) {
            $perPage = (int) $value;

            if ($perPage > 0 && in_array($perPage, self::PER_PAGE_OPTIONS, true)) {
                return $perPage;
            }
        }

        return self::PER_PAGE_DEFAULT;
    }

    /**
     * @return array{id: string, title: string, location: string, sort_order: int, is_published: bool}
     */
    private function summary(CommunityCourt $court, string $locale): array
    {
        return [
            'id' => (string) $court->id,
            'title' => $court->translated('title', $locale),
            'location' => $court->translated('location', $locale),
            'sort_order' => $court->sort_order,
            'is_published' => $court->is_published,
        ];
    }

    /**
     * @return array<string, array{title: string, body: string, location: string}>
     */
    private function translationBundle(CommunityCourt $court): array
    {
        $bundle = [];

        foreach ($this->translationLocales() as $targetLocale) {
            $bundle[$targetLocale] = [
                'title' => $this->storedTranslation($court, 'title', $targetLocale),
                'body' => $this->storedTranslation($court, 'body', $targetLocale),
                'location' => $this->storedTranslation($court, 'location', $targetLocale),
            ];
        }

        return $bundle;
    }

    private function storedTranslation(CommunityCourt $court, string $column, string $locale): string
    {
        $row = $court->translations->first(
            fn ($translation): bool => $translation->locale === $locale
                && $translation->column === $column,
        );

        return (string) ($row?->value ?? '');
    }

    /**
     * @return array<string, array{title: bool, body: bool, location: bool}>
     */
    private function translationStatus(CommunityCourt $court): array
    {
        $status = [];

        foreach ($this->translationLocales() as $targetLocale) {
            $columnStatus = [];

            foreach (self::TRANSLATION_COLUMNS as $column) {
                $source = trim((string) ($court->getAttribute($column) ?? ''));

                if ($source === '') {
                    $columnStatus[$column] = true;

                    continue;
                }

                $columnStatus[$column] = $court->translations->contains(
                    fn ($translation): bool => $translation->locale === $targetLocale
                        && $translation->column === $column,
                );
            }

            $status[$targetLocale] = $columnStatus;
        }

        return $status;
    }
}
