<?php

use App\Enums\FaqContext;
use App\Enums\RoleEnum;
use App\Models\Faq;
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

test('staff can view the faq index with filters and pagination', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.faqs.index', ['locale' => 'nl']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/faqs/index')
            ->has('faqs.data')
            ->where('filters.search', '')
            ->where('filters.context', '')
            ->where('filters.status', '')
            ->where('filters.per_page', 15)
            ->has('perPageOptions', 5)
            ->has('contexts', 2)
        );
});

test('staff can filter faqs by search context and status', function () {
    Faq::factory()->product()->published()->create([
        'question' => 'Unieke productvraag over levering',
        'answer' => 'Productantwoord',
        'sort_order' => 99,
    ]);

    Faq::factory()->contact()->draft()->create([
        'question' => 'Unieke contactvraag over retour',
        'answer' => 'Contactantwoord',
        'sort_order' => 98,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.faqs.index', [
            'locale' => 'nl',
            'search' => 'Unieke productvraag',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/faqs/index')
            ->has('faqs.data', 1)
            ->where('faqs.data.0.question', 'Unieke productvraag over levering')
            ->where('filters.search', 'Unieke productvraag')
        );

    $this->actingAs($this->admin)
        ->get(route('admin.faqs.index', [
            'locale' => 'nl',
            'context' => FaqContext::Contact->value,
            'status' => 'draft',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('faqs.data', 1)
            ->where('faqs.data.0.question', 'Unieke contactvraag over retour')
            ->where('filters.context', FaqContext::Contact->value)
            ->where('filters.status', 'draft')
        );
});

test('staff can paginate faqs with a whitelisted per page value', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.faqs.index', [
            'locale' => 'nl',
            'per_page' => 10,
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.per_page', 10)
            ->where('faqs.per_page', 10)
        );

    $this->actingAs($this->admin)
        ->get(route('admin.faqs.index', [
            'locale' => 'nl',
            'per_page' => 999,
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.per_page', 15)
        );
});

test('staff can create a faq', function () {
    $response = $this->actingAs($this->admin)
        ->post(route('admin.faqs.store', ['locale' => 'nl']), [
            'context' => FaqContext::Product->value,
            'question' => 'Nieuwe FAQ vraag?',
            'answer' => 'Nieuwe FAQ antwoord.',
            'sort_order' => 12,
            'is_published' => true,
        ]);

    $faq = Faq::query()->where('question', 'Nieuwe FAQ vraag?')->first();

    expect($faq)->not->toBeNull()
        ->and($faq->context)->toBe(FaqContext::Product)
        ->and($faq->answer)->toBe('Nieuwe FAQ antwoord.')
        ->and($faq->sort_order)->toBe(12)
        ->and($faq->is_published)->toBeTrue();

    $response->assertRedirect(route('admin.faqs.show', [
        'locale' => 'nl',
        'faq' => $faq->id,
    ]));
});

test('staff can update a faq', function () {
    $faq = Faq::factory()->product()->create([
        'question' => 'Oude vraag',
        'answer' => 'Oud antwoord',
    ]);

    $this->actingAs($this->admin)
        ->put(route('admin.faqs.update', ['locale' => 'nl', 'faq' => $faq->id]), [
            'context' => FaqContext::Contact->value,
            'question' => 'Bijgewerkte vraag',
            'answer' => 'Bijgewerkt antwoord',
            'sort_order' => 3,
            'is_published' => false,
        ])
        ->assertRedirect(route('admin.faqs.show', ['locale' => 'nl', 'faq' => $faq->id]));

    $faq->refresh();

    expect($faq->context)->toBe(FaqContext::Contact)
        ->and($faq->question)->toBe('Bijgewerkte vraag')
        ->and($faq->answer)->toBe('Bijgewerkt antwoord')
        ->and($faq->sort_order)->toBe(3)
        ->and($faq->is_published)->toBeFalse();
});

test('staff can view a faq detail page', function () {
    $faq = Faq::factory()->contact()->published()->create([
        'question' => 'Detail FAQ vraag',
        'answer' => 'Detail FAQ antwoord',
        'sort_order' => 4,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.faqs.show', ['locale' => 'nl', 'faq' => $faq->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/faqs/show')
            ->where('faq.id', (string) $faq->id)
            ->where('faq.question', 'Detail FAQ vraag')
            ->where('faq.answer', 'Detail FAQ antwoord')
            ->where('faq.context', FaqContext::Contact->value)
            ->where('faq.sort_order', 4)
            ->where('faq.is_published', true)
            ->has('locales', 3)
            ->has('translations.nl')
            ->has('translations.en')
            ->has('translations.fr')
            ->has('translationStatus.nl')
            ->has('translationStatus.en')
            ->has('translationStatus.fr')
        );
});

test('creating a faq stores deepl translations for all locales', function () {
    fakeDeepLTranslations();

    $this->actingAs($this->admin)
        ->post(route('admin.faqs.store', ['locale' => 'nl']), [
            'context' => FaqContext::Product->value,
            'question' => 'DeepL FAQ vraag?',
            'answer' => 'DeepL FAQ antwoord.',
            'sort_order' => 0,
            'is_published' => true,
        ])
        ->assertRedirect();

    $faq = Faq::query()->where('question', 'DeepL FAQ vraag?')->firstOrFail();

    expect($faq->translations()->count())->toBe(6)
        ->and($faq->translated('question', 'nl'))->toBe('NL DeepL FAQ vraag?')
        ->and($faq->translated('question', 'en'))->toBe('EN DeepL FAQ vraag?')
        ->and($faq->translated('answer', 'fr'))->toBe('FR DeepL FAQ antwoord.');
});

test('creating a faq in english auto-detects and translates to all locales', function () {
    fakeDeepLTranslations();

    $this->actingAs($this->admin)
        ->post(route('admin.faqs.store', ['locale' => 'nl']), [
            'context' => FaqContext::Product->value,
            'question' => 'How long will shipping take?',
            'answer' => 'Standard shipping takes 3-5 days.',
            'sort_order' => 0,
            'is_published' => true,
        ])
        ->assertRedirect();

    $faq = Faq::query()->where('question', 'How long will shipping take?')->firstOrFail();

    expect($faq->translations()->count())->toBe(6)
        ->and($faq->translated('question', 'en'))->toBe('EN How long will shipping take?')
        ->and($faq->translated('question', 'fr'))->toBe('FR How long will shipping take?')
        ->and($faq->translated('question', 'nl'))->toBe('NL How long will shipping take?');
});

test('staff can manually update faq translations', function () {
    $faq = Faq::factory()->product()->create([
        'question' => 'Bron vraag',
        'answer' => 'Bron antwoord',
    ]);

    $this->actingAs($this->admin)
        ->put(route('admin.faqs.translations.update', ['locale' => 'nl', 'faq' => $faq->id]), [
            'nl' => [
                'question' => 'Custom NL question',
                'answer' => 'Custom NL answer',
            ],
            'en' => [
                'question' => 'Custom EN question',
                'answer' => 'Custom EN answer',
            ],
            'fr' => [
                'question' => 'Custom FR question',
                'answer' => 'Custom FR answer',
            ],
        ])
        ->assertRedirect(route('admin.faqs.show', ['locale' => 'nl', 'faq' => $faq->id]));

    $faq->refresh();

    expect($faq->question)->toBe('Bron vraag')
        ->and($faq->translated('question', 'nl'))->toBe('Custom NL question')
        ->and($faq->translated('question', 'en'))->toBe('Custom EN question')
        ->and($faq->translated('answer', 'fr'))->toBe('Custom FR answer');
});

test('editing faq source requeues deepl for all locales', function () {
    fakeDeepLTranslations();

    $faq = Faq::factory()->product()->create([
        'question' => 'Original source question',
        'answer' => 'Original source answer',
    ]);

    $this->actingAs($this->admin)
        ->put(route('admin.faqs.update', ['locale' => 'nl', 'faq' => $faq->id]), [
            'context' => FaqContext::Product->value,
            'question' => 'Updated source question',
            'answer' => 'Updated source answer',
            'sort_order' => $faq->sort_order,
            'is_published' => true,
        ])
        ->assertRedirect(route('admin.faqs.show', ['locale' => 'nl', 'faq' => $faq->id]));

    $faq->refresh();

    expect($faq->question)->toBe('Updated source question')
        ->and($faq->translated('question', 'en'))->toBe('EN Updated source question')
        ->and($faq->translated('answer', 'fr'))->toBe('FR Updated source answer');
});

test('staff can retranslate a single faq locale from source', function () {
    fakeDeepLTranslations();

    $faq = Faq::factory()->product()->create([
        'question' => 'Single locale source question',
        'answer' => 'Single locale source answer',
    ]);

    $faq->translations()->updateOrCreate(
        ['locale' => 'en', 'column' => 'question'],
        ['value' => 'Keep EN question', 'source_hash' => $faq->translationSourceHash('question')],
    );
    $faq->translations()->updateOrCreate(
        ['locale' => 'en', 'column' => 'answer'],
        ['value' => 'Keep EN answer', 'source_hash' => $faq->translationSourceHash('answer')],
    );
    $faq->translations()->updateOrCreate(
        ['locale' => 'fr', 'column' => 'question'],
        ['value' => 'Keep FR question', 'source_hash' => $faq->translationSourceHash('question')],
    );
    $faq->translations()->updateOrCreate(
        ['locale' => 'fr', 'column' => 'answer'],
        ['value' => 'Keep FR answer', 'source_hash' => $faq->translationSourceHash('answer')],
    );

    $this->actingAs($this->admin)
        ->post(route('admin.faqs.translate', ['locale' => 'nl', 'faq' => $faq->id]), [
            'target_locale' => 'nl',
        ])
        ->assertRedirect(route('admin.faqs.show', ['locale' => 'nl', 'faq' => $faq->id]));

    $faq->refresh();

    expect($faq->translated('question', 'nl'))->toBe('NL Single locale source question')
        ->and($faq->translated('answer', 'nl'))->toBe('NL Single locale source answer')
        ->and($faq->translated('question', 'en'))->toBe('Keep EN question')
        ->and($faq->translated('answer', 'en'))->toBe('Keep EN answer')
        ->and($faq->translated('question', 'fr'))->toBe('Keep FR question')
        ->and($faq->translated('answer', 'fr'))->toBe('Keep FR answer');
});

test('staff can queue deepl retranslation for all faq locales', function () {
    fakeDeepLTranslations();

    $faq = Faq::factory()->product()->create([
        'question' => 'Hervertaal vraag',
        'answer' => 'Hervertaal antwoord',
    ]);

    $faq->translations()->delete();

    $this->actingAs($this->admin)
        ->post(route('admin.faqs.translate', ['locale' => 'nl', 'faq' => $faq->id]))
        ->assertRedirect(route('admin.faqs.show', ['locale' => 'nl', 'faq' => $faq->id]));

    expect($faq->fresh()->translations()->count())->toBe(6);
});

test('public faq pages serve translated copy for the active locale', function () {
    fakeDeepLTranslations();

    Faq::factory()->product()->published()->create([
        'question' => 'Locale product vraag',
        'answer' => 'Locale product antwoord',
    ]);

    $this->get(route('maison.product', ['locale' => 'en']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('faqs', fn ($faqs) => collect($faqs)->contains(
                fn ($item) => $item['question'] === 'EN Locale product vraag'
                    && $item['answer'] === 'EN Locale product antwoord',
            ))
        );
});

test('staff can delete a faq', function () {
    $faq = Faq::factory()->create();

    $this->actingAs($this->admin)
        ->delete(route('admin.faqs.destroy', ['locale' => 'nl', 'faq' => $faq->id]))
        ->assertRedirect(route('admin.faqs.index', ['locale' => 'nl']));

    expect(Faq::query()->whereKey($faq->id)->exists())->toBeFalse();
});

test('create and edit faq pages render', function () {
    $faq = Faq::factory()->create();

    $this->actingAs($this->admin)
        ->get(route('admin.faqs.create', ['locale' => 'nl']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/faqs/create')
            ->has('contexts', 2)
        );

    $this->actingAs($this->admin)
        ->get(route('admin.faqs.edit', ['locale' => 'nl', 'faq' => $faq->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/faqs/edit')
            ->where('faq.id', (string) $faq->id)
            ->where('faq.question', $faq->question)
        );
});

test('public product and contact pages only expose published faqs', function () {
    Faq::factory()->product()->draft()->create([
        'question' => 'Verborgen product FAQ',
        'answer' => 'Niet zichtbaar',
    ]);

    Faq::factory()->contact()->draft()->create([
        'question' => 'Verborgen contact FAQ',
        'answer' => 'Niet zichtbaar',
    ]);

    $this->get(route('maison.product', ['locale' => 'nl']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('maison/product')
            ->where('faqs', fn ($faqs) => collect($faqs)->pluck('question')->doesntContain('Verborgen product FAQ'))
        );

    $this->get(route('maison.contact', ['locale' => 'nl']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('maison/contact')
            ->where('faqs', fn ($faqs) => collect($faqs)->pluck('question')->doesntContain('Verborgen contact FAQ'))
        );
});
