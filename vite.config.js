import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
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
        tailwindcss(),
    ],
    resolve: {
        alias: {
            '@': '/resources/js',
        },
    },
    server: {
        host: 'localhost',
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
    // PERFORMANCE: Build optimization for code splitting and lazy loading
    build: {
        // Enable source maps for debugging (disable in production for smaller bundles)
        sourcemap: false,
        // Chunk size warning limit (in KB)
        chunkSizeWarningLimit: 500,
        rollupOptions: {
            output: {
                // Manual chunk splitting for better caching
                manualChunks: {
                    // Vendor chunks - these rarely change, so cache well
                    'vendor-vue': ['vue', '@vue/runtime-dom', '@vue/runtime-core'],
                    'vendor-inertia': ['@inertiajs/vue3'],
                    'vendor-pinia': ['pinia'],
                    // Heavy libraries in separate chunks
                    'vendor-charts': ['chart.js', 'apexcharts', 'vue3-apexcharts'],
                },
                // Dynamic chunk naming for lazy-loaded modules
                chunkFileNames: (chunkInfo) => {
                    const facadeModuleId = chunkInfo.facadeModuleId
                        ? chunkInfo.facadeModuleId.split('/').pop().replace('.vue', '')
                        : 'chunk';
                    return `js/${facadeModuleId}-[hash].js`;
                },
                // Entry file naming
                entryFileNames: 'js/[name]-[hash].js',
                // Asset file naming (CSS, images, etc.)
                assetFileNames: (assetInfo) => {
                    const extType = assetInfo.name.split('.').pop();
                    if (/css/i.test(extType)) {
                        return 'css/[name]-[hash][extname]';
                    }
                    if (/png|jpe?g|svg|gif|tiff|bmp|ico/i.test(extType)) {
                        return 'images/[name]-[hash][extname]';
                    }
                    if (/woff|woff2|eot|ttf|otf/i.test(extType)) {
                        return 'fonts/[name]-[hash][extname]';
                    }
                    return 'assets/[name]-[hash][extname]';
                },
            },
        },
        // Minification settings
        minify: 'terser',
        terserOptions: {
            compress: {
                drop_console: true, // Remove console.log in production
                drop_debugger: true,
            },
        },
    },
});
