<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TranslateSeoMetaRequest;
use App\Http\Requests\Admin\UpdateSeoMetaRequest;
use App\Http\Requests\Admin\UpdateSeoMetaTranslationsRequest;
use App\Models\SeoMeta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SeoMetaController extends Controller
{
    /** @var list<string> */
    private const TRANSLATION_COLUMNS = ['title', 'description'];

    /**
     * @return list<string>
     */
    private function translationLocales(): array
    {
        return config('maison.locales');
    }

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
                'has_pending_translations' => $this->hasPendingTranslations($row),
            ]),
        ]);
    }

    public function show(string $locale, SeoMeta $seoMeta): Response
    {
        $seoMeta->loadMissing('translations');

        return Inertia::render('admin/seo-metas/show', [
            'row' => [
                'id' => (string) $seoMeta->id,
                'page_key' => $seoMeta->page_key,
                'title' => $seoMeta->translated('title'),
                'description' => $seoMeta->translated('description'),
            ],
            'locales' => $this->translationLocales(),
            'translations' => $this->translationBundle($seoMeta),
            'translationStatus' => $this->translationStatus($seoMeta),
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

    public function update(UpdateSeoMetaRequest $request, string $locale, SeoMeta $seoMeta): RedirectResponse
    {
        $seoMeta->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('SEO-meta bijgewerkt.')]);

        return redirect()->route('admin.seo-metas.show', [
            'locale' => $locale,
            'seoMeta' => $seoMeta->id,
        ]);
    }

    public function updateTranslations(
        UpdateSeoMetaTranslationsRequest $request,
        string $locale,
        SeoMeta $seoMeta,
    ): RedirectResponse {
        $data = $request->validated();
        $sourceLocale = (string) config('maison.default_locale');

        SeoMeta::withoutEvents(function () use ($seoMeta, $data, $sourceLocale): void {
            $seoMeta->update([
                'title' => $data[$sourceLocale]['title'],
                'description' => $data[$sourceLocale]['description'],
            ]);
        });

        $seoMeta->refresh();

        foreach ($this->translationLocales() as $targetLocale) {
            if ($targetLocale === $sourceLocale) {
                continue;
            }

            foreach (self::TRANSLATION_COLUMNS as $column) {
                $seoMeta->translations()->updateOrCreate(
                    [
                        'locale' => $targetLocale,
                        'column' => $column,
                    ],
                    [
                        'value' => $data[$targetLocale][$column],
                        'source_hash' => $seoMeta->translationSourceHash($column),
                    ],
                );
            }
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Vertalingen opgeslagen.')]);

        return redirect()->route('admin.seo-metas.show', [
            'locale' => $locale,
            'seoMeta' => $seoMeta->id,
        ]);
    }

    public function translate(TranslateSeoMetaRequest $request, string $locale, SeoMeta $seoMeta): RedirectResponse
    {
        $targetLocale = $request->validated('target_locale');

        if (filled($targetLocale)) {
            $seoMeta->translations()
                ->where('locale', $targetLocale)
                ->whereIn('column', self::TRANSLATION_COLUMNS)
                ->delete();

            $seoMeta->dispatchDeepLTranslation([$targetLocale]);
        } else {
            $seoMeta->translations()
                ->whereIn('column', self::TRANSLATION_COLUMNS)
                ->delete();

            $seoMeta->dispatchDeepLTranslation();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Vertalingen worden bijgewerkt.')]);

        return redirect()->route('admin.seo-metas.show', [
            'locale' => $locale,
            'seoMeta' => $seoMeta->id,
        ]);
    }

    /**
     * @return array<string, array{title: string, description: string}>
     */
    private function translationBundle(SeoMeta $seoMeta): array
    {
        $bundle = [];

        foreach ($this->translationLocales() as $targetLocale) {
            $bundle[$targetLocale] = [
                'title' => $seoMeta->translated('title', $targetLocale),
                'description' => $seoMeta->translated('description', $targetLocale),
            ];
        }

        return $bundle;
    }

    /**
     * @return array<string, array{title: bool, description: bool}>
     */
    private function translationStatus(SeoMeta $seoMeta): array
    {
        $sourceLocale = (string) config('maison.default_locale');
        $status = [];

        foreach ($this->translationLocales() as $targetLocale) {
            $status[$targetLocale] = [
                'title' => $targetLocale === $sourceLocale || $seoMeta->translations->contains(
                    fn ($translation): bool => $translation->locale === $targetLocale
                        && $translation->column === 'title',
                ),
                'description' => $targetLocale === $sourceLocale || $seoMeta->translations->contains(
                    fn ($translation): bool => $translation->locale === $targetLocale
                        && $translation->column === 'description',
                ),
            ];
        }

        return $status;
    }

    private function hasPendingTranslations(SeoMeta $seoMeta): bool
    {
        $status = $this->translationStatus($seoMeta);

        foreach ($status as $localeStatus) {
            if (! $localeStatus['title'] || ! $localeStatus['description']) {
                return true;
            }
        }

        return false;
    }
}
