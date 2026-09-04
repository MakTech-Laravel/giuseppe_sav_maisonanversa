<?php

namespace App\Http\Controllers\Community;

use App\Enums\ClubStatus;
use App\Enums\SessionSport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Community\StoreClubRequest;
use App\Models\Club;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ClubController extends Controller
{
    private const SEARCH_LIMIT = 8;

    private const MIN_QUERY_LENGTH = 2;

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
