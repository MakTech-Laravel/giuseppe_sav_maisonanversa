<?php

namespace App\Http\Controllers\Community;

use App\Enums\ClubStatus;
use App\Enums\SessionSport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Community\StoreClubRequest;
use App\Models\Club;
use App\Support\ClubDirectory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ClubController extends Controller
{
    private const SEARCH_LIMIT = 8;

    private const MIN_QUERY_LENGTH = 2;

    public function index(Request $request, string $locale): Response
    {
        $search = $request->string('search')->toString() ?: null;
        $city = $request->string('city')->toString() ?: null;
        $sport = $request->string('sport')->toString() ?: null;

        return Inertia::render('maison/clubs/index', [
            'clubs' => Inertia::scroll(
                fn () => ClubDirectory::paginate($search, $city, $sport, $locale),
            ),
            'filters' => [
                'search' => $search,
                'city' => $city,
                'sport' => $sport,
            ],
            'cities' => Club::query()
                ->approved()
                ->distinct()
                ->orderBy('city')
                ->pluck('city')
                ->all(),
        ]);
    }

    public function show(Request $request, string $locale, Club $club): Response
    {
        abort_unless($club->isApproved(), 404);

        return Inertia::render('maison/clubs/show', [
            'club' => ClubDirectory::toProfile($club, $request->user(), $locale),
        ]);
    }

    /**
     * Autocomplete for the session composer. Only approved clubs are bookable.
     */
    public function search(Request $request, string $locale): JsonResponse
    {
        $term = trim($request->string('q')->toString());

        if (mb_strlen($term) < self::MIN_QUERY_LENGTH) {
            return response()->json(['clubs' => []]);
        }

        $query = Club::query()
            ->sessionVenues()
            ->matching($term)
            ->orderByDesc('is_partner')
            ->orderBy('name')
            ->limit(self::SEARCH_LIMIT);

        $sport = $request->string('sport')->toString();

        if (SessionSport::tryFrom($sport) !== null) {
            $query->whereJsonContains('sports', $sport);
        }

        return response()->json([
            'clubs' => $query->get()->map(fn (Club $club): array => $club->toCardArray())->all(),
        ]);
    }

    /**
     * Members can add a venue we do not know yet; it stays pending until an
     * admin approves it, so it cannot be attached to a session immediately.
     */
    public function store(StoreClubRequest $request, string $locale): RedirectResponse
    {
        Club::query()->create([
            ...$request->validated(),
            'country' => $request->validated('country') ?? 'BE',
            'slug' => $this->uniqueSlug($request->validated('name')),
            'status' => ClubStatus::Pending,
            'is_session_venue' => true,
            'submitted_by_id' => $request->user()->id,
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Bedankt. Uw club is ingediend en wacht op goedkeuring.'),
        ]);

        return back();
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'club';
        $slug = $base;
        $suffix = 2;

        while (Club::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
