<?php

namespace App\Http\Controllers\Admin;

use App\Enums\HomeHeroAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TranslateHomeHeroRequest;
use App\Http\Requests\Admin\UpdateHomeHeroRequest;
use App\Http\Requests\Admin\UpdateHomeHeroTranslationsRequest;
use App\Models\HomeHero;
use App\Support\HomeHeroPresenter;
use App\Support\Imagery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class HomeHeroController extends Controller
{
    public function edit(Request $request, string $locale, HomeHeroPresenter $presenter): Response
    {
        $hero = HomeHero::current()->load('translations');

        return Inertia::render('admin/home-hero/edit', [
            'hero' => $this->formPayload($hero, $presenter),
            'actions' => $this->actionOptions(),
            'pages' => $this->pageOptions(),
            'fallbackImageUrl' => Imagery::assetUrl('hero-mansion'),
            'locales' => array_values(config('maison.locales')),
            'translations' => $this->translationBundle($hero),
            'translationStatus' => $this->translationStatus($hero),
        ]);
    }

    public function update(UpdateHomeHeroRequest $request, string $locale): RedirectResponse
    {
        $hero = HomeHero::current();
        $data = $request->safe()->except(['image', 'remove_image']);

        foreach (HomeHero::SLOTS as $slot) {
            $action = HomeHeroAction::from((string) $data["{$slot}_action"]);

            if (! $action->needsTarget()) {
                $data["{$slot}_target"] = null;
            }
        }

        if ($request->hasFile('image')) {
            $this->deleteImage($hero);
            $data['image_path'] = $this->storeImage($request->file('image'));
        } elseif ($request->boolean('remove_image')) {
            $this->deleteImage($hero);
            $data['image_path'] = null;
        }

        $hero->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Home hero opgeslagen.')]);

        return back();
    }

    public function updateTranslations(
        UpdateHomeHeroTranslationsRequest $request,
        string $locale,
    ): RedirectResponse {
        $hero = HomeHero::current();
        $data = $request->validated();
        $sourceLocale = (string) config('maison.default_locale');

        HomeHero::withoutEvents(function () use ($hero, $data, $sourceLocale): void {
            $source = [];

            foreach (HomeHero::TRANSLATION_COLUMNS as $column) {
                $source[$column] = $data[$sourceLocale][$column];
            }

            $hero->update($source);
        });

        $hero->refresh();

        foreach ($this->translationLocales() as $targetLocale) {
            if ($targetLocale === $sourceLocale) {
                continue;
            }

            foreach (HomeHero::TRANSLATION_COLUMNS as $column) {
                $hero->translations()->updateOrCreate(
                    [
                        'locale' => $targetLocale,
                        'column' => $column,
                    ],
                    [
                        'value' => $data[$targetLocale][$column],
                        'source_hash' => $hero->translationSourceHash($column),
                    ],
                );
            }
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Vertalingen opgeslagen.')]);

        return back();
    }

    public function translate(TranslateHomeHeroRequest $request, string $locale): RedirectResponse
    {
        $hero = HomeHero::current();
        $targetLocale = $request->validated('target_locale');

        if (filled($targetLocale)) {
            $hero->translations()
                ->where('locale', $targetLocale)
                ->whereIn('column', HomeHero::TRANSLATION_COLUMNS)
                ->delete();

            $hero->dispatchDeepLTranslation([$targetLocale]);
        } else {
            $hero->translations()
                ->whereIn('column', HomeHero::TRANSLATION_COLUMNS)
                ->delete();

            $hero->dispatchDeepLTranslation();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Vertalingen worden bijgewerkt.')]);

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function formPayload(HomeHero $hero, HomeHeroPresenter $presenter): array
    {
        $payload = [
            'image_url' => $presenter->imageUrl($hero),
            'show_counter' => $hero->show_counter,
        ];

        foreach ([
            'eyebrow',
            'title',
            'title_accent',
            'tagline',
            'counter_line_one',
            'counter_line_two',
        ] as $column) {
            $payload[$column] = (string) $hero->getAttribute($column);
        }

        foreach (HomeHero::SLOTS as $slot) {
            $action = $hero->{$slot.'_action'};
            $payload["{$slot}_label"] = (string) $hero->getAttribute("{$slot}_label");
            $payload["{$slot}_action"] = $action instanceof HomeHeroAction ? $action->value : (string) $action;
            $payload["{$slot}_target"] = (string) ($hero->{$slot.'_target'} ?? '');
        }

        return $payload;
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function actionOptions(): array
    {
        return array_map(
            fn (HomeHeroAction $action): array => [
                'value' => $action->value,
                'label' => $action->label(),
            ],
            HomeHeroAction::cases(),
        );
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function pageOptions(): array
    {
        $pages = [];

        foreach (HomeHeroPresenter::PAGE_LABELS as $value => $label) {
            $pages[] = [
                'value' => $value,
                'label' => $label,
            ];
        }

        return $pages;
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
    private function translationBundle(HomeHero $hero): array
    {
        $bundle = [];

        foreach ($this->translationLocales() as $targetLocale) {
            $bundle[$targetLocale] = [];

            foreach (HomeHero::TRANSLATION_COLUMNS as $column) {
                $bundle[$targetLocale][$column] = $hero->translated($column, $targetLocale);
            }
        }

        return $bundle;
    }

    /**
     * @return array<string, array<string, bool>>
     */
    private function translationStatus(HomeHero $hero): array
    {
        $sourceLocale = (string) config('maison.default_locale');
        $status = [];

        foreach ($this->translationLocales() as $targetLocale) {
            $status[$targetLocale] = [];

            foreach (HomeHero::TRANSLATION_COLUMNS as $column) {
                $status[$targetLocale][$column] = $targetLocale === $sourceLocale
                    || $hero->translations->contains(
                        fn ($translation): bool => $translation->locale === $targetLocale
                            && $translation->column === $column,
                    );
            }
        }

        return $status;
    }

    private function storeImage(UploadedFile $file): string
    {
        return $file->store('home-heroes', 'public');
    }

    private function deleteImage(HomeHero $hero): void
    {
        if ($hero->image_path === null || $hero->image_path === '') {
            return;
        }

        Storage::disk('public')->delete($hero->image_path);
    }
}
