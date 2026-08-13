<?php

use App\Enums\RoleEnum;
use App\Models\Post;
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

test('staff can list posts', function () {
    Post::factory()->create(['title' => 'Maison note']);

    $this->actingAs($this->admin)
        ->get(route('admin.posts.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/posts/index')
            ->has('posts.data', 1)
        );
});

test('staff can create a post', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.posts.store'), [
            'title' => 'Heritage journal draft',
        ])
        ->assertRedirect();

    $post = Post::where('title', 'Heritage journal draft')->sole();

    expect($post)->not->toBeNull();
});

test('staff can update a post', function () {
    $post = Post::factory()->create(['title' => 'Old title']);

    $this->actingAs($this->admin)
        ->put(route('admin.posts.update', ['post' => $post]), [
            'title' => 'New title',
        ])
        ->assertRedirect(route('admin.posts.show', ['post' => $post]));

    expect($post->fresh()->title)->toBe('New title');
});

test('staff can delete a post', function () {
    $post = Post::factory()->create();

    $this->actingAs($this->admin)
        ->delete(route('admin.posts.destroy', ['post' => $post]))
        ->assertRedirect(route('admin.posts.index'));

    expect(Post::find($post->id))->toBeNull();
});

test('users without posts permission are forbidden', function () {
    $user = User::factory()->customer()->create();

    $this->actingAs($user)
        ->get(route('admin.posts.index'))
        ->assertForbidden();
});
