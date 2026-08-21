<?php

use App\Models\JournalArticle;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

test('seo:generate warms the sitemap cache', function () {
    config(['maison.seo.sitemap_cache_seconds' => 3600]);

    Cache::forget('maison:sitemap:xml');

    $this->artisan('seo:generate')
        ->assertSuccessful();

    expect(Cache::has('maison:sitemap:xml'))->toBeTrue();
});

test('seo:generate --write-files writes sitemap and robots to storage', function () {
    $dir = storage_path('app/seo');

    File::deleteDirectory($dir);

    $this->artisan('seo:generate', ['--write-files' => true])
        ->assertSuccessful();

    expect(File::exists($dir.'/sitemap.xml'))->toBeTrue()
        ->and(File::exists($dir.'/robots.txt'))->toBeTrue()
        ->and(File::get($dir.'/sitemap.xml'))->toContain('<urlset')
        ->and(File::get($dir.'/robots.txt'))->toContain('User-agent: *');

    File::deleteDirectory($dir);
});

test('journal article observer busts the sitemap cache', function () {
    config(['maison.seo.sitemap_cache_seconds' => 3600]);

    Cache::put('maison:sitemap:xml', '<dummy/>', 3600);
    expect(Cache::has('maison:sitemap:xml'))->toBeTrue();

    $article = JournalArticle::factory()->create();

    expect(Cache::has('maison:sitemap:xml'))->toBeFalse();

    Cache::put('maison:sitemap:xml', '<dummy/>', 3600);
    $article->delete();

    expect(Cache::has('maison:sitemap:xml'))->toBeFalse();
});

test('unpublished journal articles are excluded from sitemap', function () {
    $unpublished = JournalArticle::factory()->create([
        'slug' => 'draft-article-should-not-appear',
        'published_at' => null,
    ]);

    $body = $this->get('/sitemap.xml')->assertOk()->getContent();

    expect($body)->not->toContain('draft-article-should-not-appear');

    $unpublished->delete();
});
