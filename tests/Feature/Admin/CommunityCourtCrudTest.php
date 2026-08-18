<?php

use App\Enums\RoleEnum;
use App\Models\CommunityCourt;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);

    $this->admin = User::factory()->admin()->create();
    $this->admin->assignRole(RoleEnum::SUPER_ADMIN->value);
    $this->admin->syncTypeFromRoles();
});

test('staff can create a community court', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.courts.store', ['locale' => 'nl']), [
            'title' => 'Club Corner Antwerp',
            'body' => 'Founding club',
            'location' => 'Antwerpen',
            'lat' => 51.2194,
            'lng' => 4.4025,
            'sort_order' => 1,
            'is_published' => true,
        ])
        ->assertRedirect();

    expect(CommunityCourt::query()->where('title', 'Club Corner Antwerp')->exists())->toBeTrue();
});

test('staff can update a community court', function () {
    $court = CommunityCourt::factory()->create(['title' => 'Old Corner']);

    $this->actingAs($this->admin)
        ->put(route('admin.courts.update', ['locale' => 'nl', 'court' => $court->id]), [
            'title' => 'New Corner',
            'body' => 'Updated',
            'location' => 'Brussel',
            'lat' => 50.85,
            'lng' => 4.35,
            'sort_order' => 2,
            'is_published' => true,
        ])
        ->assertRedirect();

    expect($court->fresh()->title)->toBe('New Corner')
        ->and($court->fresh()->location)->toBe('Brussel');
});

test('staff can delete a community court', function () {
    $court = CommunityCourt::factory()->create();

    $this->actingAs($this->admin)
        ->delete(route('admin.courts.destroy', ['locale' => 'nl', 'court' => $court->id]))
        ->assertRedirect(route('admin.courts.index', ['locale' => 'nl']));

    expect(CommunityCourt::query()->whereKey($court->id)->exists())->toBeFalse();
});

test('courts index lists published state', function () {
    CommunityCourt::factory()->create([
        'title' => 'Heritage Court',
        'is_published' => true,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.courts.index', ['locale' => 'nl']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/courts/index')
            ->has('courts', 1)
            ->where('courts.0.title', 'Heritage Court')
            ->where('courts.0.is_published', true)
        );
});
