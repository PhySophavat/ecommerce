import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        vue(),
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: [
                'app/**/*.php',
                'bootstrap/**/*.php',
                'config/**/*.php',
                'database/**/*.php',
                'resources/views/**/*.blade.php',
                'routes/**/*.php',
            ],
        }),
        tailwindcss(),
    ],
    server: {
        host: '127.0.0.1',
        port: 5173,
        strictPort: true,
        cors: true,
        hmr: {
            host: '127.0.0.1',
            protocol: 'ws',
            port: 5173,
        },
        watch: {
            usePolling: true,
            interval: 300,
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
