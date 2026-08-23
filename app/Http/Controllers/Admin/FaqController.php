<?php

namespace App\Http\Controllers\Admin;

use App\Enums\FaqContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreFaqRequest;
use App\Http\Requests\Admin\TranslateFaqRequest;
use App\Http\Requests\Admin\UpdateFaqRequest;
use App\Http\Requests\Admin\UpdateFaqTranslationsRequest;
use App\Models\Faq;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FaqController extends Controller
{
    /** @var list<int> */
    public const PER_PAGE_OPTIONS = [10, 15, 25, 50, 100];

    public const PER_PAGE_DEFAULT = 15;

    /** @var list<string> */
    private const TRANSLATION_COLUMNS = ['question', 'answer'];

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

        $faqs = Faq::query()
            ->when($filters['search'] !== '', function (Builder $query) use ($filters): void {
                $search = $filters['search'];
                $query->where(function (Builder $inner) use ($search): void {
                    $inner->where('question', 'like', "%{$search}%")
                        ->orWhere('answer', 'like', "%{$search}%");
                });
            })
            ->when($filters['context'] !== '', function (Builder $query) use ($filters): void {
                $query->where('context', $filters['context']);
            })
            ->when($filters['status'] === 'published', function (Builder $query): void {
                $query->where('is_published', true);
            })
            ->when($filters['status'] === 'draft', function (Builder $query): void {
                $query->where('is_published', false);
            })
            ->orderBy('context')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate($filters['per_page'])
            ->withQueryString()
            ->through(fn (Faq $faq): array => [
                'id' => (string) $faq->id,
                'context' => $faq->context->value,
                'question' => $faq->translated('question'),
                'answer' => $faq->translated('answer'),
                'sort_order' => $faq->sort_order,
                'is_published' => $faq->is_published,
            ]);

        return Inertia::render('admin/faqs/index', [
            'faqs' => $faqs,
            'filters' => $filters,
            'perPageOptions' => self::PER_PAGE_OPTIONS,
            'contexts' => collect(FaqContext::cases())
                ->map(fn (FaqContext $context): array => [
                    'value' => $context->value,
                    'label' => $context->value,
                ])
                ->values()
                ->all(),
        ]);
    }

    public function create(string $locale): Response
    {
        return Inertia::render('admin/faqs/create', [
            'contexts' => $this->contextOptions(),
        ]);
    }

    public function store(StoreFaqRequest $request, string $locale): RedirectResponse
    {
        $faq = Faq::query()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('FAQ aangemaakt.')]);

        return redirect()->route('admin.faqs.show', [
            'locale' => $locale,
            'faq' => $faq->id,
        ]);
    }

    public function show(string $locale, Faq $faq): Response
    {
        $faq->loadMissing('translations');

        return Inertia::render('admin/faqs/show', [
            'faq' => [
                'id' => (string) $faq->id,
                'context' => $faq->context->value,
                'question' => $faq->translated('question'),
                'answer' => $faq->translated('answer'),
                'sort_order' => $faq->sort_order,
                'is_published' => $faq->is_published,
            ],
            'locales' => config('maison.locales'),
            'translations' => $this->translationBundle($faq),
            'translationStatus' => $this->translationStatus($faq),
        ]);
    }

    public function edit(string $locale, Faq $faq): Response
    {
        return Inertia::render('admin/faqs/edit', [
            'faq' => [
                'id' => (string) $faq->id,
                'context' => $faq->context->value,
                'question' => $faq->question,
                'answer' => $faq->answer,
                'sort_order' => $faq->sort_order,
                'is_published' => $faq->is_published,
            ],
            'contexts' => $this->contextOptions(),
        ]);
    }

    public function update(UpdateFaqRequest $request, string $locale, Faq $faq): RedirectResponse
    {
        $faq->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('FAQ bijgewerkt.')]);

        return redirect()->route('admin.faqs.show', [
            'locale' => $locale,
            'faq' => $faq->id,
        ]);
    }

    public function updateTranslations(
        UpdateFaqTranslationsRequest $request,
        string $locale,
        Faq $faq,
    ): RedirectResponse {
        $data = $request->validated();

        foreach ($this->translationLocales() as $targetLocale) {
            foreach (self::TRANSLATION_COLUMNS as $column) {
                $faq->translations()->updateOrCreate(
                    [
                        'locale' => $targetLocale,
                        'column' => $column,
                    ],
                    [
                        'value' => $data[$targetLocale][$column],
                        'source_hash' => $faq->translationSourceHash($column),
                    ],
                );
            }
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Vertalingen opgeslagen.')]);

        return redirect()->route('admin.faqs.show', [
            'locale' => $locale,
            'faq' => $faq->id,
        ]);
    }

    public function translate(TranslateFaqRequest $request, string $locale, Faq $faq): RedirectResponse
    {
        $targetLocale = $request->validated('target_locale');

        if (filled($targetLocale)) {
            $faq->translations()
                ->where('locale', $targetLocale)
                ->whereIn('column', self::TRANSLATION_COLUMNS)
                ->delete();

            $faq->dispatchDeepLTranslation([$targetLocale]);
        } else {
            $faq->translations()
                ->whereIn('column', self::TRANSLATION_COLUMNS)
                ->delete();

            $faq->dispatchDeepLTranslation();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Vertalingen worden bijgewerkt.')]);

        return redirect()->route('admin.faqs.show', [
            'locale' => $locale,
            'faq' => $faq->id,
        ]);
    }

    public function destroy(string $locale, Faq $faq): RedirectResponse
    {
        $faq->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('FAQ verwijderd.')]);

        return redirect()->route('admin.faqs.index', ['locale' => $locale]);
    }

    /**
     * @return array{search: string, context: string, status: string, per_page: int}
     */
    private function filters(Request $request): array
    {
        $search = trim((string) $request->query('search', ''));
        $context = trim((string) $request->query('context', ''));
        $status = trim((string) $request->query('status', ''));

        if (FaqContext::tryFrom($context) === null) {
            $context = '';
        }

        if (! in_array($status, ['published', 'draft'], true)) {
            $status = '';
        }

        return [
            'search' => $search,
            'context' => $context,
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
     * @return array<string, array{question: string, answer: string}>
     */
    private function translationBundle(Faq $faq): array
    {
        $bundle = [];

        foreach ($this->translationLocales() as $targetLocale) {
            $bundle[$targetLocale] = [
                'question' => $faq->translated('question', $targetLocale),
                'answer' => $faq->translated('answer', $targetLocale),
            ];
        }

        return $bundle;
    }

    /**
     * @return array<string, array{question: bool, answer: bool}>
     */
    private function translationStatus(Faq $faq): array
    {
        $status = [];

        foreach ($this->translationLocales() as $targetLocale) {
            $status[$targetLocale] = [
                'question' => $faq->translations->contains(
                    fn ($translation): bool => $translation->locale === $targetLocale
                        && $translation->column === 'question',
                ),
                'answer' => $faq->translations->contains(
                    fn ($translation): bool => $translation->locale === $targetLocale
                        && $translation->column === 'answer',
                ),
            ];
        }

        return $status;
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function contextOptions(): array
    {
        return [
            ['value' => FaqContext::Product->value, 'label' => 'product'],
            ['value' => FaqContext::Contact->value, 'label' => 'contact'],
        ];
    }
}
