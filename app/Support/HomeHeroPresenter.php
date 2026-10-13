<?php

namespace App\Support;

use App\Enums\HomeHeroAction;
use App\Models\HomeHero;
use App\Models\Product;
use Illuminate\Support\Facades\Storage;

class HomeHeroPresenter
{
    /**
     * Maison pages a hero button may open. Keys are stored targets.
     *
     * @var array<string, string>
     */
    public const PAGES = [
        'home' => 'maison.home',
        'house' => 'maison.house',
        'products' => 'maison.products',
        'story' => 'maison.story',
        'circle' => 'maison.circle',
        'register' => 'maison.register',
        'dressing' => 'maison.dressing',
        'journal' => 'maison.journal',
        'community' => 'maison.community',
        'corner' => 'maison.corner',
        'contact' => 'maison.contact',
        'privacy' => 'maison.privacy',
        'terms' => 'maison.terms',
        'shipping' => 'maison.shipping',
        'care' => 'maison.care',
    ];

    /**
     * Dutch labels for the admin page select. The storefront never sees these.
     *
     * @var array<string, string>
     */
    public const PAGE_LABELS = [
        'home' => 'Home',
        'house' => 'Het Huis',
        'products' => 'Producten',
        'story' => 'Verhaal',
        'circle' => 'Founding Circle',
        'register' => 'Founding Circle registreren',
        'dressing' => 'Kleedkamer',
        'journal' => 'Journal',
        'community' => 'Gemeenschap',
        'corner' => 'Club Corner',
        'contact' => 'Contact',
        'privacy' => 'Privacy',
        'terms' => 'Voorwaarden',
        'shipping' => 'Verzending',
        'care' => 'Verzorging',
    ];

    /**
     * @return array{
     *     imageUrl: string|null,
     *     eyebrow: string,
     *     title: string,
     *     titleAccent: string,
     *     tagline: string,
     *     showCounter: bool,
     *     counterLines: array{0: string, 1: string},
     *     buttons: list<array{label: string, kind: string, href: string|null}>
     * }
     */
    public function toStorefront(HomeHero $hero, string $locale): array
    {
        return [
            'imageUrl' => $this->imageUrl($hero),
            'eyebrow' => $hero->translated('eyebrow', $locale),
            'title' => (string) $hero->title,
            'titleAccent' => (string) $hero->title_accent,
            // Brand line uses the source column as-is on every locale (never DeepL).
            'tagline' => (string) $hero->tagline,
            'showCounter' => $hero->show_counter,
            'counterLines' => [
                $hero->translated('counter_line_one', $locale),
                $hero->translated('counter_line_two', $locale),
            ],
            'buttons' => $this->buttons($hero, $locale),
        ];
    }

    public function imageUrl(HomeHero $hero): ?string
    {
        if ($hero->image_path === null || $hero->image_path === '') {
            return null;
        }

        return Storage::disk('public')->url($hero->image_path);
    }

    /**
     * @return list<array{label: string, kind: string, href: string|null}>
     */
    private function buttons(HomeHero $hero, string $locale): array
    {
        $buttons = [];

        foreach (HomeHero::SLOTS as $slot) {
            $action = $hero->{$slot.'_action'};

            if (! $action instanceof HomeHeroAction || $action === HomeHeroAction::Hidden) {
                continue;
            }

            $label = trim($hero->translated($slot.'_label', $locale));

            if ($label === '') {
                continue;
            }

            $resolved = $this->resolve($action, (string) ($hero->{$slot.'_target'} ?? ''), $locale);

            if ($resolved === null) {
                continue;
            }

            $buttons[] = [
                'label' => $label,
                ...$resolved,
            ];
        }

        return $buttons;
    }

    /**
     * @return array{kind: string, href: string|null}|null
     */
    private function resolve(HomeHeroAction $action, string $target, string $locale): ?array
    {
        return match ($action) {
            HomeHeroAction::FoundingProduct => [
                'kind' => 'link',
                'href' => route('maison.products.show', [
                    'locale' => $locale,
                    'product' => Product::FOUNDING_SLUG,
                ]),
            ],
            HomeHeroAction::FoundingCircle => $this->pageLink('circle', $locale),
            HomeHeroAction::RegisterFoundingCircle => $this->pageLink('register', $locale),
            HomeHeroAction::Community => $this->pageLink('community', $locale),
            HomeHeroAction::ClubCorner => $this->pageLink('corner', $locale),
            HomeHeroAction::Journal => $this->pageLink('journal', $locale),
            HomeHeroAction::MaisonPage => $this->pageLink($target, $locale),
            HomeHeroAction::Newsletter => [
                'kind' => 'newsletter',
                'href' => null,
            ],
            HomeHeroAction::External => $this->externalLink($target),
            HomeHeroAction::Hidden => null,
        };
    }

    /**
     * @return array{kind: string, href: string}|null
     */
    private function pageLink(string $target, string $locale): ?array
    {
        $routeName = self::PAGES[$target] ?? null;

        if ($routeName === null) {
            return null;
        }

        return [
            'kind' => 'link',
            'href' => route($routeName, ['locale' => $locale]),
        ];
    }

    /**
     * @return array{kind: string, href: string}|null
     */
    private function externalLink(string $target): ?array
    {
        if (! str_starts_with($target, 'https://') || filter_var($target, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        return [
            'kind' => 'link',
            'href' => $target,
        ];
    }
}
