<?php

namespace App\Http\Controllers\Admin;

use App\Enums\FaqContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreFaqRequest;
use App\Http\Requests\Admin\UpdateFaqRequest;
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

        return redirect()->route('admin.faqs.edit', [
            'locale' => $locale,
            'faq' => $faq->id,
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

        return redirect()->route('admin.faqs.edit', [
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
