<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreJournalArticleRequest;
use App\Http\Requests\Admin\UpdateJournalArticleRequest;
use App\Models\JournalArticle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class JournalArticleController extends Controller
{
    public function index(Request $request, string $locale): Response
    {
        $articles = JournalArticle::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return Inertia::render('admin/journal/index', [
            'articles' => $articles->map(fn (JournalArticle $article) => $this->summary($article)),
        ]);
    }

    public function create(Request $request, string $locale): Response
    {
        return Inertia::render('admin/journal/create');
    }

    public function store(StoreJournalArticleRequest $request, string $locale): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = $data['slug'] ?: Str::slug($data['title']);

        $article = JournalArticle::query()->create($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Journalartikel aangemaakt.')]);

        return redirect()->route('admin.journal.show', [
            'locale' => $locale,
            'article' => $article->id,
        ]);
    }

    public function show(Request $request, string $locale, JournalArticle $article): Response
    {
        return Inertia::render('admin/journal/show', [
            'article' => [
                ...$this->summary($article),
                'excerpt' => $article->translated('excerpt'),
                'body' => $article->translated('body'),
                'cover_path' => $article->cover_path,
                'category' => $article->translated('category'),
                'author' => $article->author,
                'date_label' => $article->translated('date_label'),
                'published_at' => $article->published_at?->toIso8601String(),
            ],
        ]);
    }

    public function edit(Request $request, string $locale, JournalArticle $article): Response
    {
        return Inertia::render('admin/journal/edit', [
            'article' => [
                'id' => (string) $article->id,
                'slug' => $article->slug,
                'title' => $article->title,
                'excerpt' => $article->excerpt,
                'body' => $article->body,
                'cover_path' => $article->cover_path,
                'category' => $article->category,
                'author' => $article->author,
                'date_label' => $article->date_label,
                'published_at' => $article->published_at?->format('Y-m-d\TH:i'),
                'sort_order' => $article->sort_order,
            ],
        ]);
    }

    public function update(UpdateJournalArticleRequest $request, string $locale, JournalArticle $article): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = $data['slug'] ?: Str::slug($data['title']);

        $article->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Journalartikel bijgewerkt.')]);

        return redirect()->route('admin.journal.show', [
            'locale' => $locale,
            'article' => $article->id,
        ]);
    }

    public function destroy(Request $request, string $locale, JournalArticle $article): RedirectResponse
    {
        $article->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Journalartikel verwijderd.')]);

        return redirect()->route('admin.journal.index', ['locale' => $locale]);
    }

    /**
     * @return array{id: string, slug: string, title: string, sort_order: int, published_at: string|null}
     */
    private function summary(JournalArticle $article): array
    {
        return [
            'id' => (string) $article->id,
            'slug' => $article->slug,
            'title' => $article->translated('title'),
            'sort_order' => $article->sort_order,
            'published_at' => $article->published_at?->toIso8601String(),
        ];
    }
}
