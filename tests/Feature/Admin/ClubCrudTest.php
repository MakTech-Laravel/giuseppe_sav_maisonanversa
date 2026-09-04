<?php

use App\Enums\ClubStatus;
use App\Enums\RoleEnum;
use App\Enums\SessionSport;
use App\Models\Club;
use App\Models\CommunitySession;
use App\Models\Translation;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);

    $this->admin = User::factory()->admin()->create();
    $this->admin->assignRole(RoleEnum::SUPER_ADMIN->value);
    $this->admin->syncTypeFromRoles();
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function clubMergePayload(int $targetId, array $overrides = []): array
{
    $sources = [
        'name' => 'survivor',
        'street' => 'survivor',
        'postal_code' => 'survivor',
        'city' => 'survivor',
        'country' => 'survivor',
        'lat' => 'survivor',
        'lng' => 'survivor',
        'website' => 'survivor',
        'phone' => 'survivor',
        'status' => 'survivor',
        'corner_pipeline_status' => 'survivor',
        'corner_title' => 'survivor',
        'corner_body' => 'survivor',
        'corner_location' => 'survivor',
        'sort_order' => 'survivor',
    ];

    return [
        'target_id' => $targetId,
        'sources' => array_replace($sources, $overrides['sources'] ?? []),
        'image_source' => $overrides['image_source'] ?? 'survivor',
        'is_partner' => $overrides['is_partner'] ?? false,
        'is_session_venue' => $overrides['is_session_venue'] ?? true,
        'has_corner' => $overrides['has_corner'] ?? false,
        'corner_published' => $overrides['corner_published'] ?? false,
        'show_on_corner_page' => $overrides['show_on_corner_page'] ?? false,
        'sports' => $overrides['sports'] ?? [SessionSport::Padel->value],
    ];
}

test('staff create clubs that are approved right away', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.clubs.store', ['locale' => 'nl']), [
            'name' => 'Padel Noord',
            'sports' => [SessionSport::Padel->value],
            'street' => 'Noordlaan 1',
            'postal_code' => '2030',
            'city' => 'Antwerpen',
            'status' => ClubStatus::Approved->value,
            'is_partner' => true,
        ])
        ->assertRedirect();

    $club = Club::query()->where('name', 'Padel Noord')->firstOrFail();

    expect($club->status)->toBe(ClubStatus::Approved)
        ->and($club->is_partner)->toBeTrue()
        ->and($club->approved_by_id)->toBe($this->admin->id)
        ->and($club->approved_at)->not->toBeNull();
});

test('the index surfaces the pending queue first', function () {
    Club::factory()->create(['name' => 'Approved Club']);
    Club::factory()->pending()->create(['name' => 'Pending Club']);

    $this->actingAs($this->admin)
        ->get(route('admin.clubs.index', ['locale' => 'nl']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/clubs/index')
            ->where('pendingCount', 1)
            ->where('clubs.data.0.name', 'Pending Club')
        );
});

test('the index can be filtered by status', function () {
    Club::factory()->create(['name' => 'Approved Club']);
    Club::factory()->pending()->create(['name' => 'Pending Club']);

    $this->actingAs($this->admin)
        ->get(route('admin.clubs.index', ['locale' => 'nl', 'status' => ClubStatus::Pending->value]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('clubs.data', 1)
            ->where('clubs.data.0.name', 'Pending Club')
        );
});

test('approving a member submitted club makes it searchable', function () {
    $member = User::factory()->create();
    $club = Club::factory()->pending()->create(['name' => 'Padel Zuid', 'city' => 'Antwerpen']);

    $this->actingAs($this->admin)
        ->from(route('admin.clubs.index', ['locale' => 'nl']))
        ->patch(route('admin.clubs.approve', ['locale' => 'nl', 'club' => $club->id]))
        ->assertRedirect();

    expect($club->fresh()->status)->toBe(ClubStatus::Approved);

    $this->actingAs($member)
        ->getJson(route('community.clubs.search', ['locale' => 'nl', 'q' => 'Padel Zuid']))
        ->assertOk()
        ->assertJsonCount(1, 'clubs');
});

test('rejecting a club keeps it out of member search', function () {
    $member = User::factory()->create();
    $club = Club::factory()->pending()->create(['name' => 'Padel Zuid']);

    $this->actingAs($this->admin)
        ->from(route('admin.clubs.index', ['locale' => 'nl']))
        ->patch(route('admin.clubs.reject', ['locale' => 'nl', 'club' => $club->id]))
        ->assertRedirect();

    expect($club->fresh()->status)->toBe(ClubStatus::Rejected);

    $this->actingAs($member)
        ->getJson(route('community.clubs.search', ['locale' => 'nl', 'q' => 'Padel Zuid']))
        ->assertOk()
        ->assertJsonCount(0, 'clubs');
});

test('merging duplicates repoints sessions onto the surviving club', function () {
    $duplicate = Club::factory()->partner()->create([
        'name' => 'Padel Zuid Antwerpen',
        'city' => 'Antwerpen',
        'sports' => [SessionSport::Padel->value],
        'is_session_venue' => false,
        'has_corner' => true,
    ]);
    $survivor = Club::factory()->create([
        'name' => 'Padel Zuid',
        'city' => 'Gent',
        'sports' => [SessionSport::Tennis->value],
        'is_partner' => false,
        'is_session_venue' => true,
    ]);

    $session = CommunitySession::factory()->create(['club_id' => $duplicate->id]);
    $duplicateId = $duplicate->id;

    $this->actingAs($this->admin)
        ->post(route('admin.clubs.merge', ['locale' => 'nl', 'club' => $duplicate->id]), clubMergePayload($survivor->id, [
            'sources' => [
                'name' => 'survivor',
                'city' => 'duplicate',
            ],
            'is_partner' => true,
            'is_session_venue' => true,
            'has_corner' => true,
            'sports' => [SessionSport::Padel->value, SessionSport::Tennis->value],
        ]))
        ->assertRedirect(route('admin.clubs.show', ['locale' => 'nl', 'club' => $survivor->id]));

    $survivor->refresh();

    expect(Club::query()->find($duplicateId))->toBeNull()
        ->and($session->fresh()->club_id)->toBe($survivor->id)
        ->and($survivor->name)->toBe('Padel Zuid')
        ->and($survivor->city)->toBe('Antwerpen')
        ->and($survivor->is_partner)->toBeTrue()
        ->and($survivor->is_session_venue)->toBeTrue()
        ->and($survivor->has_corner)->toBeTrue()
        ->and($survivor->sports)->toEqualCanonicalizing([
            SessionSport::Padel->value,
            SessionSport::Tennis->value,
        ]);
});

test('a merged club disappears from member search and admin index', function () {
    $member = User::factory()->create();
    $duplicate = Club::factory()->create(['name' => 'Padel Zuid Antwerpen']);
    $survivor = Club::factory()->create(['name' => 'Padel Zuid']);
    $duplicateId = $duplicate->id;

    $this->actingAs($this->admin)
        ->post(route('admin.clubs.merge', ['locale' => 'nl', 'club' => $duplicate->id]), clubMergePayload($survivor->id));

    expect(Club::query()->find($duplicateId))->toBeNull();

    $clubs = $this->actingAs($member)
        ->getJson(route('community.clubs.search', ['locale' => 'nl', 'q' => 'Padel Zuid']))
        ->assertOk()
        ->json('clubs');

    expect($clubs)->toHaveCount(1)
        ->and($clubs[0]['name'])->toBe('Padel Zuid');

    $this->actingAs($this->admin)
        ->get(route('admin.clubs.index', ['locale' => 'nl', 'search' => 'Padel Zuid Antwerpen']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('clubs.data', 0));
});

test('a club cannot be merged into itself', function () {
    $club = Club::factory()->create();

    $this->actingAs($this->admin)
        ->post(route('admin.clubs.merge', ['locale' => 'nl', 'club' => $club->id]), clubMergePayload($club->id))
        ->assertSessionHasErrors('target_id');
});

test('merging remaps corner translations from the duplicate when chosen', function () {
    $duplicate = Club::factory()->publishedCorner()->create([
        'name' => 'Corner Dup',
        'corner_title' => 'Dup Title',
    ]);
    $survivor = Club::factory()->publishedCorner()->create([
        'name' => 'Corner Surv',
        'corner_title' => 'Surv Title',
    ]);

    $duplicate->translations()->create([
        'locale' => 'en',
        'column' => 'corner_title',
        'value' => 'Dup Title EN',
        'source_hash' => $duplicate->translationSourceHash('corner_title'),
    ]);
    $survivor->translations()->create([
        'locale' => 'en',
        'column' => 'corner_title',
        'value' => 'Surv Title EN',
        'source_hash' => $survivor->translationSourceHash('corner_title'),
    ]);

    $duplicateId = $duplicate->id;

    $this->actingAs($this->admin)
        ->post(route('admin.clubs.merge', ['locale' => 'nl', 'club' => $duplicate->id]), clubMergePayload($survivor->id, [
            'sources' => [
                'corner_title' => 'duplicate',
                'corner_body' => 'survivor',
                'corner_location' => 'survivor',
            ],
            'has_corner' => true,
            'corner_published' => true,
        ]))
        ->assertRedirect();

    $survivor->refresh();
    $survivor->load('translations');

    expect(Club::query()->find($duplicateId))->toBeNull()
        ->and($survivor->corner_title)->toBe('Dup Title')
        ->and(
            $survivor->translations
                ->firstWhere(fn (Translation $row): bool => $row->locale === 'en' && $row->column === 'corner_title')
                ?->value
        )->toBe('Dup Title EN')
        ->and(
            Translation::query()
                ->where('translatable_type', (new Club)->getMorphClass())
                ->where('translatable_id', $duplicateId)
                ->exists()
        )->toBeFalse();
});

test('merging keeps the survivor image when image_source is survivor', function () {
    Storage::fake('public');

    $duplicate = Club::factory()->create([
        'name' => 'Dup Image',
        'image_path' => 'clubs/dup.jpg',
    ]);
    $survivor = Club::factory()->create([
        'name' => 'Surv Image',
        'image_path' => 'clubs/surv.jpg',
    ]);

    Storage::disk('public')->put('clubs/dup.jpg', 'dup');
    Storage::disk('public')->put('clubs/surv.jpg', 'surv');

    $this->actingAs($this->admin)
        ->post(route('admin.clubs.merge', ['locale' => 'nl', 'club' => $duplicate->id]), clubMergePayload($survivor->id, [
            'image_source' => 'survivor',
        ]))
        ->assertRedirect();

    expect($survivor->fresh()->image_path)->toBe('clubs/surv.jpg');
    Storage::disk('public')->assertExists('clubs/surv.jpg');
    Storage::disk('public')->assertMissing('clubs/dup.jpg');
});

test('merging adopts the duplicate image when image_source is duplicate', function () {
    Storage::fake('public');

    $duplicate = Club::factory()->create([
        'name' => 'Dup Image Keep',
        'image_path' => 'clubs/dup-keep.jpg',
    ]);
    $survivor = Club::factory()->create([
        'name' => 'Surv Image Drop',
        'image_path' => 'clubs/surv-drop.jpg',
    ]);

    Storage::disk('public')->put('clubs/dup-keep.jpg', 'dup');
    Storage::disk('public')->put('clubs/surv-drop.jpg', 'surv');

    $this->actingAs($this->admin)
        ->post(route('admin.clubs.merge', ['locale' => 'nl', 'club' => $duplicate->id]), clubMergePayload($survivor->id, [
            'image_source' => 'duplicate',
        ]))
        ->assertRedirect();

    expect($survivor->fresh()->image_path)->toBe('clubs/dup-keep.jpg');
    Storage::disk('public')->assertExists('clubs/dup-keep.jpg');
    Storage::disk('public')->assertMissing('clubs/surv-drop.jpg');
});

test('members cannot reach the admin club dashboard', function () {
    $member = User::factory()->create();

    $this->actingAs($member)
        ->get(route('admin.clubs.index', ['locale' => 'nl']))
        ->assertForbidden();
});
