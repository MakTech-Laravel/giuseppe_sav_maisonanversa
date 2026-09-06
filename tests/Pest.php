<?php

use App\Enums\EditionPieceStatus;
use App\Models\EditionPiece;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DressingItemSeeder;
use Database\Seeders\EditionPieceSeeder;
use Database\Seeders\FaqSeeder;
use Database\Seeders\JournalArticleSeeder;
use Database\Seeders\LegalPageSeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\SeoMetaSeeder;
use Database\Seeders\SiteSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function () {
        $this->seed([
            ProductSeeder::class,
            EditionPieceSeeder::class,
            JournalArticleSeeder::class,
            SiteSettingSeeder::class,
            FaqSeeder::class,
            DressingItemSeeder::class,
            LegalPageSeeder::class,
            SeoMetaSeeder::class,
        ]);
    })
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function defaultLocale(): string
{
    return config('maison.default_locale');
}

function localized(string $name, array $parameters = [], bool $absolute = true): string
{
    return route($name, ['locale' => defaultLocale(), ...$parameters], $absolute);
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function actingAsCheckoutUser(array $attributes = []): User
{
    $user = User::factory()->create($attributes);
    test()->actingAs($user);

    return $user;
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function checkoutPayload(array $overrides = []): array
{
    $product = Product::founding();

    $editionPieceId = $overrides['edition_piece_id'] ?? EditionPiece::query()
        ->where('product_id', $product?->id)
        ->where('status', EditionPieceStatus::Available)
        ->orderBy('edition_number')
        ->value('id');

    return [
        'name' => 'Test Buyer',
        'email' => 'buyer@example.com',
        'shipping_line1' => 'Meir 1',
        'shipping_city' => 'Antwerpen',
        'shipping_postal_code' => '2000',
        'shipping_country' => 'BE',
        'edition_piece_id' => $editionPieceId,
        'gift_wrap' => false,
        ...$overrides,
    ];
}

function fakeDeepLTranslations(): void
{
    config(['services.deepl.key' => 'test-key:fx']);

    Http::fake(function ($request) {
        $target = (string) $request->data()['target_lang'];
        $label = match (true) {
            str_starts_with($target, 'EN') => 'EN',
            str_starts_with($target, 'FR') => 'FR',
            str_starts_with($target, 'NL') => 'NL',
            default => $target,
        };
        $texts = $request->data()['text'];
        $texts = is_array($texts) ? $texts : [$texts];

        return Http::response([
            'translations' => array_map(
                fn (string $text): array => ['text' => $label.' '.$text],
                $texts,
            ),
        ]);
    });
}
