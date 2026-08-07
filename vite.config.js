import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import { fontsource } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

const viteAssetPattern =
    /^\/(?!@|resources\/|packages\/|node_modules\/|\.vite\/|__laravel_vite_plugin__\/)/;

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');
    const backend = env.VITE_BACKEND_URL || env.APP_URL || 'http://127.0.0.1:8000';

    return {
        plugins: [
            laravel({
                input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/region-map.js', 'resources/js/servers.js'],
                refresh: true,
                fonts: [
                    fontsource('Sora', {
                        weights: [400, 500, 600, 700],
                    }),
                    fontsource('Fraunces', {
                        weights: [500, 600, 700],
                    }),
                ],
            }),
            tailwindcss(),
        ],
        server: {
            host: '127.0.0.1',
            proxy: {
                [viteAssetPattern.source]: {
                    target: backend,
                    changeOrigin: true,
                },
            },
            watch: {
                ignored: ['**/storage/framework/views/**'],
            },
        },
    };
});
