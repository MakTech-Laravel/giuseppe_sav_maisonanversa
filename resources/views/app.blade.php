<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#291c18">

        {{-- The site is light-mode only; cream matches the brand palette in app.css. --}}
        <style>
            html {
                background-color: #f3ebe3;
            }
        </style>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon-32x32.png" type="image/png" sizes="32x32">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @fonts

        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])
        <x-inertia::head>
            @include('seo.meta', ['seo' => $seo ?? null])
        </x-inertia::head>

        {{--
            The right light, and "once per session", decided before first paint so
            the opening never flashes the wrong colours or replays. The opening
            itself is in the server HTML, so it paints before any JavaScript runs.
            ?light=morning|afternoon|evening|night overrides the visitor's clock
            for testing; ?intro=1 plays the opening again.
        --}}
        <script @if (\Illuminate\Support\Facades\Vite::cspNonce()) nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}" @endif>
            (function () {
                var root = document.documentElement;

                try {
                    var forced = (location.search.match(/[?&]light=(morning|afternoon|evening|night)/) || [])[1];
                    var hour = new Date().getHours();

                    root.dataset.light = forced || (hour >= 5 && hour < 12 ? 'morning' : hour >= 12 && hour < 18 ? 'afternoon' : hour >= 18 && hour < 23 ? 'evening' : 'night');

                    if (!/[?&]intro(=|&|$)/.test(location.search) && sessionStorage.getItem('maison.opening.seen')) {
                        root.dataset.opening = 'seen';
                    }
                } catch (e) {
                    // Storage can be denied; the opening then simply plays.
                }
            })();
        </script>
    </head>
    <body class="antialiased">
        <x-inertia::app />
        @php
            $consent = json_decode(request()->cookie('maison_consent', ''), true);
            $allowAnalytics = is_array($consent) && ($consent['analytics'] ?? false);
            $measurementId = config('services.analytics.measurement_id');
        @endphp
        @if ($allowAnalytics && filled($measurementId))
            <script @if (\Illuminate\Support\Facades\Vite::cspNonce()) nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}" @endif async src="https://www.googletagmanager.com/gtag/js?id={{ $measurementId }}"></script>
            <script @if (\Illuminate\Support\Facades\Vite::cspNonce()) nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}" @endif>
                window.dataLayer = window.dataLayer || [];
                function gtag(){dataLayer.push(arguments);}
                gtag('js', new Date());
                gtag('config', @json($measurementId));
            </script>
        @endif
    </body>
</html>
