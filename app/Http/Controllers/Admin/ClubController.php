<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ClubStatus;
use App\Enums\CornerPipelineStatus;
use App\Enums\SessionSport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MergeClubRequest;
use App\Http\Requests\Admin\StoreClubRequest;
use App\Http\Requests\Admin\TranslateClubRequest;
use App\Http\Requests\Admin\UpdateClubRequest;
use App\Http\Requests\Admin\UpdateClubTranslationsRequest;
use App\Jobs\TranslateModelJob;
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

    /** @var list<string> */
    private const TRANSLATION_COLUMNS = ['corner_title', 'corner_body', 'corner_location'];

    /**
     * @return list<string>
     */
    private function translationLocales(): array
    {
        return config('maison.locales');
    }

    public function index(Request $request, string $locale): Response
    {
        $search = $request->string('search')->toString();
        $status = $request->string('status')->toString();
        $city = $request->string('city')->toString();
        $flag = $request->string('flag')->toString();

        $clubs = Club::query()
            ->withCount('sessions')
            ->when($search !== '', fn ($query) => $query->matching($search))
            ->when(ClubStatus::tryFrom($status) !== null, fn ($query) => $query->where('status', $status))
            ->when($city !== '', fn ($query) => $query->where('city', $city))
            ->when($flag === 'partner', fn ($query) => $query->where('is_partner', true))
            ->when($flag === 'corner', fn ($query) => $query->where('has_corner', true))
            ->when($flag === 'corner_page', fn ($query) => $query->where('show_on_corner_page', true))
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
                'flag' => in_array($flag, ['partner', 'corner', 'corner_page'], true) ? $flag : null,
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

        if ($club->has_corner) {
            TranslateModelJob::dispatchSync(Club::class, $club->id);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Club aangemaakt.')]);

        return redirect()->route('admin.clubs.show', [
            'locale' => $locale,
            'club' => $club->id,
        ]);
    }

    public function show(Request $request, string $locale, Club $club): Response
    {
        $club->loadCount('sessions')->load([
            'submittedBy',
            'approvedBy',
            'mergedInto',
            'duplicates',
            'translations',
        ]);

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
                'is_session_venue' => $club->is_session_venue,
                'show_on_corner_page' => $club->show_on_corner_page,
                'corner_pipeline_status' => $club->corner_pipeline_status?->value,
                'corner_pipeline_label' => $club->corner_pipeline_status?->label(),
                'has_corner' => $club->has_corner,
                'corner_published' => $club->corner_published,
                'corner_title' => $club->corner_title,
                'corner_body' => $club->corner_body,
                'corner_location' => $club->corner_location,
                'sort_order' => $club->sort_order,
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
                ->get()
                ->map(fn (Club $candidate): array => $this->mergeCandidateRow($candidate))
                ->all(),
            'locales' => config('maison.locales'),
            'translations' => $this->translationBundle($club),
            'translationStatus' => $this->translationStatus($club),
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
                'is_session_venue' => $club->is_session_venue,
                'show_on_corner_page' => $club->show_on_corner_page,
                'corner_pipeline_status' => $club->corner_pipeline_status?->value,
                'has_corner' => $club->has_corner,
                'corner_published' => $club->corner_published,
                'corner_title' => $club->corner_title,
                'corner_body' => $club->corner_body,
                'corner_location' => $club->corner_location,
                'sort_order' => $club->sort_order,
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

        if ($club->has_corner) {
            TranslateModelJob::dispatchSync(Club::class, $club->id);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Club bijgewerkt.')]);

        return redirect()->route('admin.clubs.show', [
            'locale' => $locale,
            'club' => $club->id,
        ]);
    }

    public function updateTranslations(
        UpdateClubTranslationsRequest $request,
        string $locale,
        Club $club,
    ): RedirectResponse {
        $data = $request->validated();

        foreach ($this->translationLocales() as $targetLocale) {
            foreach (self::TRANSLATION_COLUMNS as $column) {
                $club->translations()->updateOrCreate(
                    [
                        'locale' => $targetLocale,
                        'column' => $column,
                    ],
                    [
                        'value' => $data[$targetLocale][$column] ?? '',
                        'source_hash' => $club->translationSourceHash($column),
                    ],
                );
            }
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Vertalingen opgeslagen.')]);

        return redirect()->route('admin.clubs.show', [
            'locale' => $locale,
            'club' => $club->id,
        ]);
    }

    public function translate(
        TranslateClubRequest $request,
        string $locale,
        Club $club,
    ): RedirectResponse {
        $targetLocale = $request->validated('target_locale');

        if (filled($targetLocale)) {
            $club->translations()
                ->where('locale', $targetLocale)
                ->whereIn('column', self::TRANSLATION_COLUMNS)
                ->delete();

            TranslateModelJob::dispatchSync(Club::class, $club->id, [$targetLocale]);
        } else {
            $club->translations()
                ->whereIn('column', self::TRANSLATION_COLUMNS)
                ->delete();

            TranslateModelJob::dispatchSync(Club::class, $club->id);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Vertalingen worden bijgewerkt.')]);

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

        $merger->merge($club, $survivor, $request->mergePayload());

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
            'is_session_venue' => $club->is_session_venue,
            'show_on_corner_page' => $club->show_on_corner_page,
            'has_corner' => $club->has_corner,
            'corner_published' => $club->corner_published,
            'sessions_count' => (int) ($club->sessions_count ?? 0),
            'created_at' => $club->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mergeCandidateRow(Club $club): array
    {
        return [
            'id' => $club->id,
            'name' => $club->name,
            'street' => $club->street,
            'postal_code' => $club->postal_code,
            'city' => $club->city,
            'country' => $club->country,
            'lat' => $club->lat !== null ? (float) $club->lat : null,
            'lng' => $club->lng !== null ? (float) $club->lng : null,
            'website' => $club->website,
            'phone' => $club->phone,
            'status' => $club->status->value,
            'status_label' => $club->status->label(),
            'sports' => array_values((array) $club->sports),
            'is_partner' => $club->is_partner,
            'is_session_venue' => $club->is_session_venue,
            'show_on_corner_page' => $club->show_on_corner_page,
            'corner_pipeline_status' => $club->corner_pipeline_status?->value,
            'corner_pipeline_label' => $club->corner_pipeline_status?->label(),
            'has_corner' => $club->has_corner,
            'corner_published' => $club->corner_published,
            'corner_title' => $club->corner_title,
            'corner_body' => $club->corner_body,
            'corner_location' => $club->corner_location,
            'sort_order' => $club->sort_order,
            'image_url' => $club->imageUrl(),
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
            'pipelineStatuses' => CornerPipelineStatus::options(),
        ];
    }

    /**
     * @return array<string, array{corner_title: string, corner_body: string, corner_location: string}>
     */
    private function translationBundle(Club $club): array
    {
        $bundle = [];

        foreach ($this->translationLocales() as $targetLocale) {
            $bundle[$targetLocale] = [
                'corner_title' => $this->storedTranslation($club, 'corner_title', $targetLocale),
                'corner_body' => $this->storedTranslation($club, 'corner_body', $targetLocale),
                'corner_location' => $this->storedTranslation($club, 'corner_location', $targetLocale),
            ];
        }

        return $bundle;
    }

    private function storedTranslation(Club $club, string $column, string $locale): string
    {
        $row = $club->translations->first(
            fn ($translation): bool => $translation->locale === $locale
                && $translation->column === $column,
        );

        return (string) ($row?->value ?? '');
    }

    /**
     * @return array<string, array{corner_title: bool, corner_body: bool, corner_location: bool}>
     */
    private function translationStatus(Club $club): array
    {
        $status = [];

        foreach ($this->translationLocales() as $targetLocale) {
            $columnStatus = [];

            foreach (self::TRANSLATION_COLUMNS as $column) {
                $source = trim((string) ($club->getAttribute($column) ?? ''));

                if ($source === '') {
                    $columnStatus[$column] = true;

                    continue;
                }

                $columnStatus[$column] = $club->translations->contains(
                    fn ($translation): bool => $translation->locale === $targetLocale
                        && $translation->column === $column,
                );
            }

            $status[$targetLocale] = $columnStatus;
        }

        return $status;
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
