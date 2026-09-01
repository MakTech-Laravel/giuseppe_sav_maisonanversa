<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('Heritage Paspoort') }} · No.{{ $passport['editionNumber'] }}</title>
    <style>
        @page { margin: 28mm 22mm; }
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #1a1410;
            font-size: 12pt;
            line-height: 1.55;
        }
        .masthead {
            border-bottom: 1.5pt solid #c4a35a;
            padding-bottom: 10pt;
            margin-bottom: 22pt;
        }
        .eyebrow {
            font-size: 8pt;
            letter-spacing: 0.22em;
            text-transform: uppercase;
            color: #8a7340;
            margin: 0 0 6pt;
        }
        h1 {
            font-size: 22pt;
            font-weight: normal;
            margin: 0;
        }
        .member {
            margin-top: 6pt;
            font-size: 10pt;
            color: #5a5048;
        }
        .page {
            page-break-inside: avoid;
            margin-bottom: 18pt;
            padding: 14pt 16pt;
            border: 0.75pt solid #c4a35a;
        }
        .page-label {
            font-size: 8pt;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: #8a7340;
            margin: 0 0 8pt;
        }
        .page h2 {
            font-size: 16pt;
            font-weight: normal;
            margin: 0 0 10pt;
        }
        .page p {
            margin: 0;
            font-size: 11pt;
        }
        .verify {
            margin-top: 10pt;
            font-size: 9pt;
            word-break: break-all;
            color: #8a7340;
        }
    </style>
</head>
<body>
    <header class="masthead">
        <p class="eyebrow">Maison Anversa · No.{{ $passport['editionNumber'] }}</p>
        <h1>{{ __('Heritage Paspoort') }}</h1>
        <p class="member">{{ $memberName }}</p>
    </header>

    @foreach ($passport['pages'] as $index => $page)
        <section class="page">
            <p class="page-label">{{ __('Pagina') }} {{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</p>
            <h2>{{ $page['title'] }}</h2>
            <p>{{ $page['body'] }}</p>
            @if ($index === 3 && ! empty($passport['verificationUrl']))
                <p class="verify">{{ $passport['verificationUrl'] }}</p>
            @endif
        </section>
    @endforeach
</body>
</html>
