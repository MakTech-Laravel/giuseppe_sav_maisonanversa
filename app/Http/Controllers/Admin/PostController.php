<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Post\StorePostRequest;
use App\Http\Requests\Post\UpdatePostRequest;
use App\Models\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PostController extends Controller
{
    public function index(Request $request, string $locale): Response
    {
        $search = trim((string) $request->query('search', ''));

        $posts = Post::query()
            ->withCount('attachments')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where('title', 'like', "%{$search}%");
            })
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('admin/posts/index', [
            'posts' => $posts,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function create(string $locale): Response
    {
        return Inertia::render('admin/posts/create');
    }

    public function store(StorePostRequest $request, string $locale): RedirectResponse
    {
        $post = Post::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Post created successfully.']);

        return redirect()->route('admin.posts.show', ['post' => $post]);
    }

    public function show(string $locale, Post $post): Response
    {
        $post->load('attachments');

        return Inertia::render('admin/posts/show', [
            'post' => $post,
        ]);
    }

    public function edit(string $locale, Post $post): Response
    {
        return Inertia::render('admin/posts/edit', [
            'post' => $post,
        ]);
    }

    public function update(UpdatePostRequest $request, string $locale, Post $post): RedirectResponse
    {
        $post->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Post updated successfully.']);

        return redirect()->route('admin.posts.show', ['post' => $post]);
    }

    public function destroy(string $locale, Post $post): RedirectResponse
    {
        $post->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Post deleted successfully.']);

        return redirect()->route('admin.posts.index');
    }
}
