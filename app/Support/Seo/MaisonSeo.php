<?php

namespace App\Support\Seo;

use App\Enums\FaqContext;
use App\Models\Faq;
use App\Models\JournalArticle;
use App\Models\Product;
use App\Models\SeoMeta;
use App\Services\Edition\EditionInventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/**
 * Canonical titles, descriptions, robots, hreflang, and JSON-LD for a request.
 *
 * Shared with Inertia so the React head and the Blade fallback cannot drift.
 *
 * @phpstan-type HreflangLink array{hreflang: string, href: string}
 * @phpstan-type SeoDocument array{
 *     title: string,
 *     description: string,
 *     canonical: string,
 *     robots: string|null,
 *     ogType: string,
 *     ogImage: string,
 *     ogImageWidth: int|null,
 *     ogImageHeight: int|null,
 *     siteName: string,
 *     locale: string,
 *     localeAlternates: list<string>,
 *     hreflang: list<HreflangLink>,
 *     jsonLd: list<array<string, mixed>>,
 *     articlePublishedTime: string|null,
 *     articleModifiedTime: string|null
 * }
 */
final class MaisonSeo
{
    /**
     * @var array<string, array{title: string, description: string}>
     */
    private const PAGES = [
        'home' => [
            'title' => 'Maison Anversa — Europees erfgoedhuis voor sport en lifestyle',
            'description' => 'Een Europees erfgoedhuis, geworteld in Antwerpen. Heritage No.001 — beperkt tot 100 stuks. Elk genummerd. De Founding Edition wordt nooit herhaald.',
        ],
        'house' => [
            'title' => 'Het Huis — Maison Anversa',
            'description' => 'Ontdek het Huis van Maison Anversa — acht kamers, één erfgoedverhaal, geworteld in Antwerpen.',
        ],
        'products' => [
            'title' => 'Producten — Maison Anversa',
            'description' => 'Ontdek de collectie van Maison Anversa — Heritage No.001 en de volgende hoofdstukken van het huis.',
        ],
        'story' => [
            'title' => 'Ons Verhaal — Maison Anversa',
            'description' => 'Het verhaal van Maison Anversa — van Antwerpen naar een Europees erfgoedhuis voor sport en lifestyle.',
        ],
        'circle' => [
            'title' => 'Founding Circle — Maison Anversa',
            'description' => 'Word lid van de Founding Circle — exclusieve toegang, events en het erfgoed van Maison Anversa.',
        ],
        'dressing' => [
            'title' => 'Kleedkamer — Maison Anversa',
            'description' => 'De Kleedkamer van Maison Anversa — curated sportswear en lifestyle, met dezelfde zorg als ons erfgoed.',
        ],
        'journal' => [
            'title' => 'Journal — Maison Anversa',
            'description' => 'Het Journal van Maison Anversa — verhalen over ambacht, Antwerpen en het erfgoed van sport.',
        ],
        'community' => [
            'title' => 'Community — Maison Anversa',
            'description' => 'De Community van Maison Anversa — sessies, evenementen en een netwerk van gelijkgestemde leden.',
        ],
        'corner' => [
            'title' => 'Club Corner — Maison Anversa',
            'description' => 'Club Corner — exclusieve voordelen en early access voor leden van Maison Anversa.',
        ],
        'contact' => [
            'title' => 'Contact — Maison Anversa',
            'description' => 'Neem contact op met Maison Anversa — vragen over Heritage No.001, bestellingen of pers.',
        ],
        'privacy' => [
            'title' => 'Privacybeleid — Maison Anversa',
            'description' => 'Privacybeleid van Maison Anversa — hoe wij uw gegevens verwerken en beschermen.',
        ],
        'terms' => [
            'title' => 'Algemene voorwaarden — Maison Anversa',
            'description' => 'Algemene voorwaarden van Maison Anversa — bestellingen, levering en garantie.',
        ],
        'shipping' => [
            'title' => 'Verzending & Retour — Maison Anversa',
            'description' => 'Verzending en retour bij Maison Anversa — leveringstijden, kosten en retourbeleid.',
        ],
        'care' => [
            'title' => 'Zorg & Garantie — Maison Anversa',
            'description' => 'Zorg en garantie voor Heritage No.001 — onderhoud, reparatie en klantenservice van Maison Anversa.',
        ],
    ];

    private const OG_LOCALE = [
        'nl' => 'nl_BE',
        'en' => 'en_GB',
        'fr' => 'fr_BE',
    ];

    /**
     * @return array<string, array{title: string, description: string}>
     */
    public static function defaults(): array
    {
        return self::PAGES;
    }

    /**
     * @return SeoDocument
     */
    public static function document(?Request $request = null): array
    {
        $request ??= request();

        if ($request->attributes->get('maison_seo_error') === true) {
            $locale = self::locale($request);
            $origin = self::origin();

            return [
                'title' => __('Pagina niet gevonden'),
                'description' => __('Deze pagina bestaat niet. Keer terug naar Maison Anversa.'),
                'canonical' => $origin.$request->getPathInfo(),
                'robots' => 'noindex, nofollow',
                'ogType' => 'website',
                'ogImage' => self::absoluteUrl($origin, (string) config('maison.seo.image')),
                'ogImageWidth' => self::ogImageWidth(),
                'ogImageHeight' => self::ogImageHeight(),
                'siteName' => 'Maison Anversa',
                'locale' => self::OG_LOCALE[$locale] ?? 'nl_BE',
                'localeAlternates' => [],
                'hreflang' => [],
                'jsonLd' => [],
                'articlePublishedTime' => null,
                'articleModifiedTime' => null,
            ];
        }
        $locale = self::locale($request);
        $origin = self::origin();
        $routeName = Route::currentRouteName();
        $page = self::pageKey($routeName);
        $article = self::journalArticle($request, $routeName);
        $product = self::productForSeo($request, $routeName);
        $indexable = self::isIndexable($routeName, $article, $product);
        $canonical = self::canonical($request, $routeName, $locale, $origin, $article, $product);
        $copy = self::copy($page, $article, $routeName, $product);
        $hreflang = self::hreflang($routeName, $article, $product);
        $ogImage = self::absoluteUrl($origin, (string) config('maison.seo.image'));

        return [
            'title' => $copy['title'],
            'description' => $copy['description'],
            'canonical' => $canonical,
            'robots' => $indexable ? null : 'noindex, nofollow',
            'ogType' => $article !== null ? 'article' : 'website',
            'ogImage' => $ogImage,
            'ogImageWidth' => self::ogImageWidth(),
            'ogImageHeight' => self::ogImageHeight(),
            'siteName' => 'Maison Anversa',
            'locale' => self::OG_LOCALE[$locale] ?? 'nl_BE',
            'localeAlternates' => self::localeAlternates($locale),
            'hreflang' => $hreflang,
            'jsonLd' => self::jsonLd(
                $page,
                $canonical,
                $copy['title'],
                $copy['description'],
                $ogImage,
                $origin,
                $article,
                $locale,
                $product,
            ),
            'articlePublishedTime' => $article?->published_at?->toIso8601String(),
            'articleModifiedTime' => $article?->updated_at?->toIso8601String(),
        ];
    }

    public static function locale(Request $request): string
    {
        $locales = config('maison.locales');
        $segment = $request->segment(1);

        if (is_string($segment) && in_array($segment, $locales, true)) {
            return $segment;
        }

        $locale = app()->getLocale();

        return in_array($locale, $locales, true)
            ? $locale
            : (string) config('maison.default_locale');
    }

    private static function origin(): string
    {
        return rtrim((string) config('app.url'), '/');
    }

    private static function pageKey(?string $routeName): ?string
    {
        if ($routeName === 'maison.journal.show') {
            return 'journal';
        }

        if ($routeName === 'maison.products.show') {
            return 'products';
        }

        if (is_string($routeName) && str_starts_with($routeName, 'maison.')) {
            $key = substr($routeName, strlen('maison.'));

            return array_key_exists($key, self::PAGES) ? $key : null;
        }

        return null;
    }

    private static function isIndexable(?string $routeName, ?JournalArticle $article, ?Product $product): bool
    {
        if ($routeName === 'maison.journal.show') {
            return $article !== null;
        }

        if ($routeName === 'maison.products.show') {
            return $product !== null;
        }

        $page = self::pageKey($routeName);

        return $page !== null && array_key_exists($page, self::PAGES);
    }

    private static function journalArticle(Request $request, ?string $routeName): ?JournalArticle
    {
        if ($routeName !== 'maison.journal.show') {
            return null;
        }

        $slug = $request->route('slug');

        if (! is_string($slug) || $slug === '') {
            return null;
        }

        return JournalArticle::query()
            ->published()
            ->where('slug', $slug)
            ->first();
    }

    private static function productForSeo(Request $request, ?string $routeName): ?Product
    {
        if ($routeName !== 'maison.products.show') {
            return null;
        }

        $product = $request->route('product');

        if ($product instanceof Product) {
            return $product->is_published ? $product : null;
        }

        if (! is_string($product) || $product === '') {
            return null;
        }

        return Product::query()
            ->where('slug', $product)
            ->where('is_published', true)
            ->first();
    }

    /**
     * @return array{title: string, description: string}
     */
    private static function copy(?string $page, ?JournalArticle $article, ?string $routeName, ?Product $product = null): array
    {
        if ($article !== null) {
            $title = $article->translated('title') ?: (string) $article->title;

            return [
                'title' => $title.' — Maison Anversa',
                'description' => $article->translated('excerpt') ?: (string) $article->excerpt,
            ];
        }

        if ($product !== null) {
            $title = $product->translated('name') ?: (string) $product->name;

            return [
                'title' => $title.' — Maison Anversa',
                'description' => $product->translated('description') ?: (string) $product->description,
            ];
        }

        if ($page !== null && isset(self::PAGES[$page])) {
            $meta = SeoMeta::query()
                ->where('page_key', $page)
                ->first();

            if ($meta !== null) {
                return [
                    'title' => $meta->translated('title'),
                    'description' => $meta->translated('description'),
                ];
            }

            return [
                'title' => __(self::PAGES[$page]['title']),
                'description' => __(self::PAGES[$page]['description']),
            ];
        }

        return match ($routeName) {
            'maison.checkout.success' => [
                'title' => __('Betaling bevestigd — Maison Anversa'),
                'description' => __('Uw Founding Edition-reservering is ontvangen.'),
            ],
            'maison.checkout.cancel' => [
                'title' => __('Betaling geannuleerd — Maison Anversa'),
                'description' => __('Uw betaling is geannuleerd. Er is niets in rekening gebracht.'),
            ],
            'maison.verify' => [
                'title' => __('Authenticiteit').' — Maison Anversa',
                'description' => __('Geverifieerd Founding Edition-stuk'),
            ],
            'maison.heritage-letter.unsubscribe' => [
                'title' => __('Uitgeschreven').' — Maison Anversa',
                'description' => __('U ontvangt geen Heritage Letter meer. U kunt zich altijd opnieuw inschrijven.'),
            ],
            default => [
                'title' => 'Maison Anversa',
                'description' => __('Een Europees erfgoedhuis geworteld in Antwerpen. Wij bouwen producten voor mensen die begrijpen dat de mooiste dingen in het leven tijd kosten.'),
            ],
        };
    }

    private static function canonical(
        Request $request,
        ?string $routeName,
        string $locale,
        string $origin,
        ?JournalArticle $article,
        ?Product $product = null,
    ): string {
        if ($routeName === 'maison.journal.show' && $article !== null) {
            return route('maison.journal.show', [
                'locale' => $locale,
                'slug' => $article->slug,
            ]);
        }

        if ($routeName === 'maison.products.show' && $product !== null) {
            return route('maison.products.show', [
                'locale' => $locale,
                'product' => $product->slug,
            ]);
        }

        if (is_string($routeName) && $routeName !== '' && Route::has($routeName)) {
            $parameters = $request->route()?->parameters() ?? [];

            if (isset($parameters['locale'])) {
                $parameters['locale'] = $locale;
            }

            try {
                return route($routeName, $parameters);
            } catch (\Throwable) {
                // Fall through to the request URL.
            }
        }

        return $origin.$request->getPathInfo();
    }

    /**
     * @return list<HreflangLink>
     */
    private static function hreflang(?string $routeName, ?JournalArticle $article, ?Product $product = null): array
    {
        if (! is_string($routeName) || ! Route::has($routeName)) {
            return [];
        }

        $route = Route::getRoutes()->getByName($routeName);

        if ($route === null || ! str_contains($route->uri(), '{locale}')) {
            return [];
        }

        $locales = config('maison.locales');
        $defaultLocale = config('maison.default_locale');
        $parameters = [];

        if ($article !== null) {
            $parameters['slug'] = $article->slug;
        }

        if ($product !== null) {
            $parameters['product'] = $product->slug;
        }

        $links = [];

        foreach ($locales as $locale) {
            try {
                $links[] = [
                    'hreflang' => $locale,
                    'href' => route($routeName, ['locale' => $locale, ...$parameters]),
                ];
            } catch (\Throwable) {
                return [];
            }
        }

        $links[] = [
            'hreflang' => 'x-default',
            'href' => route($routeName, ['locale' => $defaultLocale, ...$parameters]),
        ];

        return $links;
    }

    /**
     * @return list<string>
     */
    private static function localeAlternates(string $locale): array
    {
        $alternates = [];

        foreach (config('maison.locales') as $candidate) {
            if ($candidate === $locale) {
                continue;
            }

            $alternates[] = self::OG_LOCALE[$candidate] ?? $candidate;
        }

        return $alternates;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function jsonLd(
        ?string $page,
        string $canonical,
        string $title,
        string $description,
        string $ogImage,
        string $origin,
        ?JournalArticle $article,
        string $locale,
        ?Product $product = null,
    ): array {
        if ($page === null && $article === null) {
            return [];
        }

        $organizationId = $origin.'/#organization';
        $websiteId = $origin.'/#website';

        $graph = [
            [
                '@type' => 'Organization',
                '@id' => $organizationId,
                'name' => 'Maison Anversa',
                'url' => $origin,
                'logo' => $ogImage,
            ],
            [
                '@type' => 'WebSite',
                '@id' => $websiteId,
                'name' => 'Maison Anversa',
                'url' => $origin,
                'publisher' => ['@id' => $organizationId],
                'inLanguage' => $locale,
            ],
        ];

        if ($article !== null) {
            $graph[] = [
                '@type' => 'Article',
                'headline' => $title,
                'description' => $description,
                'image' => $ogImage,
                'datePublished' => $article->published_at?->toIso8601String(),
                'dateModified' => $article->updated_at?->toIso8601String(),
                'author' => [
                    '@type' => 'Person',
                    'name' => (string) ($article->author ?: 'Maison Anversa'),
                ],
                'publisher' => ['@id' => $organizationId],
                'mainEntityOfPage' => $canonical,
                'inLanguage' => $locale,
            ];
            $graph[] = [
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    [
                        '@type' => 'ListItem',
                        'position' => 1,
                        'name' => 'Maison Anversa',
                        'item' => route('maison.home', ['locale' => $locale]),
                    ],
                    [
                        '@type' => 'ListItem',
                        'position' => 2,
                        'name' => __('Journal — Maison Anversa'),
                        'item' => route('maison.journal', ['locale' => $locale]),
                    ],
                    [
                        '@type' => 'ListItem',
                        'position' => 3,
                        'name' => $title,
                        'item' => $canonical,
                    ],
                ],
            ];
        } else {
            $graph[] = [
                '@type' => 'WebPage',
                'name' => $title,
                'description' => $description,
                'url' => $canonical,
                'isPartOf' => ['@id' => $websiteId],
                'inLanguage' => $locale,
            ];
        }

        if ($page === 'products' && $product !== null) {
            $graph[] = self::productSchema($canonical, $description, $ogImage, $organizationId, $product);
            $productFaqs = self::publishedFaqItems(FaqContext::Product);

            if ($productFaqs !== []) {
                $graph[] = self::faqSchema($productFaqs);
            }
        }

        if ($page === 'contact') {
            $contactFaqs = self::publishedFaqItems(FaqContext::Contact);

            if ($contactFaqs !== []) {
                $graph[] = self::faqSchema($contactFaqs);
            }
        }

        return [[
            '@context' => 'https://schema.org',
            '@graph' => $graph,
        ]];
    }

    /**
     * @return array<string, mixed>
     */
    private static function productSchema(
        string $canonical,
        string $description,
        string $ogImage,
        string $organizationId,
        Product $product,
    ): array {
        $checkout = Product::checkoutShare($product);
        $snapshot = app(EditionInventory::class)->snapshot($product);
        $availability = ($snapshot['soldOut'] ?? false)
            ? 'https://schema.org/SoldOut'
            : 'https://schema.org/PreOrder';

        $offer = [
            '@type' => 'Offer',
            'url' => $canonical,
            'availability' => $availability,
            'priceCurrency' => strtoupper((string) ($checkout['currency'] ?: 'eur')),
        ];

        if ($checkout['amount'] !== '') {
            $offer['price'] = $checkout['amount'];
        }

        return [
            '@type' => 'Product',
            'name' => $checkout['productName'] !== '' ? $checkout['productName'] : $product->name,
            'description' => $description,
            'image' => $ogImage,
            'brand' => ['@id' => $organizationId],
            'offers' => $offer,
        ];
    }

    /**
     * @return list<array{question: string, answer: string}>
     */
    private static function publishedFaqItems(FaqContext $context): array
    {
        return Faq::publishedFor($context)
            ->map(fn (Faq $faq): array => [
                'question' => $faq->translated('question'),
                'answer' => $faq->translated('answer'),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  list<array{question: string, answer: string}>  $items
     * @return array<string, mixed>
     */
    private static function faqSchema(array $items): array
    {
        return [
            '@type' => 'FAQPage',
            'mainEntity' => array_map(fn (array $item): array => [
                '@type' => 'Question',
                'name' => $item['question'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $item['answer'],
                ],
            ], $items),
        ];
    }

    private static function ogImageWidth(): ?int
    {
        $width = config('maison.seo.image_width');

        return is_int($width) ? $width : null;
    }

    private static function ogImageHeight(): ?int
    {
        $height = config('maison.seo.image_height');

        return is_int($height) ? $height : null;
    }

    private static function absoluteUrl(string $origin, string $path): string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return $origin.'/'.ltrim($path, '/');
    }
}
