<?php

use App\Enums\RoleEnum;
use App\Models\SeoMeta;
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

test('staff can view the seo meta index', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.seo-metas.index', ['locale' => 'nl']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/seo-metas/index')
            ->has('rows')
            ->has('rows.0.id')
            ->has('rows.0.page_key')
            ->has('rows.0.title')
            ->has('rows.0.description')
            ->has('rows.0.has_pending_translations'));
});

test('staff can open the seo meta details page', function () {
    $meta = SeoMeta::query()->where('page_key', 'home')->firstOrFail();

    $this->actingAs($this->admin)
        ->get(route('admin.seo-metas.show', ['locale' => 'nl', 'seoMeta' => $meta->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/seo-metas/show')
            ->where('row.page_key', 'home')
            ->has('translations.nl.title')
            ->has('translations.en.title')
            ->has('translations.fr.description')
            ->has('translationStatus.en.title'));
});

test('staff can open the seo meta editor', function () {
    $meta = SeoMeta::query()->where('page_key', 'contact')->firstOrFail();

    $this->actingAs($this->admin)
        ->get(route('admin.seo-metas.edit', ['locale' => 'nl', 'seoMeta' => $meta->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/seo-metas/edit')
            ->where('row.page_key', 'contact')
            ->where('row.title', $meta->title));
});

test('staff can update dutch seo source and requeue deepl', function () {
    fakeDeepLTranslations();

    $meta = SeoMeta::query()->where('page_key', 'story')->firstOrFail();

    $this->actingAs($this->admin)
        ->put(route('admin.seo-metas.update', ['locale' => 'nl', 'seoMeta' => $meta->id]), [
            'title' => 'Ons Verhaal — bijgewerkt',
            'description' => 'Bijgewerkte verhaalbeschrijving.',
        ])
        ->assertRedirect(route('admin.seo-metas.show', ['locale' => 'nl', 'seoMeta' => $meta->id]));

    $meta->refresh();

    expect($meta->title)->toBe('Ons Verhaal — bijgewerkt')
        ->and($meta->translated('title', 'en'))->toBe('EN Ons Verhaal — bijgewerkt')
        ->and($meta->translated('description', 'fr'))->toBe('FR Bijgewerkte verhaalbeschrijving.');
});

test('staff can manually update seo translations without overwriting source via deepl', function () {
    $meta = SeoMeta::query()->where('page_key', 'house')->firstOrFail();
    $meta->update([
        'title' => 'Het Huis — bron',
        'description' => 'Nederlandse huisbeschrijving.',
    ]);

    $this->actingAs($this->admin)
        ->put(route('admin.seo-metas.translations.update', ['locale' => 'nl', 'seoMeta' => $meta->id]), [
            'nl' => [
                'title' => 'Het Huis — aangepast',
                'description' => 'Aangepaste Nederlandse beschrijving.',
            ],
            'en' => [
                'title' => 'The House — custom',
                'description' => 'Custom English description.',
            ],
            'fr' => [
                'title' => 'La Maison — personnalisé',
                'description' => 'Description française personnalisée.',
            ],
        ])
        ->assertRedirect(route('admin.seo-metas.show', ['locale' => 'nl', 'seoMeta' => $meta->id]));

    $meta->refresh();

    expect($meta->title)->toBe('Het Huis — aangepast')
        ->and($meta->description)->toBe('Aangepaste Nederlandse beschrijving.')
        ->and($meta->translated('title', 'en'))->toBe('The House — custom')
        ->and($meta->translated('description', 'fr'))->toBe('Description française personnalisée.');
});

test('staff can retranslate a single seo locale from source', function () {
    fakeDeepLTranslations();

    $meta = SeoMeta::query()->where('page_key', 'care')->firstOrFail();
    $meta->update([
        'title' => 'Zorg — bron',
        'description' => 'Zorgbeschrijving bron.',
    ]);
    $meta->translations()->updateOrCreate(
        ['locale' => 'en', 'column' => 'title'],
        ['value' => 'Keep EN title', 'source_hash' => $meta->translationSourceHash('title')],
    );
    $meta->translations()->updateOrCreate(
        ['locale' => 'en', 'column' => 'description'],
        ['value' => 'Keep EN description', 'source_hash' => $meta->translationSourceHash('description')],
    );
    $meta->translations()->updateOrCreate(
        ['locale' => 'fr', 'column' => 'title'],
        ['value' => 'Keep FR title', 'source_hash' => $meta->translationSourceHash('title')],
    );
    $meta->translations()->updateOrCreate(
        ['locale' => 'fr', 'column' => 'description'],
        ['value' => 'Keep FR description', 'source_hash' => $meta->translationSourceHash('description')],
    );

    $this->actingAs($this->admin)
        ->post(route('admin.seo-metas.translate', ['locale' => 'nl', 'seoMeta' => $meta->id]), [
            'target_locale' => 'en',
        ])
        ->assertRedirect(route('admin.seo-metas.show', ['locale' => 'nl', 'seoMeta' => $meta->id]));

    $meta->refresh();

    expect($meta->translated('title', 'en'))->toBe('EN Zorg — bron')
        ->and($meta->translated('description', 'en'))->toBe('EN Zorgbeschrijving bron.')
        ->and($meta->translated('title', 'fr'))->toBe('Keep FR title')
        ->and($meta->translated('description', 'fr'))->toBe('Keep FR description');
});

test('staff can queue deepl retranslation for all seo locales', function () {
    fakeDeepLTranslations();

    $meta = SeoMeta::query()->where('page_key', 'circle')->firstOrFail();
    $meta->update([
        'title' => 'Circle bron',
        'description' => 'Circle beschrijving.',
    ]);
    $meta->translations()->delete();

    $this->actingAs($this->admin)
        ->post(route('admin.seo-metas.translate', ['locale' => 'nl', 'seoMeta' => $meta->id]))
        ->assertRedirect(route('admin.seo-metas.show', ['locale' => 'nl', 'seoMeta' => $meta->id]));

    expect($meta->fresh()->translations()->count())->toBe(4);
});

test('members cannot open seo meta admin pages', function () {
    $member = User::factory()->create();
    $meta = SeoMeta::query()->where('page_key', 'home')->firstOrFail();

    $this->actingAs($member)
        ->get(route('admin.seo-metas.show', ['locale' => 'nl', 'seoMeta' => $meta->id]))
        ->assertForbidden();
});
