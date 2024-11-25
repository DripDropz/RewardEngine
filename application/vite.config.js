import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/tailwind_app.css',
                'resources/js/tailwind_app.js',
                'resources/js/vuetify_app.js',
            ],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    build: {
        chunkSizeWarningLimit: 10240,
    },
    server: {
        port: 8201,
        hmr: {
            host: '0.0.0.0'
        }
    },
});
