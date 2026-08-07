import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig(({ mode }) => {
    // Memuat file .env
    const env = loadEnv(mode, process.cwd(), '');

    // Mengambil hostname dari APP_URL (misalnya http://10.102.10.180:8000 -> 10.102.10.180)
    let hmrHost = 'localhost';
    if (env.APP_URL) {
        try {
            const url = new URL(env.APP_URL);
            if (url.hostname && url.hostname !== '0.0.0.0') {
                hmrHost = url.hostname;
            }
        } catch (e) {
            // Abaikan jika APP_URL tidak valid
        }
    }

    return {
        plugins: [
            laravel({
                input: ['resources/css/app.css', 'resources/js/app.js'],
                refresh: true,
            }),
        ],
        server: {
            host: '0.0.0.0', // Memungkinkan akses dari LAN
            hmr: {
                host: hmrHost,
            },
            cors: true,
        },
    };
});

