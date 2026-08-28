<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TranslateLegalPageRequest;
use App\Http\Requests\Admin\UpdateLegalPageRequest;
use App\Http\Requests\Admin\UpdateLegalPageTranslationsRequest;
use App\Models\LegalPage;
use App\Support\Html\LegalHtml;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LegalPageController extends Controller
{
    /** @var list<string> */
    private const TRANSLATION_COLUMNS = ['body'];

    /**
     * @return list<string>
     */
    private function translationLocales(): array
    {
        return config('maison.locales');
    }

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

    public function show(string $locale, LegalPage $legalPage): Response
    {
        $legalPage->loadMissing('translations');

        return Inertia::render('admin/legal-pages/show', [
            'page' => [
                'id' => (string) $legalPage->id,
                'slug' => $legalPage->slug,
                'is_published' => $legalPage->is_published,
            ],
            'locales' => $this->translationLocales(),
            'translations' => $this->translationBundle($legalPage),
            'translationStatus' => $this->translationStatus($legalPage),
        ]);
    }

    public function edit(Request $request, string $locale, LegalPage $legalPage): Response
    {
        return Inertia::render('admin/legal-pages/edit', [
            'page' => [
                'id' => (string) $legalPage->id,
                'slug' => $legalPage->slug,
                'body' => LegalHtml::forEditor($legalPage->body),
                'is_published' => $legalPage->is_published,
            ],
        ]);
    }

    public function update(UpdateLegalPageRequest $request, string $locale, LegalPage $legalPage): RedirectResponse
    {
        $legalPage->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Juridische pagina bijgewerkt.')]);

        return redirect()->route('admin.legal-pages.show', [
            'locale' => $locale,
            'legalPage' => $legalPage->id,
        ]);
    }

    public function updateTranslations(
        UpdateLegalPageTranslationsRequest $request,
        string $locale,
        LegalPage $legalPage,
    ): RedirectResponse {
        $data = $request->validated();
        $sourceLocale = (string) config('maison.default_locale');

        LegalPage::withoutEvents(function () use ($legalPage, $data, $sourceLocale): void {
            $legalPage->update([
                'body' => $data[$sourceLocale]['body'],
            ]);
        });

        $legalPage->refresh();

        foreach ($this->translationLocales() as $targetLocale) {
            if ($targetLocale === $sourceLocale) {
                continue;
            }

            $legalPage->translations()->updateOrCreate(
                [
                    'locale' => $targetLocale,
                    'column' => 'body',
                ],
                [
                    'value' => $data[$targetLocale]['body'],
                    'source_hash' => $legalPage->translationSourceHash('body'),
                ],
            );
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Vertalingen opgeslagen.')]);

        return redirect()->route('admin.legal-pages.show', [
            'locale' => $locale,
            'legalPage' => $legalPage->id,
        ]);
    }

    public function translate(TranslateLegalPageRequest $request, string $locale, LegalPage $legalPage): RedirectResponse
    {
        $targetLocale = $request->validated('target_locale');

        if (filled($targetLocale)) {
            $legalPage->translations()
                ->where('locale', $targetLocale)
                ->whereIn('column', self::TRANSLATION_COLUMNS)
                ->delete();

            $legalPage->dispatchDeepLTranslation([$targetLocale]);
        } else {
            $legalPage->translations()
                ->whereIn('column', self::TRANSLATION_COLUMNS)
                ->delete();

            $legalPage->dispatchDeepLTranslation();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Vertalingen worden bijgewerkt.')]);

        return redirect()->route('admin.legal-pages.show', [
            'locale' => $locale,
            'legalPage' => $legalPage->id,
        ]);
    }

    /**
     * @return array<string, array{body: string}>
     */
    private function translationBundle(LegalPage $legalPage): array
    {
        $bundle = [];

        foreach ($this->translationLocales() as $targetLocale) {
            $bundle[$targetLocale] = [
                'body' => LegalHtml::forEditor($legalPage->translated('body', $targetLocale)),
            ];
        }

        return $bundle;
    }

    /**
     * @return array<string, array{body: bool}>
     */
    private function translationStatus(LegalPage $legalPage): array
    {
        $sourceLocale = (string) config('maison.default_locale');
        $status = [];

        foreach ($this->translationLocales() as $targetLocale) {
            $status[$targetLocale] = [
                'body' => $targetLocale === $sourceLocale || $legalPage->translations->contains(
                    fn ($translation): bool => $translation->locale === $targetLocale
                        && $translation->column === 'body',
                ),
            ];
        }

        return $status;
    }
}
