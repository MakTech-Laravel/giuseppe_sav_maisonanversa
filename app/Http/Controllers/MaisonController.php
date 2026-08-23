<?php

namespace App\Http\Controllers;

use App\Enums\FaqContext;
use App\Models\CommunityCourt;
use App\Models\CommunityEvent;
use App\Models\CommunityPost;
use App\Models\CommunitySession;
use App\Models\DressingItem;
use App\Models\Faq;
use App\Models\LegalPage;
use App\Models\PartnerClub;
use App\Models\Product;
use App\Models\User;
use App\Services\Edition\EditionInventory;
use App\Support\CommunityFeed;
use App\Support\Journal;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
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

    public function product(): Response
    {
        $product = Product::founding();

        if ($product === null) {
            abort(404);
        }

        $related = Product::query()
            ->where('id', '!=', $product->id)
            ->where('status', 'coming_soon')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (Product $item): array => $item->toCardShare())
            ->values()
            ->all();

        return $this->page('product', [
            'product' => $product->toPageShare(),
            'related' => $related,
            'faqs' => Faq::publishedFor(FaqContext::Product)
                ->map(fn (Faq $faq): array => [
                    'question' => $faq->translated('question'),
                    'answer' => $faq->translated('answer'),
                ])
                ->values()
                ->all(),
        ]);
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
                ->map(fn (DressingItem $item): array => [
                    'name' => $item->translated('name'),
                    'slug' => $item->slug,
                    'category' => $item->translated('category'),
                    'status' => $item->status,
                    'image_key' => $item->image_key,
                ])
                ->values()
                ->all(),
        ]);
    }

    public function journal(Request $request): Response
    {
        $locale = app()->getLocale();
        $articles = Journal::articles();
        $total = count($articles);
        $perPage = Journal::PER_PAGE;
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = max(1, $request->integer('page', 1));

        if ($page > $lastPage) {
            abort(404);
        }

        $slice = array_slice($articles, ($page - 1) * $perPage, $perPage);

        $paginator = new LengthAwarePaginator(
            array_map(fn (array $article): array => Journal::card($article, $locale), $slice),
            $total,
            $perPage,
            $page,
            [
                'path' => route('maison.journal', ['locale' => $locale]),
                'pageName' => 'page',
            ],
        );

        return $this->page('journal', [
            'articles' => $paginator,
        ]);
    }

    public function journalShow(string $locale, string $slug): Response
    {
        $article = Journal::find($slug);

        if ($article === null) {
            abort(404);
        }

        return $this->page('journal/show', [
            'article' => Journal::localize($article, $locale),
            'related' => Journal::related($slug, $locale),
        ]);
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
            $props['sessions'] = CommunitySession::query()
                ->with(['host', 'participants.user'])
                ->where('starts_at', '>=', now()->subDay())
                ->orderBy('starts_at')
                ->get()
                ->map(fn (CommunitySession $session) => [
                    'id' => (string) $session->id,
                    'location' => $session->translated('location'),
                    'starts_at' => $session->starts_at->toIso8601String(),
                    'capacity' => $session->capacity,
                    'level' => $session->level,
                    'notes' => $session->translated('notes'),
                    'host' => $session->host->name,
                    'joined' => $session->participants->contains('user_id', $request->user()->id),
                    'spots' => $session->capacity === null
                        ? null
                        : max(0, $session->capacity - $session->participants->count()),
                    'players' => $session->participants->map(fn ($p) => strtoupper(substr($p->user->name, 0, 2)))->all(),
                ]);
            $props['events'] = CommunityEvent::query()
                ->with('rsvps.user')
                ->withCount('rsvps')
                ->where('starts_at', '>=', now()->subDay())
                ->orderBy('starts_at')
                ->get()
                ->map(fn (CommunityEvent $event) => [
                    'id' => (string) $event->id,
                    'title' => $event->translated('title'),
                    'description' => $event->translated('description'),
                    'starts_at' => $event->starts_at->toIso8601String(),
                    'location' => $event->translated('location'),
                    'capacity' => $event->capacity,
                    'thumbnail_url' => $event->thumbnailUrl(),
                    'joined' => $event->rsvps->contains('user_id', $request->user()->id),
                    'is_full' => $event->isFull(),
                    'rsvp_count' => $event->rsvps->count(),
                    'attendees' => $event->rsvps
                        ->take(3)
                        ->map(fn ($rsvp) => strtoupper(substr($rsvp->user->name, 0, 2)))
                        ->all(),
                ]);
            $props['courts'] = CommunityCourt::query()
                ->published()
                ->with('translations')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->map(function (CommunityCourt $court) use ($pathLocale) {
                    $pin = $court->mapPinPosition();

                    return [
                        'id' => (string) $court->id,
                        'title' => $court->translated('title', $pathLocale),
                        'body' => $court->translated('body', $pathLocale),
                        'location' => $court->translated('location', $pathLocale),
                        'lat' => $court->lat !== null ? (float) $court->lat : null,
                        'lng' => $court->lng !== null ? (float) $court->lng : null,
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

        return $this->page('community', $props);
    }

    public function corner(): Response
    {
        return $this->page('corner', [
            'clubs' => PartnerClub::query()
                ->where('is_published', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->map(fn (PartnerClub $club): array => [
                    'city' => $club->translated('city'),
                    'country' => $club->translated('country'),
                    'status' => $club->status,
                ])
                ->values()
                ->all(),
            'cornerFormOptions' => config('maison.corner_form_options'),
        ]);
    }

    public function contact(): Response
    {
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
                'body' => $body,
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
        return match ($slug) {
            'privacy' => 'Privacybeleid van Maison Anversa.',
            'terms' => 'Algemene voorwaarden van Maison Anversa.',
            'shipping' => 'Verzending en retourinformatie van Maison Anversa.',
            'care' => 'Zorg- en garantiebeleid van Maison Anversa.',
            default => '',
        };
    }
}
