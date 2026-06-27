import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        vue(),
    ],
    build: {
        rollupOptions: {
            output: {
                manualChunks: {
                    'pdf-libs': ['jspdf', 'jspdf-autotable'],
                    'chart':    ['chart.js'],
                },
            },
        },
    },
    server: {
        proxy: {
            '/api': {
                target: 'http://localhost/LifeOrganizer/public',
                changeOrigin: true,
            },
        },
    },
});
