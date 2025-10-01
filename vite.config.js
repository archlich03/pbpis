// pbpis/vite.config.js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
    server: {
        host: '0.0.0.0', // <--- IMPORTANT: Listen on all interfaces
        hmr: {
            host: process.env.VITE_HMR_HOST || 'localhost', // Browser connects to this host for HMR
        },
        watch: {
            usePolling: true, // Recommended for Docker volumes on some OS/filesystems
        }
    }
});