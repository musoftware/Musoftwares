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
                        if (id.includes('lucide-react')) return 'vendor-icons';
                        if (id.includes('framer-motion')) return 'vendor-motion';
                        if (id.includes('recharts') || id.includes('d3')) return 'vendor-charts';
                        if (id.includes('monaco-editor')) return 'vendor-editor';
                        if (id.includes('xlsx') || id.includes('exceljs')) return 'vendor-excel';
                        // Let React and Inertia safely fall back to the default vendor chunk
                        return 'vendor'; 
                    }
                    if (id.includes('resources/js/Pages/')) {
                        const parts = id.split('resources/js/Pages/')[1].split('/');
                        
                        // Break down tools specifically into their own chunks
                        if (parts[0] === 'Tools' && parts.length > 1) {
                            if (parts.length > 2) {
                                return 'tool-' + parts[1].toLowerCase();
                            }
                            const fileName = parts[1].replace(/\.(tsx|jsx|ts|js)$/, '').toLowerCase();
                            return 'tool-' + fileName;
                        }

                        // Group other pages by their main module folder (e.g. page-frontend, page-admin)
                        if (parts.length > 1) {
                            return 'page-' + parts[0].toLowerCase();
                        }
                    }
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
