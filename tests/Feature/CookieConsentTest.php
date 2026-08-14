<?php

test('visitors can store cookie consent', function () {
    $this->from('/nl')
        ->post(route('cookie-consent.store'), [
            'necessary' => 1,
            'analytics' => 1,
            'marketing' => 0,
        ])
        ->assertRedirect()
        ->assertCookie('maison_consent');
});
