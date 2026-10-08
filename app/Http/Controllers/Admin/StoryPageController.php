<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TranslateStoryPageRequest;
use App\Http\Requests\Admin\UpdateStoryPageRequest;
use App\Http\Requests\Admin\UpdateStoryPageTranslationsRequest;
use App\Models\StoryPage;
use App\Support\Imagery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class StoryPageController extends Controller
{
    public function edit(Request $request, string $locale): Response
    {
        $page = StoryPage::current()->load('translations');

        return Inertia::render('admin/story-page/edit', [
            'page' => $this->formPayload($page),
            'locales' => array_values(config('maison.locales')),
            'translations' => $this->translationBundle($page),
            'translationStatus' => $this->translationStatus($page),
            'fallbacks' => $this->fallbackImages(),
        ]);
    }

    public function update(UpdateStoryPageRequest $request, string $locale): RedirectResponse
    {
        $page = StoryPage::current();
        $data = $request->safe()->except($this->imageInputKeys());
        $sourceLocale = (string) config('maison.default_locale');

        if ($locale === $sourceLocale) {
            $page->update($data);
        } else {
            $structural = [];

            foreach ([...StoryPage::VISIBLE_COLUMNS, ...StoryPage::UNTRANSLATED_COLUMNS] as $column) {
                $structural[$column] = $data[$column];
            }

            $page->update($structural);
            $page->refresh();

            foreach (StoryPage::TRANSLATION_COLUMNS as $column) {
                $page->translations()->updateOrCreate(
                    [
                        'locale' => $locale,
                        'column' => $column,
                    ],
                    [
                        'value' => $data[$column],
                        'source_hash' => $page->translationSourceHash($column),
                    ],
                );
            }
        }

        $this->applyImages($request, $page->fresh() ?? $page);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Verhaal opgeslagen.')]);

        return back();
    }

    public function updateTranslations(
        UpdateStoryPageTranslationsRequest $request,
        string $locale,
    ): RedirectResponse {
        $page = StoryPage::current();
        $data = $request->validated();
        $sourceLocale = (string) config('maison.default_locale');

        StoryPage::withoutEvents(function () use ($page, $data, $sourceLocale): void {
            $source = [];

            foreach (StoryPage::TRANSLATION_COLUMNS as $column) {
                $source[$column] = $data[$sourceLocale][$column];
            }

            $page->update($source);
        });

        $page->refresh();

        foreach ($this->translationLocales() as $targetLocale) {
            if ($targetLocale === $sourceLocale) {
                continue;
            }

            foreach (StoryPage::TRANSLATION_COLUMNS as $column) {
                $page->translations()->updateOrCreate(
                    [
                        'locale' => $targetLocale,
                        'column' => $column,
                    ],
                    [
                        'value' => $data[$targetLocale][$column],
                        'source_hash' => $page->translationSourceHash($column),
                    ],
                );
            }
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Vertalingen opgeslagen.')]);

        return back();
    }

    public function translate(TranslateStoryPageRequest $request, string $locale): RedirectResponse
    {
        $page = StoryPage::current();
        $targetLocale = $request->validated('target_locale');

        if (filled($targetLocale)) {
            $page->translations()
                ->where('locale', $targetLocale)
                ->whereIn('column', StoryPage::TRANSLATION_COLUMNS)
                ->delete();

            $page->dispatchDeepLTranslation([$targetLocale]);
        } else {
            $page->translations()
                ->whereIn('column', StoryPage::TRANSLATION_COLUMNS)
                ->delete();

            $page->dispatchDeepLTranslation();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Vertalingen worden bijgewerkt.')]);

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function formPayload(StoryPage $page): array
    {
        $payload = [];

        foreach (StoryPage::VISIBLE_COLUMNS as $column) {
            $payload[$column] = (bool) $page->getAttribute($column);
        }

        foreach ([...StoryPage::UNTRANSLATED_COLUMNS, ...StoryPage::TRANSLATION_COLUMNS] as $column) {
            $payload[$column] = (string) $page->getAttribute($column);
        }

        foreach (StoryPage::IMAGE_SLOTS as $slot => $column) {
            $payload["{$slot}_image_url"] = $this->imageUrl($page->getAttribute($column));
        }

        return $payload;
    }

    /**
     * @return list<string>
     */
    private function imageInputKeys(): array
    {
        $keys = [];

        foreach (array_keys(StoryPage::IMAGE_SLOTS) as $slot) {
            $keys[] = "{$slot}_image";
            $keys[] = "{$slot}_remove_image";
        }

        return $keys;
    }

    private function applyImages(Request $request, StoryPage $page): void
    {
        $changes = [];

        foreach (StoryPage::IMAGE_SLOTS as $slot => $column) {
            if ($request->hasFile("{$slot}_image")) {
                $this->deleteStoredImage($page->getAttribute($column));
                $file = $request->file("{$slot}_image");
                $changes[$column] = $file instanceof UploadedFile
                    ? $file->store('story-pages', 'public')
                    : null;
            } elseif ($request->boolean("{$slot}_remove_image")) {
                $this->deleteStoredImage($page->getAttribute($column));
                $changes[$column] = null;
            }
        }

        if ($changes !== []) {
            $page->update($changes);
        }
    }

    private function imageUrl(mixed $path): ?string
    {
        if (! is_string($path) || $path === '') {
            return null;
        }

        return Storage::disk('public')->url($path);
    }

    private function deleteStoredImage(mixed $path): void
    {
        if (! is_string($path) || $path === '') {
            return;
        }

        Storage::disk('public')->delete($path);
    }

    /**
     * @return array<string, string|null>
     */
    private function fallbackImages(): array
    {
        return [
            'hero' => null,
            'city' => Imagery::assetUrl('antwerp-cityscape'),
            'name' => Imagery::assetUrl('maison-facade-house'),
            'make_one' => Imagery::assetUrl('heritage-001-detail-gravure'),
            'make_two' => Imagery::assetUrl('heritage-001-front'),
            'make_three' => Imagery::assetUrl('heritage-001-lifestyle-court'),
            'founder' => null,
        ];
    }

    /**
     * @return list<string>
     */
    private function translationLocales(): array
    {
        return array_values(config('maison.locales'));
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function translationBundle(StoryPage $page): array
    {
        $bundle = [];

        foreach ($this->translationLocales() as $targetLocale) {
            $bundle[$targetLocale] = [];

            foreach (StoryPage::TRANSLATION_COLUMNS as $column) {
                $bundle[$targetLocale][$column] = $page->translated($column, $targetLocale);
            }
        }

        return $bundle;
    }

    /**
     * @return array<string, array<string, bool>>
     */
    private function translationStatus(StoryPage $page): array
    {
        $sourceLocale = (string) config('maison.default_locale');
        $status = [];

        foreach ($this->translationLocales() as $targetLocale) {
            $status[$targetLocale] = [];

            foreach (StoryPage::TRANSLATION_COLUMNS as $column) {
                $status[$targetLocale][$column] = $targetLocale === $sourceLocale
                    || $page->translations->contains(
                        fn ($translation): bool => $translation->locale === $targetLocale
                            && $translation->column === $column,
                    );
            }
        }

        return $status;
    }
}
