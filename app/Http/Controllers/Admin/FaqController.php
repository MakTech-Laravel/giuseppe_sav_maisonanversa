<?php

namespace App\Http\Controllers\Admin;

use App\Enums\FaqContext;
use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class FaqController extends Controller
{
    public function index(Request $request, string $locale): Response
    {
        $faqs = Faq::query()
            ->orderBy('context')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return Inertia::render('admin/faqs/index', [
            'faqs' => $faqs->map(fn (Faq $faq): array => [
                'id' => (string) $faq->id,
                'context' => $faq->context->value,
                'question' => $faq->translated('question'),
                'answer' => $faq->translated('answer'),
                'sort_order' => $faq->sort_order,
                'is_published' => $faq->is_published,
            ]),
        ]);
    }

    public function create(Request $request, string $locale): Response
    {
        return Inertia::render('admin/faqs/create');
    }

    public function store(Request $request, string $locale): RedirectResponse
    {
        $faq = Faq::query()->create($this->validated($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('FAQ aangemaakt.')]);

        return redirect()->route('admin.faqs.edit', [
            'locale' => $locale,
            'faq' => $faq->id,
        ]);
    }

    public function edit(Request $request, string $locale, Faq $faq): Response
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
        ]);
    }

    public function update(Request $request, string $locale, Faq $faq): RedirectResponse
    {
        $faq->update($this->validated($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('FAQ bijgewerkt.')]);

        return redirect()->route('admin.faqs.edit', [
            'locale' => $locale,
            'faq' => $faq->id,
        ]);
    }

    public function destroy(Request $request, string $locale, Faq $faq): RedirectResponse
    {
        $faq->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('FAQ verwijderd.')]);

        return redirect()->route('admin.faqs.index', ['locale' => $locale]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'context' => ['required', Rule::enum(FaqContext::class)],
            'question' => ['required', 'string'],
            'answer' => ['required', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_published' => ['required', 'boolean'],
        ]);
    }
}
