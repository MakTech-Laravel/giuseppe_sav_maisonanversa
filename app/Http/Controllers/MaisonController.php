<?php

namespace App\Http\Controllers;

use App\Enums\CornerPipelineStatus;
use App\Enums\FaqContext;
use App\Models\Club;
use App\Models\CommunityEvent;
use App\Models\CommunityPost;
use App\Models\CommunitySession;
use App\Models\DressingItem;
use App\Models\Faq;
use App\Models\JournalArticle;
use App\Models\LegalPage;
use App\Models\Product;
use App\Models\User;
use App\Services\Edition\EditionInventory;
use App\Services\Inquiry\InquiryDeviceCookie;
use App\Support\CommunityFeed;
use App\Support\Html\LegalHtml;
use App\Support\Journal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The public Maison Anversa site. The Journal pages Eloquent articles via
 * {@see Journal}; community props load live sessions, events and courts.
 * Edition figures come from each product's inventory.
 */
class MaisonController extends Controller
{
    public function home(): Response
    {
        return $this->page('home', [
            'product' => Product::founding()?->toPageShare(),
        ]);
    }

    public function house(): Response
    {
        return $this->page('house');
    }

    /** @var list<int> */
    private const PRODUCTS_PER_PAGE_OPTIONS = [12, 24, 48];

    private const PRODUCTS_PER_PAGE_DEFAULT = 12;

    public function products(Request $request): Response
    {
        $filters = $this->productCatalogFilters($request);

        $products = Product::query()
            ->where('is_published', true)
            ->when($filters['search'] !== '', function ($query) use ($filters): void {
                $search = $filters['search'];
                $query->where(function ($inner) use ($search): void {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhereHas('translations', function ($translations) use ($search): void {
                            $translations
                                ->where('column', 'name')
                                ->where('locale', app()->getLocale())
                                ->where('value', 'like', "%{$search}%");
                        });
                });
            })
            ->when($filters['status'] !== '', function ($query) use ($filters): void {
                $query->where('status', $filters['status']);
            })
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate($filters['per_page'])
            ->withQueryString()
            ->through(fn (Product $product): array => $product->toCardShare());

        return $this->page('products/index', [
            'products' => $products,
            'filters' => [
                'search' => $filters['search'],
                'status' => $filters['status'],
                'per_page' => $filters['per_page'],
            ],
        ]);
    }

    public function productShow(string $locale, Product $product): Response
    {
        abort_unless($product->is_published, 404);

        $related = Product::query()
            ->where('is_published', true)
            ->where('id', '!=', $product->id)
            ->where('type', $product->type)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->limit(3)
            ->get();

        if ($related->isEmpty()) {
            $related = Product::query()
                ->where('is_published', true)
                ->where('id', '!=', $product->id)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->limit(3)
                ->get();
        }

        $product->loadMissing('sections.items', 'faqs');

        return $this->page('products/show', [
            'product' => $product->toPageShare(),
            'productCheckout' => Product::checkoutShare($product),
            'productEdition' => app(EditionInventory::class)->snapshot($product),
            'related' => $related
                ->map(fn (Product $item): array => $item->toCardShare())
                ->values()
                ->all(),
        ]);
    }

    /**
     * @return array{search: string, status: string, per_page: int}
     */
    private function productCatalogFilters(Request $request): array
    {
        $search = trim((string) $request->query('search', ''));
        $status = trim((string) $request->query('status', ''));

        if (! in_array($status, ['active', 'coming_soon'], true)) {
            $status = '';
        }

        $perPage = $request->query('per_page');
        $perPage = is_numeric($perPage) ? (int) $perPage : null;

        if ($perPage === null || ! in_array($perPage, self::PRODUCTS_PER_PAGE_OPTIONS, true)) {
            $perPage = self::PRODUCTS_PER_PAGE_DEFAULT;
        }

        return [
            'search' => $search,
            'status' => $status,
            'per_page' => $perPage,
        ];
    }

    public function story(): Response
    {
        return $this->page('story');
    }

    public function circle(): Response
    {
        return $this->page('circle');
    }

    public function dressing(): Response
    {
        return $this->page('dressing', [
            'items' => DressingItem::query()
                ->where('is_published', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->map(fn (DressingItem $item): array => $this->dressingCard($item))
                ->values()
                ->all(),
        ]);
    }

    public function dressingShow(string $locale, DressingItem $dressingItem): Response
    {
        abort_unless($dressingItem->is_published, 404);

        $related = DressingItem::query()
            ->where('is_published', true)
            ->where('id', '!=', $dressingItem->id)
            ->where('category', $dressingItem->category)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->limit(3)
            ->get();

        if ($related->isEmpty()) {
            $related = DressingItem::query()
                ->where('is_published', true)
                ->where('id', '!=', $dressingItem->id)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->limit(3)
                ->get();
        }

        return $this->page('dressing-show', [
            'item' => [
                ...$this->dressingCard($dressingItem),
                'description' => $dressingItem->translated('description'),
            ],
            'related' => $related
                ->map(fn (DressingItem $item): array => $this->dressingCard($item))
                ->values()
                ->all(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function dressingCard(DressingItem $item): array
    {
        return [
            'name' => $item->translated('name'),
            'slug' => $item->slug,
            'category' => $item->translated('category'),
            'status' => $item->status,
            /*
             * Kept apart rather than collapsed into resolvedImageUrl(): a
             * seeded image_key with no photograph on disk yet must still
             * fall back to the front end's brand-palette PlaceholderImage,
             * which only image_key (not a synthesized /images/... URL) can do.
             */
            'image_url' => $item->image_path !== null ? Storage::disk('public')->url($item->image_path) : null,
            'image_key' => $item->image_key,
        ];
    }

    public function journal(Request $request): Response
    {
        $locale = app()->getLocale();
        $paginator = JournalArticle::query()
            ->published()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate(Journal::PER_PAGE)
            ->withQueryString()
            ->through(fn (JournalArticle $article): array => Journal::card($article->toCatalogArray(), $locale));

        return $this->page('journal', [
            'articles' => $paginator,
        ]);
    }

    public function journalShow(string $locale, string $slug): Response
    {
        $article = JournalArticle::query()
            ->published()
            ->where('slug', $slug)
            ->first();

        abort_if($article === null, 404);

        return $this->page('journal/show', [
            'article' => Journal::localize($article->toCatalogArray(), $locale),
            'related' => $this->relatedJournalArticles($article, $locale),
        ]);
    }

    /**
     * @return list<array{slug: string, asset: string, image_url: string|null, category: string, title: string, excerpt: string, author: string, date: string, meta: string}>
     */
    private function relatedJournalArticles(JournalArticle $article, string $locale): array
    {
        $sameCategory = JournalArticle::query()
            ->published()
            ->whereKeyNot($article->id)
            ->when(filled($article->category), fn ($query) => $query->where('category', $article->category))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->limit(3)
            ->get();

        $related = $sameCategory
            ->map(fn (JournalArticle $relatedArticle): array => Journal::card($relatedArticle->toCatalogArray(), $locale));

        if ($related->count() < 3) {
            $excludeIds = [$article->id, ...$sameCategory->pluck('id')->all()];

            $fallback = JournalArticle::query()
                ->published()
                ->whereNotIn('id', $excludeIds)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->limit(3 - $related->count())
                ->get()
                ->map(fn (JournalArticle $relatedArticle): array => Journal::card($relatedArticle->toCatalogArray(), $locale));

            $related = $related->concat($fallback);
        }

        return $related->values()->all();
    }

    public function community(Request $request): Response
    {
        $props = [];
        $routeLocale = $request->route('locale');
        $pathLocale = is_string($routeLocale) ? $routeLocale : app()->getLocale();

        if ($request->user() !== null) {
            $props['posts'] = Inertia::scroll(
                fn () => CommunityFeed::paginate($request),
            );
            // Sessions and events each have their own page now.
            $props['courts'] = Club::query()
                ->publishedCorners()
                ->with('translations')
                ->get()
                ->map(function (Club $club) use ($pathLocale) {
                    $pin = $club->mapPinPosition();

                    return [
                        'id' => (string) $club->id,
                        'title' => $club->cornerTitle($pathLocale),
                        'body' => $club->cornerBody($pathLocale),
                        'location' => $club->cornerLocation($pathLocale),
                        'lat' => $club->lat !== null ? (float) $club->lat : null,
                        'lng' => $club->lng !== null ? (float) $club->lng : null,
                        'pin_top' => $pin['top'] ?? null,
                        'pin_left' => $pin['left'] ?? null,
                        'coming' => $pin === null,
                    ];
                });
            $props['sidebar'] = [
                'profile' => $this->communityProfile((int) $request->user()->id),
                'recentMembers' => $this->recentMembers(),
                'nextEvent' => $this->nextEvent(),
            ];
        }

        $tab = $request->string('tab')->toString();
        $props['tab'] = $tab === 'courts' ? 'courts' : 'feed';

        return $this->page('community', $props);
    }

    public function corner(): Response
    {
        return $this->page('corner', [
            'clubs' => Club::query()
                ->cornerPage()
                ->get()
                ->map(fn (Club $club): array => [
                    'city' => $club->city,
                    'country' => $club->countryLabel(),
                    'status' => $club->corner_pipeline_status?->value ?? CornerPipelineStatus::Open->value,
                    'status_label' => $club->corner_pipeline_status?->label()
                        ?? CornerPipelineStatus::Open->label(),
                ])
                ->values()
                ->all(),
            'cornerFormOptions' => config('maison.corner_form_options'),
        ]);
    }

    public function contact(InquiryDeviceCookie $deviceCookie): Response
    {
        $deviceCookie->remember(request());

        return $this->page('contact', [
            'faqs' => Faq::publishedFor(FaqContext::Contact)
                ->map(fn (Faq $faq): array => [
                    'question' => $faq->translated('question'),
                    'answer' => $faq->translated('answer'),
                ])
                ->values()
                ->all(),
        ]);
    }

    public function privacy(): Response
    {
        return $this->legalPage('privacy');
    }

    public function terms(): Response
    {
        return $this->legalPage('terms');
    }

    public function shipping(): Response
    {
        return $this->legalPage('shipping');
    }

    public function care(): Response
    {
        return $this->legalPage('care');
    }

    /**
     * Render a page under resources/js/pages/maison, with the edition state
     * every page may quote attached.
     *
     * @param  array<string, mixed>  $props
     */
    private function page(string $component, array $props = []): Response
    {
        return Inertia::render("maison/{$component}", [
            'edition' => $this->edition(),
            ...$props,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function edition(): array
    {
        return app(EditionInventory::class)->snapshot(Product::founding());
    }

    private function legalPage(string $slug): Response
    {
        $page = LegalPage::query()
            ->where('slug', $slug)
            ->where('is_published', true)
            ->first();
        $body = $page?->translated('body') ?? $this->fallbackLegalBody($slug);

        return $this->page("legal/{$slug}", [
            'legalPage' => [
                'slug' => $slug,
                'body' => LegalHtml::sanitize($body),
            ],
        ]);
    }

    /**
     * @return array{editionNumber: string, postCount: int, sessionCount: int}
     */
    private function communityProfile(int $userId): array
    {
        $editionNumber = User::query()
            ->whereKey($userId)
            ->with(['orders' => fn ($query) => $query
                ->whereHas('product', fn ($productQuery) => $productQuery->where('grants_founding_circle', true))
                ->latest('id')])
            ->first()
            ?->orders
            ->first()
            ?->edition_number;

        return [
            'editionNumber' => $editionNumber !== null ? str_pad((string) $editionNumber, 3, '0', STR_PAD_LEFT) : '---',
            'postCount' => CommunityPost::query()->where('author_id', $userId)->count(),
            'sessionCount' => CommunitySession::query()
                ->whereHas('participants', fn ($query) => $query->where('user_id', $userId))
                ->count(),
        ];
    }

    /**
     * @return array<int, array{name: string, city: string, editionNumber: string}>
     */
    private function recentMembers(): array
    {
        return User::query()
            ->whereHas('orders', fn ($query) => $query
                ->whereNotNull('edition_number')
                ->whereHas('product', fn ($productQuery) => $productQuery->where('grants_founding_circle', true)))
            ->with(['orders' => fn ($query) => $query
                ->whereHas('product', fn ($productQuery) => $productQuery->where('grants_founding_circle', true))
                ->latest('id')])
            ->latest('id')
            ->limit(4)
            ->get()
            ->map(function (User $user): array {
                $order = $user->orders->first();

                return [
                    'name' => $user->name,
                    'city' => (string) ($order?->locale ?? 'Member'),
                    'editionNumber' => str_pad((string) ($order?->edition_number ?? 0), 3, '0', STR_PAD_LEFT),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array{title: string, location: string, startsAt: string}|null
     */
    private function nextEvent(): ?array
    {
        $event = CommunityEvent::query()
            ->where('starts_at', '>=', now())
            ->orderBy('starts_at')
            ->first();

        if ($event === null) {
            return null;
        }

        return [
            'title' => $event->translated('title'),
            'location' => $event->translated('location'),
            'startsAt' => $event->starts_at->toDateString(),
        ];
    }

    private function fallbackLegalBody(string $slug): string
    {
        $body = match ($slug) {
            'privacy' => '<p>Privacybeleid van Maison Anversa.</p>',
            'terms' => '<p>Algemene voorwaarden van Maison Anversa.</p>',
            'shipping' => '<p>Verzending en retourinformatie van Maison Anversa.</p>',
            'care' => '<p>Zorg- en garantiebeleid van Maison Anversa.</p>',
            default => '',
        };

        return LegalHtml::sanitize($body);
    }
}
