<?php

use App\Enums\RoleEnum;
use App\Models\LegalPage;
use App\Models\User;
use App\Services\Translation\DeepLTranslator;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);

    $this->admin = User::factory()->admin()->create();
    $this->admin->assignRole(RoleEnum::SUPER_ADMIN->value);
    $this->admin->syncTypeFromRoles();
});

test('staff can open the legal page preview', function () {
    $page = LegalPage::query()->where('slug', 'privacy')->firstOrFail();

    $this->actingAs($this->admin)
        ->get(route('admin.legal-pages.show', ['locale' => 'nl', 'legalPage' => $page->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $inertia) => $inertia
            ->component('admin/legal-pages/show')
            ->where('page.slug', 'privacy')
            ->has('translations.nl.body')
            ->has('translations.en.body')
            ->has('translations.fr.body'));
});

test('staff can update legal page translations without overwriting source via deepl', function () {
    $page = LegalPage::query()->where('slug', 'privacy')->firstOrFail();
    $source = '<h2>Privacybeleid</h2><p>Nederlandse bron.</p>';
    $page->update(['body' => $source]);

    $this->actingAs($this->admin)
        ->put(route('admin.legal-pages.translations.update', ['locale' => 'nl', 'legalPage' => $page->id]), [
            'nl' => ['body' => '<h2>Privacybeleid</h2><p>Aangepaste bron.</p>'],
            'en' => ['body' => '<h2>Privacy policy</h2><p>Custom English.</p>'],
            'fr' => ['body' => '<h2>Politique de confidentialité</h2><p>Français personnalisé.</p>'],
        ])
        ->assertRedirect(route('admin.legal-pages.show', ['locale' => 'nl', 'legalPage' => $page->id]));

    $page->refresh();

    expect($page->body)
        ->toContain('<p>Aangepaste bron.</p>')
        ->and($page->translated('body', 'en'))->toContain('Custom English.')
        ->and($page->translated('body', 'fr'))->toContain('Français personnalisé.');
});

test('staff can retranslate a legal page locale from source', function () {
    fakeDeepLTranslations();

    $page = LegalPage::query()->where('slug', 'care')->firstOrFail();
    $page->update(['body' => '<h2>Zorg</h2><p>Brontekst.</p>']);
    $page->translations()->updateOrCreate(
        ['locale' => 'en', 'column' => 'body'],
        ['value' => '<p>Keep EN</p>', 'source_hash' => $page->translationSourceHash('body')],
    );
    $page->translations()->updateOrCreate(
        ['locale' => 'fr', 'column' => 'body'],
        ['value' => '<p>Keep FR</p>', 'source_hash' => $page->translationSourceHash('body')],
    );

    $this->actingAs($this->admin)
        ->post(route('admin.legal-pages.translate', ['locale' => 'nl', 'legalPage' => $page->id]), [
            'target_locale' => 'en',
        ])
        ->assertRedirect(route('admin.legal-pages.show', ['locale' => 'nl', 'legalPage' => $page->id]));

    $page->refresh();

    expect($page->translated('body', 'en'))->toContain('EN ')
        ->and($page->translated('body', 'fr'))->toContain('Keep FR');
});

test('members cannot preview legal pages', function () {
    $member = User::factory()->create();
    $page = LegalPage::query()->where('slug', 'privacy')->firstOrFail();

    $this->actingAs($member)
        ->get(route('admin.legal-pages.show', ['locale' => 'nl', 'legalPage' => $page->id]))
        ->assertForbidden();
});

test('staff can open the legal page editor', function () {
    $page = LegalPage::query()->where('slug', 'privacy')->firstOrFail();

    $this->actingAs($this->admin)
        ->get(route('admin.legal-pages.edit', ['locale' => 'nl', 'legalPage' => $page->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $inertia) => $inertia
            ->component('admin/legal-pages/edit')
            ->where('page.slug', 'privacy')
            ->where('page.body', fn (string $body): bool => str_contains($body, '<h2>')));
});

test('staff can update a legal page with html', function () {
    $page = LegalPage::query()->where('slug', 'privacy')->firstOrFail();

    $this->actingAs($this->admin)
        ->put(route('admin.legal-pages.update', ['locale' => 'nl', 'legalPage' => $page->id]), [
            'body' => '<h2>Privacybeleid</h2><p>Bijgewerkte tekst.</p>',
            'is_published' => true,
        ])
        ->assertRedirect(route('admin.legal-pages.show', ['locale' => 'nl', 'legalPage' => $page->id]));

    expect($page->fresh()->body)
        ->toContain('<h2>Privacybeleid</h2>')
        ->toContain('<p>Bijgewerkte tekst.</p>');
});

test('xss is stripped on save and empty payloads are rejected', function () {
    $page = LegalPage::query()->where('slug', 'terms')->firstOrFail();

    $this->actingAs($this->admin)
        ->put(route('admin.legal-pages.update', ['locale' => 'nl', 'legalPage' => $page->id]), [
            'body' => '<p>Veilig</p><script>alert(1)</script><a href="javascript:alert(1)">x</a>',
            'is_published' => true,
        ])
        ->assertRedirect();

    expect($page->fresh()->body)
        ->toContain('<p>Veilig</p>')
        ->not->toContain('script')
        ->not->toContain('javascript:');

    $this->actingAs($this->admin)
        ->put(route('admin.legal-pages.update', ['locale' => 'nl', 'legalPage' => $page->id]), [
            'body' => '<script>alert(1)</script>',
            'is_published' => true,
        ])
        ->assertSessionHasErrors('body');
});

test('guests cannot edit legal pages', function () {
    $page = LegalPage::query()->where('slug', 'care')->firstOrFail();

    $this->get(route('admin.legal-pages.edit', ['locale' => 'nl', 'legalPage' => $page->id]))
        ->assertRedirect();
});

test('members cannot edit legal pages', function () {
    $member = User::factory()->create();
    $page = LegalPage::query()->where('slug', 'privacy')->firstOrFail();

    $this->actingAs($member)
        ->get(route('admin.legal-pages.edit', ['locale' => 'nl', 'legalPage' => $page->id]))
        ->assertForbidden();
});

test('legal page translation requests html tag handling', function () {
    fakeDeepLTranslations();

    $page = LegalPage::query()->where('slug', 'shipping')->firstOrFail();
    $page->update([
        'body' => '<h2 class="keep">Hallo huis</h2><script>alert(1)</script><p>Tekst</p>',
    ]);

    Http::assertSent(function ($request): bool {
        $data = $request->data();
        $text = is_array($data['text'] ?? null) ? ($data['text'][0] ?? '') : '';

        return ($data['tag_handling'] ?? null) === 'html'
            && str_contains($text, '<h2 class="keep">')
            && ! str_contains(strtolower($text), 'script');
    });

    expect(app(DeepLTranslator::class)->translateMany(
        ['<h2>Hallo</h2>'],
        'EN',
        'NL',
        true,
    )[0])->toStartWith('EN ');
});
