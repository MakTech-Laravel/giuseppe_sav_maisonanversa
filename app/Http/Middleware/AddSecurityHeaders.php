<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class AddSecurityHeaders
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        Vite::useCspNonce();

        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->headers->set('Content-Security-Policy', $this->contentSecurityPolicy());

        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        if (! app()->isProduction()) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        return $response;
    }

    /**
     * Stripe Checkout is a full-page redirect, so js.stripe.com is allow-listed
     * for Cashier confirmation pages rather than the public catalogue.
     */
    private function contentSecurityPolicy(): string
    {
        $nonce = Vite::cspNonce();

        $script = [
            "'self'",
            "'nonce-{$nonce}'",
            'https://js.stripe.com',
            'https://www.googletagmanager.com',
        ];

        $style = ["'self'", "'unsafe-inline'", 'https://fonts.bunny.net'];
        $font = ["'self'", 'https://fonts.bunny.net', 'data:'];
        $img = ["'self'", 'data:', 'blob:'];

        $connect = [
            "'self'",
            'https://api.stripe.com',
            'https://m.stripe.com',
            'https://m.stripe.network',
            'https://www.google-analytics.com',
            'https://www.googletagmanager.com',
            'https://*.google-analytics.com',
            'https://*.analytics.google.com',
        ];

        if (! app()->isProduction()) {
            $script[] = "'unsafe-eval'";

            foreach (['localhost', '127.0.0.1', '[::1]'] as $host) {
                $origin = "http://{$host}:5173";

                $script[] = $origin;
                $style[] = $origin;
                $font[] = $origin;
                $img[] = $origin;
                $connect[] = $origin;
                $connect[] = "ws://{$host}:5173";
            }
        }

        $directives = [
            'default-src' => ["'self'"],
            'base-uri' => ["'self'"],
            'object-src' => ["'none'"],
            'frame-ancestors' => ["'self'"],
            'script-src' => $script,
            'style-src' => $style,
            'font-src' => $font,
            'img-src' => $img,
            'connect-src' => $connect,
            'frame-src' => [
                "'self'",
                'https://js.stripe.com',
                'https://hooks.stripe.com',
                'https://checkout.stripe.com',
            ],
            'form-action' => ["'self'", 'https://checkout.stripe.com'],
        ];

        return collect($directives)
            ->map(fn (array $sources, string $name): string => $name.' '.implode(' ', $sources))
            ->implode('; ');
    }
}
