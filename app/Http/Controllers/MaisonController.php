<?php

namespace App\Http\Controllers;

use App\Models\CommunityEvent;
use App\Models\CommunitySession;
use App\Services\Edition\EditionInventory;
use App\Support\CommunityFeed;
use App\Support\Journal;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The public Maison Anversa site. Most pages are presentational in this phase;
 * the Journal is the exception, paging a static catalog. Edition figures are
 * the one piece of shared state and come from config/maison.php.
 */
class MaisonController extends Controller
{
    public function home(): Response
    {
        return $this->page('home');
    }

    public function house(): Response
    {
        return $this->page('house');
    }

    public function product(): Response
    {
        return $this->page('product');
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
        return $this->page('dressing');
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
                    'location' => $session->location,
                    'starts_at' => $session->starts_at->toIso8601String(),
                    'capacity' => $session->capacity,
                    'level' => $session->level,
                    'notes' => $session->notes,
                    'host' => $session->host->name,
                    'joined' => $session->participants->contains('user_id', $request->user()->id),
                    'spots' => $session->capacity === null
                        ? null
                        : max(0, $session->capacity - $session->participants->count()),
                    'players' => $session->participants->map(fn ($p) => strtoupper(substr($p->user->name, 0, 2)))->all(),
                ]);
            $props['events'] = CommunityEvent::query()
                ->with('rsvps.user')
                ->where('starts_at', '>=', now()->subDay())
                ->orderBy('starts_at')
                ->get()
                ->map(fn (CommunityEvent $event) => [
                    'id' => (string) $event->id,
                    'title' => $event->title,
                    'description' => $event->description,
                    'starts_at' => $event->starts_at->toIso8601String(),
                    'location' => $event->location,
                    'joined' => $event->rsvps->contains('user_id', $request->user()->id),
                    'rsvp_count' => $event->rsvps->count(),
                    'attendees' => $event->rsvps
                        ->take(3)
                        ->map(fn ($rsvp) => strtoupper(substr($rsvp->user->name, 0, 2)))
                        ->all(),
                ]);
        }

        return $this->page('community', $props);
    }

    public function corner(): Response
    {
        return $this->page('corner');
    }

    public function contact(): Response
    {
        return $this->page('contact');
    }

    public function privacy(): Response
    {
        return $this->page('legal/privacy');
    }

    public function terms(): Response
    {
        return $this->page('legal/terms');
    }

    public function shipping(): Response
    {
        return $this->page('legal/shipping');
    }

    public function care(): Response
    {
        return $this->page('legal/care');
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
        return app(EditionInventory::class)->snapshot();
    }
}
