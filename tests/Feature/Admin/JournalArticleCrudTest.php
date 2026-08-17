<?php

use App\Enums\RoleEnum;
use App\Models\JournalArticle;
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

test('staff can create a journal article', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.journal.store', ['locale' => 'nl']), [
            'slug' => 'nieuw-artikel',
            'title' => 'Nieuw artikel',
            'excerpt' => 'Korte intro',
            'body' => "Eerste alinea.\n\nTweede alinea.",
            'cover_path' => 'antwerp-cityscape',
            'category' => 'Erfgoed',
            'author' => 'Maison Anversa',
            'date_label' => 'Augustus 2026',
            'published_at' => now()->toDateTimeString(),
            'sort_order' => 99,
        ])
        ->assertRedirect();

    expect(JournalArticle::query()->where('slug', 'nieuw-artikel')->exists())->toBeTrue();
});

test('staff can update a journal article', function () {
    $article = JournalArticle::factory()->create(['title' => 'Oud']);

    $this->actingAs($this->admin)
        ->put(route('admin.journal.update', ['locale' => 'nl', 'article' => $article->id]), [
            'slug' => $article->slug,
            'title' => 'Nieuw',
            'excerpt' => $article->excerpt,
            'body' => $article->body,
            'cover_path' => $article->cover_path,
            'category' => $article->category,
            'author' => $article->author,
            'date_label' => $article->date_label,
            'published_at' => now()->toDateTimeString(),
            'sort_order' => 1,
        ])
        ->assertRedirect();

    expect($article->fresh()->title)->toBe('Nieuw');
});

test('staff can delete a journal article', function () {
    $article = JournalArticle::factory()->create();

    $this->actingAs($this->admin)
        ->delete(route('admin.journal.destroy', ['locale' => 'nl', 'article' => $article->id]))
        ->assertRedirect(route('admin.journal.index', ['locale' => 'nl']));

    expect(JournalArticle::query()->whereKey($article->id)->exists())->toBeFalse();
});

test('journal index lists articles', function () {
    JournalArticle::factory()->create([
        'slug' => 'smoke-artikel',
        'title' => 'Smoke artikel',
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.journal.index', ['locale' => 'nl']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/journal/index')
            ->has('articles')
            ->where('articles.0.title', fn ($title) => is_string($title) && $title !== '')
        );
});
