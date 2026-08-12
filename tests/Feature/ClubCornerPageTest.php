<?php

test('the club corner page renders the maison corner component', function () {
    $this->get('/nl/corner')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('maison/corner'));
});

test('the partnership form uses react submit instead of onclick handlers', function () {
    $source = file_get_contents(resource_path('js/pages/maison/corner.tsx'));

    expect($source)
        ->toContain('onSubmit={onSubmit}')
        ->toContain('useRef')
        ->toContain('useState')
        ->toContain("from 'react'")
        ->not->toContain('onclick=');
});

test('the partnership form includes the expected club fields', function () {
    $source = file_get_contents(resource_path('js/pages/maison/corner.tsx'));

    expect($source)
        ->toContain('name="clubName"')
        ->toContain('name="contactPerson"')
        ->toContain('name="email"')
        ->toContain('name="location"')
        ->toContain('name="courts"')
        ->toContain('name="format"')
        ->toContain('name="about"');
});
