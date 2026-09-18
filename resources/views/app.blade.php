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
            Paint the arrival curtain before React boots so the home page never
            flashes underneath the preloader. ImmersiveIntro / Preloader remove
            #maison-boot-cover once they own the screen.
        --}}
        <script @if (\Illuminate\Support\Facades\Vite::cspNonce()) nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}" @endif>
            (function () {
                try {
                    if (sessionStorage.getItem('maison.intro.seen')) {
                        return;
                    }

                    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                        return;
                    }

                    var locales = @json(config('maison.locales'));
                    var path = location.pathname.replace(/\/+$/, '') || '/';
                    var home = locales.some(function (locale) {
                        return path === '/' + locale;
                    });

                    if (!home && path !== '/') {
                        return;
                    }

                    var cover = document.createElement('div');
                    cover.id = 'maison-boot-cover';
                    cover.setAttribute('aria-hidden', 'true');
                    cover.style.cssText =
                        'position:fixed;inset:0;z-index:100000;background:#291c18;';
                    document.documentElement.appendChild(cover);
                } catch (e) {
                    // Storage or matchMedia can fail; React will still mount the loader.
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
