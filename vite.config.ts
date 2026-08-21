import { resolve } from 'node:path';
import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import { defineConfig } from 'vite';

export default defineConfig({
    resolve: {
        dedupe: ['react', 'react-dom'],
        alias: {
            // Laravel's JSON translation files, imported lazily per locale.
            '@lang': resolve(import.meta.dirname, 'lang'),
        },
    },
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            refresh: true,
            fonts: [
                // Baskervville ships weight 400 only; headings asking for 500+
                // are synthesised, matching the prototype. Italic carries the
                // <em> emphasis used throughout the headings.
                bunny('Baskervville', {
                    weights: [400],
                    styles: ['normal', 'italic'],
                    subsets: ['latin'],
                    preload: [{ weight: 400, style: 'normal' }],
                    fallbacks: ['Georgia', 'serif'],
                }),
                bunny('Montserrat', {
                    weights: [300, 400, 500],
                    styles: ['normal'],
                    subsets: ['latin'],
                    preload: [{ weight: 300, style: 'normal' }],
                    fallbacks: ['sans-serif'],
                }),
            ],
        }),
        inertia({
            ssr: {
                entry: 'resources/js/ssr.tsx',
            },
        }),
        react({
            babel: {
                plugins: ['babel-plugin-react-compiler'],
            },
        }),
        tailwindcss(),
        wayfinder({
            formVariants: true,
        }),
    ],
});
