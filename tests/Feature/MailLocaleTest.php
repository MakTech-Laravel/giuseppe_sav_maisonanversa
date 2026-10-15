<?php

use App\Support\MailLocale;

test('mail locale falls back to english when the user has no supported locale', function () {
    expect(MailLocale::resolve(null))->toBe('en')
        ->and(MailLocale::resolve(''))->toBe('en')
        ->and(MailLocale::resolve('xx'))->toBe('en')
        ->and(MailLocale::resolve('nl'))->toBe('nl')
        ->and(MailLocale::resolve('en'))->toBe('en')
        ->and(MailLocale::resolve('fr'))->toBe('fr');
});
