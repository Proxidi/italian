import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/css/auth/login.css',
                'resources/js/auth/login.js',
                'resources/css/authenticated.css',
                'resources/js/authenticated.js',
                'resources/css/ui-list.css',
                'resources/css/ui-feedback.css',
                'resources/js/ui.js',
                'resources/css/users-read.css',
                'resources/js/users-read.js',
                'resources/css/configuration.css',
                'resources/js/configuration.js',
                'resources/css/products.css',
                'resources/js/products.js',
            ],
            refresh: true,
        }),
    ],
});
