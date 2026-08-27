<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ClubStatus;
use App\Enums\SessionSport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MergeClubRequest;
use App\Http\Requests\Admin\StoreClubRequest;
use App\Http\Requests\Admin\UpdateClubRequest;
use App\Models\Club;
use App\Models\CommunitySession;
use App\Services\Community\ClubMerger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ClubController extends Controller
{
    private const PER_PAGE = 15;

    public function index(Request $request, string $locale): Response
    {
        $search = $request->string('search')->toString();
        $status = $request->string('status')->toString();
        $city = $request->string('city')->toString();

        $clubs = Club::query()
            ->withCount('sessions')
            ->when($search !== '', fn ($query) => $query->matching($search))
            ->when(ClubStatus::tryFrom($status) !== null, fn ($query) => $query->where('status', $status))
            ->when($city !== '', fn ($query) => $query->where('city', $city))
            ->orderByRaw('CASE WHEN status = ? THEN 0 ELSE 1 END', [ClubStatus::Pending->value])
            ->orderBy('name')
            ->paginate(self::PER_PAGE)
            ->withQueryString()
            ->through(fn (Club $club): array => $this->row($club));

        return Inertia::render('admin/clubs/index', [
            'clubs' => $clubs,
            'filters' => [
                'search' => $search ?: null,
                'status' => $status ?: null,
                'city' => $city ?: null,
            ],
            'cities' => Club::query()->distinct()->orderBy('city')->pluck('city')->all(),
            'pendingCount' => Club::query()->where('status', ClubStatus::Pending)->count(),
            'statusOptions' => ClubStatus::options(),
        ]);
    }

    public function create(Request $request, string $locale): Response
    {
        return Inertia::render('admin/clubs/create', [
            'options' => $this->formOptions(),
        ]);
    }

    public function store(StoreClubRequest $request, string $locale): RedirectResponse
    {
        $data = $request->safe()->except(['image']);
        $data['slug'] = $this->uniqueSlug($data['name']);
        $data['country'] = $data['country'] ?? 'BE';

        if ($request->hasFile('image')) {
            $data['image_path'] = $this->storeImage($request->file('image'));
        }

        if (($data['status'] ?? null) === ClubStatus::Approved->value) {
            $data['approved_by_id'] = $request->user()->id;
            $data['approved_at'] = now();
        }

        $club = Club::query()->create($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Club aangemaakt.')]);

        return redirect()->route('admin.clubs.show', [
            'locale' => $locale,
            'club' => $club->id,
        ]);
    }

    public function show(Request $request, string $locale, Club $club): Response
    {
        $club->loadCount('sessions')->load(['submittedBy', 'approvedBy', 'mergedInto', 'duplicates']);

        return Inertia::render('admin/clubs/show', [
            'club' => [
                ...$this->row($club),
                'street' => $club->street,
                'postal_code' => $club->postal_code,
                'country' => $club->country,
                'website' => $club->website,
                'phone' => $club->phone,
                'lat' => $club->lat !== null ? (float) $club->lat : null,
                'lng' => $club->lng !== null ? (float) $club->lng : null,
                'image_url' => $club->imageUrl(),
                'submitted_by' => $club->submittedBy?->only(['id', 'name', 'email']),
                'approved_by' => $club->approvedBy?->only(['id', 'name']),
                'approved_at' => $club->approved_at?->toIso8601String(),
                'merged_into' => $club->mergedInto?->only(['id', 'name', 'city']),
                'duplicates' => $club->duplicates->map(fn (Club $duplicate): array => $duplicate->only(['id', 'name', 'city']))->all(),
            ],
            'sessions' => CommunitySession::query()
                ->where('club_id', $club->id)
                ->with('host')
                ->withCount('participants')
                ->orderByDesc('starts_at')
                ->limit(20)
                ->get()
                ->map(fn (CommunitySession $session): array => [
                    'id' => (string) $session->id,
                    'host' => $session->host->name,
                    'sport_label' => $session->sport->label(),
                    'starts_at' => $session->starts_at->toIso8601String(),
                    'participants_count' => (int) $session->participants_count,
                    'capacity' => $session->capacity,
                ])->all(),
            'mergeCandidates' => Club::query()
                ->whereKeyNot($club->id)
                ->where('status', '!=', ClubStatus::Merged)
                ->orderBy('name')
                ->get(['id', 'name', 'city'])
                ->map(fn (Club $candidate): array => [
                    'id' => $candidate->id,
                    'name' => $candidate->name,
                    'city' => $candidate->city,
                ])->all(),
        ]);
    }

    public function edit(Request $request, string $locale, Club $club): Response
    {
        return Inertia::render('admin/clubs/edit', [
            'club' => [
                'id' => $club->id,
                'name' => $club->name,
                'sports' => array_values((array) $club->sports),
                'street' => $club->street,
                'postal_code' => $club->postal_code,
                'city' => $club->city,
                'country' => $club->country,
                'lat' => $club->lat !== null ? (float) $club->lat : null,
                'lng' => $club->lng !== null ? (float) $club->lng : null,
                'website' => $club->website,
                'phone' => $club->phone,
                'status' => $club->status->value,
                'is_partner' => $club->is_partner,
                'image_url' => $club->imageUrl(),
            ],
            'options' => $this->formOptions(),
        ]);
    }

    public function update(UpdateClubRequest $request, string $locale, Club $club): RedirectResponse
    {
        $data = $request->safe()->except(['image', 'remove_image']);
        $data['country'] = $data['country'] ?? 'BE';

        if ($request->hasFile('image')) {
            $this->deleteImage($club);
            $data['image_path'] = $this->storeImage($request->file('image'));
        } elseif ($request->boolean('remove_image')) {
            $this->deleteImage($club);
            $data['image_path'] = null;
        }

        if ($data['status'] === ClubStatus::Approved->value && ! $club->isApproved()) {
            $data['approved_by_id'] = $request->user()->id;
            $data['approved_at'] = now();
        }

        $club->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Club bijgewerkt.')]);

        return redirect()->route('admin.clubs.show', [
            'locale' => $locale,
            'club' => $club->id,
        ]);
    }

    public function approve(Request $request, string $locale, Club $club): RedirectResponse
    {
        $club->update([
            'status' => ClubStatus::Approved,
            'approved_by_id' => $request->user()->id,
            'approved_at' => now(),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Club goedgekeurd.')]);

        return back();
    }

    public function reject(Request $request, string $locale, Club $club): RedirectResponse
    {
        $club->update([
            'status' => ClubStatus::Rejected,
            'approved_by_id' => null,
            'approved_at' => null,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Club afgewezen.')]);

        return back();
    }

    public function merge(MergeClubRequest $request, string $locale, Club $club, ClubMerger $merger): RedirectResponse
    {
        $survivor = Club::query()->findOrFail($request->validated('target_id'));

        $merger->merge($club, $survivor);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Clubs samengevoegd.')]);

        return redirect()->route('admin.clubs.show', [
            'locale' => $locale,
            'club' => $survivor->id,
        ]);
    }

    public function destroy(Request $request, string $locale, Club $club): RedirectResponse
    {
        $this->deleteImage($club);
        $club->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Club verwijderd.')]);

        return redirect()->route('admin.clubs.index', ['locale' => $locale]);
    }

    /**
     * @return array<string, mixed>
     */
    private function row(Club $club): array
    {
        return [
            'id' => $club->id,
            'name' => $club->name,
            'city' => $club->city,
            'address' => $club->addressLine(),
            'sports' => array_values((array) $club->sports),
            'status' => $club->status->value,
            'status_label' => $club->status->label(),
            'is_partner' => $club->is_partner,
            'sessions_count' => (int) ($club->sessions_count ?? 0),
            'created_at' => $club->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'sports' => SessionSport::options(),
            'statuses' => array_values(array_filter(
                ClubStatus::options(),
                static fn (array $option): bool => $option['value'] !== ClubStatus::Merged->value,
            )),
        ];
    }

    private function storeImage(UploadedFile $file): string
    {
        return $file->store('clubs', 'public');
    }

    private function deleteImage(Club $club): void
    {
        if ($club->image_path === null || $club->image_path === '') {
            return;
        }

        Storage::disk('public')->delete($club->image_path);
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
