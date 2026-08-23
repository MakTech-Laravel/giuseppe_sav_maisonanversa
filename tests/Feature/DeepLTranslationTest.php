<?php

use App\Enums\EditionPieceStatus;
use App\Enums\OrderStatus;
use App\Enums\RoleEnum;
use App\Jobs\TranslateModelJob;
use App\Mail\OrderConfirmation;
use App\Models\CommunityComment;
use App\Models\CommunityCourt;
use App\Models\CommunityEvent;
use App\Models\CommunityPost;
use App\Models\CommunitySession;
use App\Models\EditionPiece;
use App\Models\JournalArticle;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Edition\EditionAllocator;
use App\Services\Edition\EditionInventory;
use App\Services\Translation\DeepLTranslator;
use App\Support\CommunityFeed;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

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
        ->toBe(['name', 'hero_subtitle', 'expected_delivery_label']);
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

    config(['services.deepl.host' => 'api-free.deepl.com']);

    expect(app(DeepLTranslator::class)->host())->toBe('https://api-free.deepl.com');
});

test('saving a community post stores translations for all locales', function () {
    fakeDeepLTranslations();

    $post = CommunityPost::factory()->create(['content' => 'Hallo huis']);

    expect($post->translations()->count())->toBe(3);

    app()->setLocale('en');
    expect($post->fresh()->translated('content'))->toBe('EN Hallo huis');

    app()->setLocale('fr');
    expect($post->fresh()->translated('content'))->toBe('FR Hallo huis');

    app()->setLocale('nl');
    expect($post->fresh()->translated('content'))->toBe('NL Hallo huis');
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
        return $job->uniqueId() === CommunityPost::class.':'.$post->id.':all';
    });
});

test('english requests use the british deepl target', function () {
    expect(app(DeepLTranslator::class)->targetLang('en'))->toBe('EN-GB')
        ->and(app(DeepLTranslator::class)->targetLang('fr'))->toBe('FR');
});

test('a missing deepl key keeps dutch and does not call the api', function () {
    config(['services.deepl.key' => null]);
    Http::fake();

    $translated = app(DeepLTranslator::class)->translate('Hallo huis', 'EN-GB');

    expect($translated)->toBe('Hallo huis');
    Http::assertNothingSent();
});

test('deepl retries after a 429 and then stores the translation', function () {
    config(['services.deepl.key' => 'test-key:fx']);

    Http::fake([
        '*' => Http::sequence()
            ->push(['message' => 'Too many requests'], 429)
            ->push(['translations' => [['text' => 'Hello home']]], 200),
    ]);

    expect(app(DeepLTranslator::class)->translate('Hallo huis', 'en'))->toBe('Hello home');
    Http::assertSentCount(2);
});

test('a deepl quota error does not persist translation rows', function () {
    Queue::fake();

    $post = CommunityPost::factory()->create(['content' => 'Hallo huis']);

    config(['services.deepl.key' => 'test-key:fx']);
    Http::fake([
        '*' => Http::response(['message' => 'Quota exceeded'], 456),
    ]);

    $job = new TranslateModelJob(CommunityPost::class, $post->id);
    $job->handle(app(DeepLTranslator::class));

    expect($post->translations()->count())->toBe(0);
});

test('admin event show uses translated copy while product edit stays dutch', function () {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);

    fakeDeepLTranslations();

    $admin = User::factory()->admin()->create();
    $admin->assignRole(RoleEnum::SUPER_ADMIN->value);
    $admin->syncTypeFromRoles();

    $event = CommunityEvent::factory()->create([
        'title' => 'Hallo salon',
        'location' => 'Antwerpen',
    ]);

    $product = Product::founding();
    TranslateModelJob::dispatchSync(Product::class, (int) $product->id);

    $this->actingAs($admin)
        ->get(route('admin.events.show', ['locale' => 'en', 'event' => $event->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('event.title', 'EN Hallo salon')
            ->where('event.location', 'EN Antwerpen')
        );

    $this->actingAs($admin)
        ->get(route('admin.products.edit', ['locale' => 'en', 'product' => $product->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('product.name', $product->name)
        );
});

test('member heritage shows translated product copy', function () {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);

    fakeDeepLTranslations();

    $product = Product::founding();
    TranslateModelJob::dispatchSync(Product::class, (int) $product->id);

    $user = User::factory()->create();
    $user->assignRole(RoleEnum::FOUNDING_CIRCLE->value);
    $order = Order::factory()->forUser($user)->create();
    app(EditionAllocator::class)->allocate($order);
    $order->update(['status' => OrderStatus::Paid]);

    $this->actingAs($user)
        ->get(route('member.heritage', ['locale' => 'en']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('heritage.productName', 'EN '.$product->name)
        );
});

test('the public verify page uses the translated product name', function () {
    fakeDeepLTranslations();

    $product = Product::founding();
    TranslateModelJob::dispatchSync(Product::class, (int) $product->id);

    $piece = EditionPiece::query()
        ->where('product_id', $product->id)
        ->whereNotNull('verification_token')
        ->first();

    $this->get(route('maison.verify', ['locale' => 'en', 'token' => $piece->verification_token]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('piece.productName', 'EN '.$product->name)
        );
});

test('checkout sold-out errors use the translated product name', function () {
    fakeDeepLTranslations();

    $product = Product::founding();
    TranslateModelJob::dispatchSync(Product::class, (int) $product->id);

    EditionPiece::query()
        ->where('status', EditionPieceStatus::Available)
        ->update(['status' => EditionPieceStatus::Allocated]);

    app(EditionInventory::class)->bust();

    $this->from(route('maison.home', ['locale' => 'en']))
        ->post(route('maison.checkout.store', ['locale' => 'en']), [
            'name' => 'Buyer',
            'email' => 'soldout@example.com',
        ])
        ->assertSessionHasErrors('checkout');

    expect(session('errors')->first('checkout'))->toContain('EN '.$product->name);
});

test('order confirmation mail uses the order locale for the product name', function () {
    fakeDeepLTranslations();

    $product = Product::founding();
    TranslateModelJob::dispatchSync(Product::class, (int) $product->id);

    $order = Order::factory()->create(['locale' => 'en']);
    $mailable = new OrderConfirmation($order->fresh(['product']));

    expect($mailable->envelope()->subject)->toContain('EN '.$product->name);
    $mailable->assertSeeInHtml('EN '.$product->name);
});

test('community events translate title description and location but not thumbnail', function () {
    $event = CommunityEvent::factory()->create();

    expect($event->translatableColumns())
        ->toEqualCanonicalizing(['title', 'description', 'location'])
        ->not->toContain('thumbnail')
        ->not->toContain('email')
        ->not->toContain('url');
});

test('creating a community event queues TranslateModelJob', function () {
    Queue::fake();

    $event = CommunityEvent::factory()->create(['title' => 'Salon avond']);

    Queue::assertPushed(TranslateModelJob::class, function (TranslateModelJob $job) use ($event): bool {
        return $job->uniqueId() === CommunityEvent::class.':'.$event->id.':all';
    });
});

test('community courts auto-detect title body and location', function () {
    $court = CommunityCourt::factory()->create();

    expect($court->translatableColumns())
        ->toEqualCanonicalizing(['title', 'body', 'location'])
        ->not->toContain('lat')
        ->not->toContain('lng');
});

test('journal articles translate title excerpt body category and date label', function () {
    $article = JournalArticle::factory()->create();

    expect($article->translatableColumns())
        ->toEqualCanonicalizing(['title', 'excerpt', 'body', 'category', 'date_label'])
        ->not->toContain('slug')
        ->not->toContain('author')
        ->not->toContain('cover_path');
});

test('community sessions translate notes and location but not level', function () {
    $session = CommunitySession::factory()->create();

    expect($session->translatableColumns())
        ->toEqualCanonicalizing(['location', 'notes'])
        ->not->toContain('level')
        ->not->toContain('url');
});

test('community comments are translated for all locales like faqs', function () {
    fakeDeepLTranslations();

    $comment = CommunityComment::factory()->create(['body' => 'Mooi bericht']);

    expect($comment->translations()->count())->toBe(3)
        ->and($comment->translated('body', 'nl'))->toBe('NL Mooi bericht')
        ->and($comment->translated('body', 'en'))->toBe('EN Mooi bericht')
        ->and($comment->translated('body', 'fr'))->toBe('FR Mooi bericht');
});

test('the community feed exposes translated comment bodies', function () {
    fakeDeepLTranslations();

    $user = User::factory()->create();
    $post = CommunityPost::factory()->create(['content' => 'Hallo huis']);
    CommunityComment::factory()->create([
        'community_post_id' => $post->id,
        'author_id' => $user->id,
        'body' => 'Mooi bericht',
    ]);

    $this->actingAs($user)
        ->get(route('maison.community', ['locale' => 'en']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('posts.data.0.comments.0.body', 'EN Mooi bericht')
        );
});

test('community feed uses route locale when app locale differs', function () {
    fakeDeepLTranslations();

    $user = User::factory()->create();
    $post = CommunityPost::factory()->create(['content' => 'Hallo huis']);
    CommunityComment::factory()->create([
        'community_post_id' => $post->id,
        'author_id' => $user->id,
        'body' => 'Mooi bericht',
    ]);

    app()->setLocale('nl');

    $request = Request::create('/en/community', 'GET');
    $request->setUserResolver(fn () => $user);

    $route = app('router')->getRoutes()->getByName('maison.community');
    $request->setRouteResolver(function () use ($route) {
        $route->bind(new Request);
        $route->setParameter('locale', 'en');

        return $route;
    });

    $paginator = CommunityFeed::paginate($request);

    expect($paginator->items()[0]['content'])->toBe('EN Hallo huis')
        ->and($paginator->items()[0]['comments'][0]['body'])->toBe('EN Mooi bericht');
});

test('translation retry queues jobs for a specific model id', function () {
    $first = CommunityPost::factory()->create(['content' => 'Eerste']);
    $second = CommunityPost::factory()->create(['content' => 'Tweede']);

    Queue::fake();

    $this->artisan('translation:retry', [
        'model' => 'communitypost',
        'id' => $first->id,
    ])->assertSuccessful();

    expect(Queue::pushed(
        TranslateModelJob::class,
        fn (TranslateModelJob $job): bool => $job->modelClass === CommunityPost::class && $job->modelId === $first->id,
    )->count())->toBe(1)
        ->and(Queue::pushed(
            TranslateModelJob::class,
            fn (TranslateModelJob $job): bool => $job->modelClass === CommunityPost::class && $job->modelId === $second->id,
        )->count())->toBe(0);
});
