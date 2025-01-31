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
        host: true,
	https: true, // HTTPSを有効化
        hmr: {
            host: 'l11dev.com', // 本番環境のドメイン
	    protocol: 'wss' // WebSocket Secure（HTTPS環境ではwssが必要）
        },
    },
    build: {
        manifest: true,
        outDir: 'public/build',
        rollupOptions: {
            input: 'resources/js/app.js'
        }
    }
});
