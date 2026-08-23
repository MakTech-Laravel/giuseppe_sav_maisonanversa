<?php

use App\Enums\RoleEnum;
use App\Jobs\TranslateModelJob;
use App\Models\CommunityPost;
use App\Models\User;
use App\Support\CommunityPostPresenter;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);

    $this->admin = User::factory()->admin()->create();
    $this->admin->assignRole(RoleEnum::SUPER_ADMIN->value);
    $this->admin->syncTypeFromRoles();
});

test('admin community index returns paginated posts with filters and excerpt', function () {
    $longContent = str_repeat('Community post content. ', 20);
    CommunityPost::factory()->create([
        'author_id' => $this->admin->id,
        'content' => $longContent,
        'status' => 'published',
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.community.index', ['locale' => 'nl']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/community/index')
            ->has('posts.data', 1)
            ->where('posts.current_page', 1)
            ->where('filters.search', '')
            ->where('filters.status', '')
            ->where('filters.type', '')
            ->where('posts.data.0.excerpt', CommunityPostPresenter::excerpt($longContent))
            ->where('posts.data.0.is_truncated', true)
            ->where('posts.data.0.can_edit', true)
        );
});

test('admin community index search filters by content and author name', function () {
    $author = User::factory()->create(['name' => 'Unique Author Name']);
    CommunityPost::factory()->create([
        'author_id' => $author->id,
        'content' => 'Searchable post body',
    ]);
    CommunityPost::factory()->create([
        'content' => 'Other unrelated content',
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.community.index', [
            'locale' => 'nl',
            'search' => 'Searchable',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('posts.data', 1)
            ->where('posts.data.0.content', 'Searchable post body')
        );

    $this->actingAs($this->admin)
        ->get(route('admin.community.index', [
            'locale' => 'nl',
            'search' => 'Unique Author',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('posts.data', 1)
            ->where('posts.data.0.author', 'Unique Author Name')
        );
});

test('admin community index filters by status and type', function () {
    CommunityPost::factory()->create([
        'status' => 'published',
        'is_official' => true,
    ]);
    CommunityPost::factory()->create([
        'status' => 'hidden',
        'is_official' => false,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.community.index', [
            'locale' => 'nl',
            'status' => 'hidden',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('posts.data', 1)
            ->where('posts.data.0.status', 'hidden')
        );

    $this->actingAs($this->admin)
        ->get(route('admin.community.index', [
            'locale' => 'nl',
            'type' => 'official',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('posts.data', 1)
            ->where('posts.data.0.is_official', true)
        );
});

test('authors can update their own community posts', function () {
    $post = CommunityPost::factory()->create([
        'author_id' => $this->admin->id,
        'content' => 'Original content',
    ]);

    $this->actingAs($this->admin)
        ->put(route('admin.community.posts.update', [
            'locale' => 'nl',
            'communityPost' => $post->id,
        ]), [
            'content' => 'Updated content',
        ])
        ->assertRedirect();

    expect($post->fresh()->content)->toBe('Updated content');
});

test('authors cannot update another users community post', function () {
    $other = User::factory()->create();
    $post = CommunityPost::factory()->create([
        'author_id' => $other->id,
        'content' => 'Original content',
    ]);

    $this->actingAs($this->admin)
        ->put(route('admin.community.posts.update', [
            'locale' => 'nl',
            'communityPost' => $post->id,
        ]), [
            'content' => 'Updated content',
        ])
        ->assertForbidden();

    expect($post->fresh()->content)->toBe('Original content');
});

test('admin community index shows translated excerpt for the active locale', function () {
    $post = CommunityPost::factory()->create([
        'author_id' => $this->admin->id,
        'content' => 'Bron tekst in het Nederlands',
        'status' => 'published',
    ]);

    $post->translations()->updateOrCreate(
        ['locale' => 'fr', 'column' => 'content'],
        [
            'value' => 'Texte source en français pour ce message',
            'source_hash' => $post->translationSourceHash('content'),
        ],
    );

    $this->actingAs($this->admin)
        ->get(route('admin.community.index', ['locale' => 'fr']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/community/index')
            ->where(
                'posts.data.0.excerpt',
                CommunityPostPresenter::excerpt('Texte source en français pour ce message'),
            )
            ->where('posts.data.0.content', 'Bron tekst in het Nederlands')
            ->has('locales', 3)
        );
});

test('authors can save community post translations', function () {
    $post = CommunityPost::factory()->create([
        'author_id' => $this->admin->id,
        'content' => 'Bron tekst',
    ]);

    $this->actingAs($this->admin)
        ->put(route('admin.community.posts.translations.update', [
            'locale' => 'nl',
            'communityPost' => $post->id,
        ]), [
            'nl' => ['content' => 'Nederlands'],
            'en' => ['content' => 'English'],
            'fr' => ['content' => 'Français'],
        ])
        ->assertRedirect();

    expect($post->translations()->where('locale', 'en')->where('column', 'content')->value('value'))
        ->toBe('English');
});

test('authors can retranslate their community posts', function () {
    Queue::fake();

    $post = CommunityPost::factory()->create([
        'author_id' => $this->admin->id,
        'content' => 'Hallo huis',
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.community.posts.translate', [
            'locale' => 'nl',
            'communityPost' => $post->id,
        ]), [
            'target_locale' => 'en',
        ])
        ->assertRedirect();

    Queue::assertPushed(TranslateModelJob::class);
});

test('moderators can hide another users community post', function () {
    $post = CommunityPost::factory()->create([
        'status' => 'published',
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.community.hide', [
            'locale' => 'nl',
            'communityPost' => $post->id,
        ]))
        ->assertRedirect();

    expect($post->fresh()->status)->toBe('hidden')
        ->and($post->fresh()->hidden_at)->not->toBeNull();
});
