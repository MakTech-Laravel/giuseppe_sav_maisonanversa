<?php

use App\Jobs\TranslateModelJob;
use App\Models\CommunityPost;
use App\Models\Product;
use App\Models\User;
use App\Services\Translation\DeepLTranslator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

function fakeDeepLTranslations(): void
{
    config(['services.deepl.key' => 'test-key:fx']);

    Http::fake(function ($request) {
        $target = strtoupper((string) $request->data()['target_lang']);
        $texts = $request->data()['text'];
        $texts = is_array($texts) ? $texts : [$texts];

        return Http::response([
            'translations' => array_map(
                fn (string $text): array => ['text' => $target.' '.$text],
                $texts,
            ),
        ]);
    });
}

test('community posts auto-detect the content column and skip status', function () {
    $post = CommunityPost::factory()->create(['content' => 'Hallo huis']);

    expect($post->translatableColumns())
        ->toBe(['content'])
        ->not->toContain('status')
        ->not->toContain('email');
});

test('products use the explicit translatable name column', function () {
    $product = Product::query()->where('slug', Product::FOUNDING_SLUG)->first();

    expect($product->translatableColumns())
        ->toBe(['name', 'expected_delivery_label']);
});

test('deepl uses the free host for keys ending in fx', function () {
    config([
        'services.deepl.key' => 'abc:fx',
        'services.deepl.host' => null,
    ]);

    expect(app(DeepLTranslator::class)->host())->toBe(DeepLTranslator::FREE_HOST);

    config(['services.deepl.key' => 'paid-key']);

    expect(app(DeepLTranslator::class)->host())->toBe(DeepLTranslator::PAID_HOST);

    config(['services.deepl.host' => 'https://example.test']);

    expect(app(DeepLTranslator::class)->host())->toBe('https://example.test');
});

test('saving a community post stores english and french translations', function () {
    fakeDeepLTranslations();

    $post = CommunityPost::factory()->create(['content' => 'Hallo huis']);

    expect($post->translations()->count())->toBe(2);

    app()->setLocale('en');
    expect($post->fresh()->translated('content'))->toBe('EN Hallo huis');

    app()->setLocale('fr');
    expect($post->fresh()->translated('content'))->toBe('FR Hallo huis');

    app()->setLocale('nl');
    expect($post->fresh()->translated('content'))->toBe('Hallo huis');
});

test('unchanged source text does not call deepl again', function () {
    fakeDeepLTranslations();

    $post = CommunityPost::factory()->create(['content' => 'Hallo huis']);

    Http::recorded();
    $first = count(Http::recorded());

    $post->update(['is_official' => true]);

    expect(count(Http::recorded()))->toBe($first);

    $post->update(['content' => 'Hallo huis']);

    expect(count(Http::recorded()))->toBe($first);
});

test('changing source text retranslates', function () {
    fakeDeepLTranslations();

    $post = CommunityPost::factory()->create(['content' => 'Hallo huis']);
    $first = count(Http::recorded());

    $post->update(['content' => 'Nieuwe tekst']);

    expect(count(Http::recorded()))->toBeGreaterThan($first);
    expect($post->fresh()->translated('content', 'en'))->toBe('EN Nieuwe tekst');
});

test('translated content falls back to dutch when a locale row is missing', function () {
    config(['services.deepl.key' => null]);

    $post = CommunityPost::factory()->create(['content' => 'Alleen Nederlands']);

    expect($post->translations()->count())->toBe(0)
        ->and($post->translated('content', 'en'))->toBe('Alleen Nederlands');
});

test('the community feed exposes translated copy for the request locale', function () {
    fakeDeepLTranslations();

    $user = User::factory()->create();
    CommunityPost::factory()->create(['content' => 'Hallo huis']);

    $this->actingAs($user)
        ->get(route('maison.community', ['locale' => 'en']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('posts.data.0.content', 'EN Hallo huis')
        );
});

test('the translations unique index name fits mysql identifier length', function () {
    $source = file_get_contents(database_path('migrations/2026_08_15_031856_create_translations_table.php'));

    expect($source)->toContain('translations_morph_locale_column_unique');
    expect(strlen('translations_morph_locale_column_unique'))->toBeLessThanOrEqual(64);

    $default = 'translations_translatable_type_translatable_id_locale_column_unique';
    expect(strlen($default))->toBeGreaterThan(64);
});

test('translate jobs are unique per model', function () {
    Queue::fake();

    $post = CommunityPost::factory()->create(['content' => 'Hallo']);

    Queue::assertPushed(TranslateModelJob::class, function (TranslateModelJob $job) use ($post): bool {
        return $job->uniqueId() === CommunityPost::class.':'.$post->id;
    });
});
