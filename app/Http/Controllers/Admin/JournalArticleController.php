<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreJournalArticleRequest;
use App\Http\Requests\Admin\TranslateJournalRequest;
use App\Http\Requests\Admin\UpdateJournalArticleRequest;
use App\Http\Requests\Admin\UpdateJournalTranslationsRequest;
use App\Models\JournalArticle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class JournalArticleController extends Controller
{
    /** @var list<int> */
    public const PER_PAGE_OPTIONS = [10, 15, 25, 50, 100];

    public const PER_PAGE_DEFAULT = 15;

    /** @var list<string> */
    private const TRANSLATION_COLUMNS = [
        'title',
        'excerpt',
        'body',
        'category',
        'date_label',
    ];

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

        $articles = JournalArticle::query()
            ->when($filters['search'] !== '', function (Builder $query) use ($filters): void {
                $search = $filters['search'];
                $query->where(function (Builder $inner) use ($search): void {
                    $inner->where('title', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%")
                        ->orWhere('author', 'like', "%{$search}%");
                });
            })
            ->when($filters['category'] !== '', function (Builder $query) use ($filters): void {
                $query->where('category', $filters['category']);
            })
            ->when($filters['publication'] === 'published', function (Builder $query): void {
                $query->whereNotNull('published_at')
                    ->where('published_at', '<=', now());
            })
            ->when($filters['publication'] === 'draft', function (Builder $query): void {
                $query->where(function (Builder $inner): void {
                    $inner->whereNull('published_at')
                        ->orWhere('published_at', '>', now());
                });
            })
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate($filters['per_page'])
            ->withQueryString()
            ->through(fn (JournalArticle $article): array => $this->summary($article));

        return Inertia::render('admin/journal/index', [
            'articles' => $articles,
            'filters' => $filters,
            'perPageOptions' => self::PER_PAGE_OPTIONS,
            'categories' => JournalArticle::query()
                ->whereNotNull('category')
                ->where('category', '!=', '')
                ->orderBy('category')
                ->distinct()
                ->pluck('category')
                ->map(fn (string $category): array => [
                    'value' => $category,
                    'label' => $category,
                ])
                ->values()
                ->all(),
        ]);
    }

    public function create(Request $request, string $locale): Response
    {
        return Inertia::render('admin/journal/create');
    }

    public function store(StoreJournalArticleRequest $request, string $locale): RedirectResponse
    {
        $data = $request->safe()->except(['image', 'remove_image']);
        $data['slug'] = $data['slug'] ?: Str::slug($data['title']);

        if ($request->hasFile('image')) {
            $data['image_path'] = $this->storeImage($request->file('image'));
        }

        $article = JournalArticle::query()->create($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Journalartikel aangemaakt.')]);

        return redirect()->route('admin.journal.show', [
            'locale' => $locale,
            'article' => $article->id,
        ]);
    }

    public function show(Request $request, string $locale, JournalArticle $article): Response
    {
        $article->loadMissing('translations');

        return Inertia::render('admin/journal/show', [
            'article' => $this->details($article, $locale),
            'locales' => $this->translationLocales(),
            'translations' => $this->translationBundle($article),
            'translationStatus' => $this->translationStatus($article),
        ]);
    }

    public function edit(Request $request, string $locale, JournalArticle $article): Response
    {
        return Inertia::render('admin/journal/edit', [
            'article' => [
                'id' => (string) $article->id,
                'slug' => $article->slug,
                'title' => $article->title,
                'excerpt' => $article->excerpt,
                'body' => $article->body,
                'cover_path' => $article->cover_path,
                'image_url' => $article->resolvedImageUrl(),
                'category' => $article->category,
                'author' => $article->author,
                'date_label' => $article->date_label,
                'published_at' => $article->published_at?->format('Y-m-d\TH:i'),
                'sort_order' => $article->sort_order,
            ],
        ]);
    }

    public function update(UpdateJournalArticleRequest $request, string $locale, JournalArticle $article): RedirectResponse
    {
        $data = $request->safe()->except(['image', 'remove_image']);
        $data['slug'] = $data['slug'] ?: Str::slug($data['title']);

        if ($request->hasFile('image')) {
            $this->deleteImage($article);
            $data['image_path'] = $this->storeImage($request->file('image'));
        } elseif ($request->boolean('remove_image')) {
            $this->deleteImage($article);
            $data['image_path'] = null;
        }

        $article->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Journalartikel bijgewerkt.')]);

        return redirect()->route('admin.journal.show', [
            'locale' => $locale,
            'article' => $article->id,
        ]);
    }

    public function updateTranslations(
        UpdateJournalTranslationsRequest $request,
        string $locale,
        JournalArticle $article,
    ): RedirectResponse {
        $data = $request->validated();

        foreach ($this->translationLocales() as $targetLocale) {
            foreach (self::TRANSLATION_COLUMNS as $column) {
                $article->translations()->updateOrCreate(
                    [
                        'locale' => $targetLocale,
                        'column' => $column,
                    ],
                    [
                        'value' => $data[$targetLocale][$column],
                        'source_hash' => $article->translationSourceHash($column),
                    ],
                );
            }
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Vertalingen opgeslagen.')]);

        return redirect()->route('admin.journal.show', [
            'locale' => $locale,
            'article' => $article->id,
        ]);
    }

    public function translate(
        TranslateJournalRequest $request,
        string $locale,
        JournalArticle $article,
    ): RedirectResponse {
        $targetLocale = $request->validated('target_locale');

        if (filled($targetLocale)) {
            $article->translations()
                ->where('locale', $targetLocale)
                ->whereIn('column', self::TRANSLATION_COLUMNS)
                ->delete();

            $article->dispatchDeepLTranslation([$targetLocale]);
        } else {
            $article->translations()
                ->whereIn('column', self::TRANSLATION_COLUMNS)
                ->delete();

            $article->dispatchDeepLTranslation();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Vertalingen worden bijgewerkt.')]);

        return redirect()->route('admin.journal.show', [
            'locale' => $locale,
            'article' => $article->id,
        ]);
    }

    public function destroy(Request $request, string $locale, JournalArticle $article): RedirectResponse
    {
        $this->deleteImage($article);
        $article->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Journalartikel verwijderd.')]);

        return redirect()->route('admin.journal.index', ['locale' => $locale]);
    }

    /**
     * @return array{id: string, slug: string, title: string, category: string, author: string, sort_order: int, published_at: string|null, is_published: bool, image_url: string|null}
     */
    private function summary(JournalArticle $article): array
    {
        return [
            'id' => (string) $article->id,
            'slug' => $article->slug,
            'title' => $article->translated('title'),
            'category' => $article->translated('category'),
            'author' => (string) ($article->author ?? ''),
            'sort_order' => $article->sort_order,
            'published_at' => $article->published_at?->toIso8601String(),
            'is_published' => $article->published_at !== null && $article->published_at->lte(now()),
            'image_url' => $article->resolvedImageUrl(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function details(JournalArticle $article, string $locale): array
    {
        return [
            ...$this->summary($article),
            'excerpt' => $article->translated('excerpt', $locale),
            'body' => $article->translated('body', $locale),
            'cover_path' => $article->cover_path,
            'category' => $article->translated('category', $locale),
            'author' => $article->author,
            'date_label' => $article->translated('date_label', $locale),
            'public_url' => route('maison.journal.show', [
                'locale' => $locale,
                'slug' => $article->slug,
            ]),
        ];
    }

    private function storeImage(UploadedFile $file): string
    {
        return $file->store('journal', 'public');
    }

    private function deleteImage(JournalArticle $article): void
    {
        if ($article->image_path === null || $article->image_path === '') {
            return;
        }

        Storage::disk('public')->delete($article->image_path);
    }

    /**
     * @return array{search: string, category: string, publication: string, per_page: int}
     */
    private function filters(Request $request): array
    {
        $search = trim((string) $request->query('search', ''));
        $category = trim((string) $request->query('category', ''));
        $publication = trim((string) $request->query('publication', ''));

        if (! in_array($publication, ['published', 'draft'], true)) {
            $publication = '';
        }

        return [
            'search' => $search,
            'category' => $category,
            'publication' => $publication,
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
     * @return array<string, array{title: string, excerpt: string, body: string, category: string, date_label: string}>
     */
    private function translationBundle(JournalArticle $article): array
    {
        $bundle = [];

        foreach ($this->translationLocales() as $targetLocale) {
            $bundle[$targetLocale] = [
                'title' => $article->translated('title', $targetLocale),
                'excerpt' => $article->translated('excerpt', $targetLocale),
                'body' => $article->translated('body', $targetLocale),
                'category' => $article->translated('category', $targetLocale),
                'date_label' => $article->translated('date_label', $targetLocale),
            ];
        }

        return $bundle;
    }

    /**
     * @return array<string, array{title: bool, excerpt: bool, body: bool, category: bool, date_label: bool}>
     */
    private function translationStatus(JournalArticle $article): array
    {
        $status = [];

        foreach ($this->translationLocales() as $targetLocale) {
            $status[$targetLocale] = [
                'title' => $article->translations->contains(
                    fn ($translation): bool => $translation->locale === $targetLocale
                        && $translation->column === 'title',
                ),
                'excerpt' => $article->translations->contains(
                    fn ($translation): bool => $translation->locale === $targetLocale
                        && $translation->column === 'excerpt',
                ),
                'body' => $article->translations->contains(
                    fn ($translation): bool => $translation->locale === $targetLocale
                        && $translation->column === 'body',
                ),
                'category' => $article->translations->contains(
                    fn ($translation): bool => $translation->locale === $targetLocale
                        && $translation->column === 'category',
                ),
                'date_label' => $article->translations->contains(
                    fn ($translation): bool => $translation->locale === $targetLocale
                        && $translation->column === 'date_label',
                ),
            ];
        }

        return $status;
    }
}
