<?php

use App\Enums\RoleEnum;
use App\Models\DressingItem;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);

    $this->admin = User::factory()->admin()->create();
    $this->admin->assignRole(RoleEnum::SUPER_ADMIN->value);
    $this->admin->syncTypeFromRoles();
});

test('staff can view the dressing items index', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.dressing-items.index', ['locale' => 'nl']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/dressing-items/index')
            ->has('items', 6)
        );
});

test('staff can create a dressing item with a cover image', function () {
    Storage::fake('public');

    $response = $this->actingAs($this->admin)
        ->post(route('admin.dressing-items.store', ['locale' => 'nl']), [
            'name' => 'Nieuw Item',
            'category' => 'Apparel',
            'description' => 'Een gloednieuw stuk voor de Kleedkamer.',
            'status' => 'available',
            'sort_order' => 5,
            'is_published' => true,
            'image' => UploadedFile::fake()->image('cover.jpg'),
        ]);

    $item = DressingItem::query()->where('name', 'Nieuw Item')->first();

    expect($item)->not->toBeNull()
        ->and($item->slug)->toBe('nieuw-item')
        ->and($item->category)->toBe('Apparel')
        ->and($item->status)->toBe('available')
        ->and($item->sort_order)->toBe(5)
        ->and($item->is_published)->toBeTrue()
        ->and($item->image_path)->not->toBeNull();

    Storage::disk('public')->assertExists($item->image_path);

    $response->assertRedirect(route('admin.dressing-items.show', [
        'locale' => 'nl',
        'dressingItem' => $item->id,
    ]));
});

test('staff can view a dressing item detail page', function () {
    $item = DressingItem::factory()->create([
        'name' => 'Detail Item',
        'category' => 'Accessories',
        'status' => 'available',
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.dressing-items.show', ['locale' => 'nl', 'dressingItem' => $item->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/dressing-items/show')
            ->where('item.id', (string) $item->id)
            ->where('item.name', 'Detail Item')
            ->where('item.category', 'Accessories')
            ->where('item.status', 'available')
        );
});

test('staff can update a dressing item and replace its image', function () {
    Storage::fake('public');

    $item = DressingItem::factory()->create([
        'name' => 'Old Name',
        'status' => 'coming_soon',
        'image_path' => null,
    ]);

    $this->actingAs($this->admin)
        ->put(route('admin.dressing-items.update', ['locale' => 'nl', 'dressingItem' => $item->id]), [
            'name' => 'New Name',
            'category' => $item->category,
            'description' => 'Bijgewerkte beschrijving',
            'status' => 'available',
            'sort_order' => $item->sort_order,
            'is_published' => true,
            'image' => UploadedFile::fake()->image('new-cover.jpg'),
        ])
        ->assertRedirect(route('admin.dressing-items.show', [
            'locale' => 'nl',
            'dressingItem' => $item->id,
        ]));

    $item->refresh();

    expect($item->name)->toBe('New Name')
        ->and($item->status)->toBe('available')
        ->and($item->description)->toBe('Bijgewerkte beschrijving')
        ->and($item->image_path)->not->toBeNull();

    Storage::disk('public')->assertExists($item->image_path);
});

test('staff can remove a dressing item image', function () {
    Storage::fake('public');
    $path = UploadedFile::fake()->image('existing.jpg')->store('dressing-items', 'public');

    $item = DressingItem::factory()->create([
        'image_path' => $path,
    ]);

    $this->actingAs($this->admin)
        ->put(route('admin.dressing-items.update', ['locale' => 'nl', 'dressingItem' => $item->id]), [
            'name' => $item->name,
            'category' => $item->category,
            'description' => $item->description,
            'status' => $item->status,
            'sort_order' => $item->sort_order,
            'is_published' => $item->is_published,
            'remove_image' => true,
        ])
        ->assertRedirect();

    expect($item->fresh()->image_path)->toBeNull();
    Storage::disk('public')->assertMissing($path);
});

test('staff can delete a dressing item', function () {
    $item = DressingItem::factory()->create();

    $this->actingAs($this->admin)
        ->delete(route('admin.dressing-items.destroy', ['locale' => 'nl', 'dressingItem' => $item->id]))
        ->assertRedirect(route('admin.dressing-items.index', ['locale' => 'nl']));

    expect(DressingItem::query()->whereKey($item->id)->exists())->toBeFalse();
});

test('unpublished dressing items are hidden from the public catalog', function () {
    DressingItem::factory()->create([
        'name' => 'Hidden Item',
        'is_published' => false,
    ]);

    $this->get(localized('maison.dressing'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('items', fn ($items) => collect($items)->pluck('name')->doesntContain('Hidden Item'))
        );
});

test('create and edit dressing item pages render', function () {
    $item = DressingItem::factory()->create();

    $this->actingAs($this->admin)
        ->get(route('admin.dressing-items.create', ['locale' => 'nl']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/dressing-items/create')
        );

    $this->actingAs($this->admin)
        ->get(route('admin.dressing-items.edit', ['locale' => 'nl', 'dressingItem' => $item->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/dressing-items/edit')
            ->where('item.id', (string) $item->id)
            ->where('item.name', $item->name)
        );
});
