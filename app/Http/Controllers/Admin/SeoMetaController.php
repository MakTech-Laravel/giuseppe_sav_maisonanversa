<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SeoMeta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SeoMetaController extends Controller
{
    public function index(Request $request, string $locale): Response
    {
        $rows = SeoMeta::query()
            ->orderBy('page_key')
            ->get();

        return Inertia::render('admin/seo-metas/index', [
            'rows' => $rows->map(fn (SeoMeta $row): array => [
                'id' => (string) $row->id,
                'page_key' => $row->page_key,
                'title' => $row->translated('title'),
                'description' => $row->translated('description'),
            ]),
        ]);
    }

    public function edit(Request $request, string $locale, SeoMeta $seoMeta): Response
    {
        return Inertia::render('admin/seo-metas/edit', [
            'row' => [
                'id' => (string) $seoMeta->id,
                'page_key' => $seoMeta->page_key,
                'title' => $seoMeta->title,
                'description' => $seoMeta->description,
            ],
        ]);
    }

    public function update(Request $request, string $locale, SeoMeta $seoMeta): RedirectResponse
    {
        $seoMeta->update($request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
        ]));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('SEO-meta bijgewerkt.')]);

        return redirect()->route('admin.seo-metas.edit', [
            'locale' => $locale,
            'seoMeta' => $seoMeta->id,
        ]);
    }
}
