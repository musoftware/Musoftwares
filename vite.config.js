import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    server: {
        host: '127.0.0.1',
    },
    css: {
        transformer: 'lightningcss',
        lightningcss: {
            targets: {
                chrome: 92 << 16,
                edge: 92 << 16,
                safari: 14 << 16,
                firefox: 90 << 16
            }
        }
    },
    build: {
        target: 'es2020',
        cssMinify: 'lightningcss',
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (id.includes('node_modules')) {
                        // Separate heavy UI libraries to prevent blocking the main vendor chunk
                        if (id.includes('lucide-react')) return 'lib-icons';
                        if (id.includes('framer-motion')) return 'lib-motion';
                        if (id.includes('recharts') || id.includes('d3')) return 'lib-charts';
                        if (id.includes('monaco-editor')) return 'lib-editor';
                        if (id.includes('xlsx') || id.includes('exceljs')) return 'lib-excel';
                        // Let React and Inertia safely fall back to the default vendor chunk
                        return 'lib-core'; 
                    }
                    // Let Vite handle Pages chunking automatically to avoid circular dependencies
                }
            }
        }
    },
    plugins: [
        laravel({
            input: 'resources/js/app.tsx',
            refresh: true,
        }),
        react(),
        tailwindcss(),
    ],
});
