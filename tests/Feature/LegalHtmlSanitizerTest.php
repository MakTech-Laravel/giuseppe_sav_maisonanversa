<?php

use App\Support\Html\LegalHtml;

test('the sanitizer strips scripts event handlers and javascript urls', function () {
    $html = <<<'HTML'
<h2 class="keep" onclick="alert(1)">Titel</h2>
<p style="color:red">Kleur</p>
<p style="text-align: center">Uitlijning</p>
<a href="javascript:alert(1)">slecht</a>
<a href="https://maisonanversa.com" class="gold">goed</a>
<iframe src="https://evil.test"></iframe>
<script>alert(1)</script>
<img src="x" onerror="alert(1)">
HTML;

    $clean = LegalHtml::sanitize($html);

    expect($clean)
        ->toContain('<h2 class="keep">Titel</h2>')
        ->toContain('style="text-align: center"')
        ->toContain('https://maisonanversa.com')
        ->not->toContain('script')
        ->not->toContain('onclick')
        ->not->toContain('javascript:')
        ->not->toContain('iframe')
        ->not->toContain('onerror')
        ->not->toContain('color:red')
        ->not->toContain('<img');
});

test('blank after sanitize is detected', function () {
    expect(LegalHtml::isBlank(LegalHtml::sanitize('<script>alert(1)</script>')))->toBeTrue()
        ->and(LegalHtml::isBlank('<p>Hallo</p>'))->toBeFalse()
        ->and(LegalHtml::isBlank('<hr>'))->toBeFalse();
});

test('legacy markdown is converted for the editor', function () {
    $html = LegalHtml::forEditor("## Privacy\n\nEerste alinea.\n\n- Een\n- Twee");

    expect($html)
        ->toContain('<h2>Privacy</h2>')
        ->toContain('<p>Eerste alinea.</p>')
        ->toContain('<li>Een</li>');

    expect(LegalHtml::sanitize("## Privacy\n\nEerste alinea."))
        ->toContain('<h2>Privacy</h2>')
        ->toContain('<p>Eerste alinea.</p>');
});
