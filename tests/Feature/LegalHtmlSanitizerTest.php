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

test('safe typography styles and subscripts are kept while expressions are stripped', function () {
    $html = <<<'HTML'
<p style="color:#291c18;font-family:Montserrat, sans-serif;font-size:16px;line-height:1.5;background-color:expression(alert(1))">Maison</p>
<span style="background-color:#f3ebe3">vlak</span>
<p>H<sub>2</sub>O<sup>n</sup></p>
<mark data-color="#fff3bf" style="background-color: #fff3bf; color: inherit">markering</mark>
HTML;

    $clean = LegalHtml::sanitize($html);

    expect($clean)
        ->toContain('#291c18')
        ->toContain('Montserrat, sans-serif')
        ->toContain('font-size: 16px')
        ->toContain('line-height: 1.5')
        ->toContain('background-color: #f3ebe3')
        ->toContain('<sub>2</sub>')
        ->toContain('<sup>n</sup>')
        ->toContain('data-color="#fff3bf"')
        ->not->toContain('expression');
});

test('headings details and safe ids are kept while scripts stay stripped', function () {
    $html = <<<'HTML'
<h1 id="intro">Intro</h1>
<h6 id="fine">Fine print</h6>
<details open="open"><summary>Meer</summary><p>Inhoud</p></details>
<script>alert(1)</script>
HTML;

    $clean = LegalHtml::sanitize($html);

    expect($clean)
        ->toContain('<h1 id="intro">Intro</h1>')
        ->toContain('<h6 id="fine">Fine print</h6>')
        ->toContain('<details')
        ->toContain('<summary>Meer</summary>')
        ->not->toContain('script');
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
