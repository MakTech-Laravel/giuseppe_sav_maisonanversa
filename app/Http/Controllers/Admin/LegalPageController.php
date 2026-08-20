<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LegalPage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LegalPageController extends Controller
{
    public function index(Request $request, string $locale): Response
    {
        $pages = LegalPage::query()
            ->whereIn('slug', ['privacy', 'terms', 'shipping', 'care'])
            ->orderByRaw("CASE slug WHEN 'privacy' THEN 1 WHEN 'terms' THEN 2 WHEN 'shipping' THEN 3 WHEN 'care' THEN 4 ELSE 5 END")
            ->get();

        return Inertia::render('admin/legal-pages/index', [
            'pages' => $pages->map(fn (LegalPage $page): array => [
                'id' => (string) $page->id,
                'slug' => $page->slug,
                'is_published' => $page->is_published,
            ]),
        ]);
    }

    public function edit(Request $request, string $locale, LegalPage $legalPage): Response
    {
        return Inertia::render('admin/legal-pages/edit', [
            'page' => [
                'id' => (string) $legalPage->id,
                'slug' => $legalPage->slug,
                'body' => $legalPage->body,
                'is_published' => $legalPage->is_published,
            ],
        ]);
    }

    public function update(Request $request, string $locale, LegalPage $legalPage): RedirectResponse
    {
        $legalPage->update($request->validate([
            'body' => ['required', 'string'],
            'is_published' => ['required', 'boolean'],
        ]));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Juridische pagina bijgewerkt.')]);

        return redirect()->route('admin.legal-pages.edit', [
            'locale' => $locale,
            'legalPage' => $legalPage->id,
        ]);
    }
}
