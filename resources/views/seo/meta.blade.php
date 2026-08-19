@if (! empty($seo) && is_array($seo))
    <title>{{ $seo['title'] }}</title>
    <meta name="description" content="{{ $seo['description'] }}">
    @if (! empty($seo['robots']))
        <meta name="robots" content="{{ $seo['robots'] }}">
    @endif
    <link rel="canonical" href="{{ $seo['canonical'] }}">
    @foreach ($seo['hreflang'] ?? [] as $alternate)
        <link rel="alternate" hreflang="{{ $alternate['hreflang'] }}" href="{{ $alternate['href'] }}">
    @endforeach
    <meta property="og:type" content="{{ $seo['ogType'] }}">
    <meta property="og:url" content="{{ $seo['canonical'] }}">
    <meta property="og:title" content="{{ $seo['title'] }}">
    <meta property="og:description" content="{{ $seo['description'] }}">
    <meta property="og:image" content="{{ $seo['ogImage'] }}">
    @if (! empty($seo['ogImageWidth']))
        <meta property="og:image:width" content="{{ $seo['ogImageWidth'] }}">
    @endif
    @if (! empty($seo['ogImageHeight']))
        <meta property="og:image:height" content="{{ $seo['ogImageHeight'] }}">
    @endif
    <meta property="og:locale" content="{{ $seo['locale'] }}">
    @foreach ($seo['localeAlternates'] ?? [] as $alternateLocale)
        <meta property="og:locale:alternate" content="{{ $alternateLocale }}">
    @endforeach
    <meta property="og:site_name" content="{{ $seo['siteName'] }}">
    @if (! empty($seo['articlePublishedTime']))
        <meta property="article:published_time" content="{{ $seo['articlePublishedTime'] }}">
    @endif
    @if (! empty($seo['articleModifiedTime']))
        <meta property="article:modified_time" content="{{ $seo['articleModifiedTime'] }}">
    @endif
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seo['title'] }}">
    <meta name="twitter:description" content="{{ $seo['description'] }}">
    <meta name="twitter:image" content="{{ $seo['ogImage'] }}">
    @foreach ($seo['jsonLd'] ?? [] as $graph)
        <script type="application/ld+json">{!! json_encode($graph, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
    @endforeach
@else
    <title>{{ config('app.name', 'Laravel') }}</title>
@endif
