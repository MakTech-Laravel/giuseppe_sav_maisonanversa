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
                    fallbacks: ['Georgia', 'serif'],
                }),
                bunny('Montserrat', {
                    weights: [200, 300, 400, 500, 600],
                    fallbacks: ['sans-serif'],
                }),
            ],
        }),
        inertia(),
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
