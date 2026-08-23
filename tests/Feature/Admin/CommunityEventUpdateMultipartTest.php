<?php

use App\Enums\RoleEnum;
use App\Models\CommunityEvent;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);

    $this->admin = User::factory()->admin()->create();
    $this->admin->assignRole(RoleEnum::SUPER_ADMIN->value);
    $this->admin->syncTypeFromRoles();
});

test('staff can update an event via multipart post with method spoofing', function () {
    Storage::fake('public');

    $event = CommunityEvent::factory()->create([
        'title' => 'Old Title',
        'capacity' => 10,
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.events.update', ['locale' => 'nl', 'event' => $event->id]), [
            '_method' => 'put',
            'title' => 'Updated Title',
            'description' => 'Updated description',
            'starts_at' => now()->addDays(5)->format('Y-m-d\TH:i'),
            'location' => 'Ghent',
            'capacity' => '24',
            'remove_thumbnail' => '0',
            'thumbnail' => UploadedFile::fake()->image('cover.jpg'),
        ])
        ->assertRedirect(route('admin.events.show', [
            'locale' => 'nl',
            'event' => $event->id,
        ]));

    $event->refresh();

    expect($event->title)->toBe('Updated Title')
        ->and($event->location)->toBe('Ghent')
        ->and($event->capacity)->toBe(24)
        ->and($event->thumbnail)->not->toBeNull();

    Storage::disk('public')->assertExists($event->thumbnail);
});

test('staff can clear capacity with empty multipart value', function () {
    $event = CommunityEvent::factory()->create([
        'title' => 'Has Capacity',
        'capacity' => 20,
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.events.update', ['locale' => 'nl', 'event' => $event->id]), [
            '_method' => 'put',
            'title' => 'Has Capacity',
            'description' => 'No limit now',
            'starts_at' => now()->addDays(5)->format('Y-m-d\TH:i'),
            'location' => 'Antwerp',
            'capacity' => '',
            'remove_thumbnail' => '0',
        ])
        ->assertRedirect();

    expect($event->fresh()->capacity)->toBeNull();
});
