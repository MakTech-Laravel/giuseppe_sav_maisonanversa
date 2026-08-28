<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TranslateSiteSettingRequest;
use App\Http\Requests\Admin\UpdateSiteSettingRequest;
use App\Http\Requests\Admin\UpdateSiteSettingTranslationsRequest;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SiteSettingController extends Controller
{
    /** @var list<string> */
    private const TRANSLATION_COLUMNS = ['announcement_text'];

    /**
     * @return list<string>
     */
    private function translationLocales(): array
    {
        return config('maison.locales');
    }

    public function edit(Request $request, string $locale): Response
    {
        $settings = SiteSetting::current();
        $settings->loadMissing('translations');

        return Inertia::render('admin/site-settings/edit', [
            'settings' => [
                'phone' => $settings->phone,
                'whatsapp' => $settings->whatsapp,
                'email_hello' => $settings->email_hello,
                'email_press' => $settings->email_press,
                'instagram_url' => $settings->instagram_url,
                'boutique_lat' => (string) $settings->boutique_lat,
                'boutique_lng' => (string) $settings->boutique_lng,
                'announcement_text' => $settings->announcement_text,
            ],
            'locales' => $this->translationLocales(),
            'translations' => $this->translationBundle($settings),
            'translationStatus' => $this->translationStatus($settings),
        ]);
    }

    public function update(UpdateSiteSettingRequest $request, string $locale): RedirectResponse
    {
        SiteSetting::current()->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Site-instellingen opgeslagen.')]);

        return back();
    }

    public function updateTranslations(
        UpdateSiteSettingTranslationsRequest $request,
        string $locale,
    ): RedirectResponse {
        $data = $request->validated();
        $sourceLocale = (string) config('maison.default_locale');
        $settings = SiteSetting::current();

        SiteSetting::withoutEvents(function () use ($settings, $data, $sourceLocale): void {
            $source = $data[$sourceLocale]['announcement_text'] ?? null;

            $settings->update([
                'announcement_text' => filled($source) ? $source : null,
            ]);
        });

        $settings->refresh();

        foreach ($this->translationLocales() as $targetLocale) {
            if ($targetLocale === $sourceLocale) {
                continue;
            }

            $value = $data[$targetLocale]['announcement_text'] ?? '';

            $settings->translations()->updateOrCreate(
                [
                    'locale' => $targetLocale,
                    'column' => 'announcement_text',
                ],
                [
                    'value' => $value,
                    'source_hash' => $settings->translationSourceHash('announcement_text'),
                ],
            );
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Vertalingen opgeslagen.')]);

        return redirect()->route('admin.site-settings.edit', [
            'locale' => $locale,
        ]);
    }

    public function translate(TranslateSiteSettingRequest $request, string $locale): RedirectResponse
    {
        $settings = SiteSetting::current();
        $targetLocale = $request->validated('target_locale');

        if (filled($targetLocale)) {
            $settings->translations()
                ->where('locale', $targetLocale)
                ->whereIn('column', self::TRANSLATION_COLUMNS)
                ->delete();

            $settings->dispatchDeepLTranslation([$targetLocale]);
        } else {
            $settings->translations()
                ->whereIn('column', self::TRANSLATION_COLUMNS)
                ->delete();

            $settings->dispatchDeepLTranslation();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Vertalingen worden bijgewerkt.')]);

        return redirect()->route('admin.site-settings.edit', [
            'locale' => $locale,
        ]);
    }

    /**
     * @return array<string, array{announcement_text: string}>
     */
    private function translationBundle(SiteSetting $settings): array
    {
        $bundle = [];

        foreach ($this->translationLocales() as $targetLocale) {
            $bundle[$targetLocale] = [
                'announcement_text' => $settings->translated('announcement_text', $targetLocale),
            ];
        }

        return $bundle;
    }

    /**
     * @return array<string, array{announcement_text: bool}>
     */
    private function translationStatus(SiteSetting $settings): array
    {
        $sourceLocale = (string) config('maison.default_locale');
        $sourceFilled = filled($settings->announcement_text);
        $status = [];

        foreach ($this->translationLocales() as $targetLocale) {
            $status[$targetLocale] = [
                'announcement_text' => ! $sourceFilled
                    || $targetLocale === $sourceLocale
                    || $settings->translations->contains(
                        fn ($translation): bool => $translation->locale === $targetLocale
                            && $translation->column === 'announcement_text',
                    ),
            ];
        }

        return $status;
    }
}
