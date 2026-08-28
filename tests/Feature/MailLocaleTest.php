<?php

use App\Support\MailLocale;

test('mail locale falls back to dutch default', function () {
    expect(MailLocale::resolve(null))->toBe('nl')
        ->and(MailLocale::resolve(''))->toBe('nl')
        ->and(MailLocale::resolve('xx'))->toBe('nl')
        ->and(MailLocale::resolve('en'))->toBe('en')
        ->and(MailLocale::resolve('fr'))->toBe('fr');
});
