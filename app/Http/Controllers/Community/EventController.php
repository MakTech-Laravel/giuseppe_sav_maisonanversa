<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Controller;
use App\Models\CommunityEvent;
use App\Models\User;
use App\Support\EventFeed;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    public function index(Request $request, string $locale): Response
    {
        $user = $request->user();
        $tab = $request->string('tab')->toString();
        $tab = in_array($tab, EventFeed::TABS, true) ? $tab : 'open';

        return Inertia::render('maison/events/index', [
            'events' => Inertia::scroll(
                fn (): LengthAwarePaginator => $this->paginateTab($request, $locale, $tab, $user),
            ),
            'tab' => $tab,
            'counts' => [
                'open' => EventFeed::open()->count(),
                'mine' => EventFeed::mine($user)->count(),
                'past' => EventFeed::past($user)->count(),
            ],
        ]);
    }

    /**
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    private function paginateTab(Request $request, string $locale, string $tab, User $user): LengthAwarePaginator
    {
        $events = EventFeed::forTab($tab, $user)
            ->paginate(EventFeed::PER_PAGE)
            ->withQueryString()
            ->withPath(route('community.events.index', ['locale' => $locale]));

        $events->setCollection(
            $events->getCollection()->map(
                fn (CommunityEvent $event): array => EventFeed::toCard($event, $user, $locale),
            ),
        );

        return $events;
    }
}
