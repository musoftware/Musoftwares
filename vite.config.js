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
                    if (id.includes('resources/js/Pages/')) {
                        const parts = id.split('resources/js/Pages/')[1].split('/');
                        
                        // Group all tools into ONE chunk to avoid massive concurrent requests
                        if (parts[0] === 'Tools') {
                            return 'app-tools';
                        }

                        // Group major modules to prevent 503 rate limits on shared hosting
                        const coreModules = ['Admin', 'Auth', 'Core', 'Dashboard.tsx', 'Error.tsx', 'Welcome.tsx', 'Profile', 'Settings', 'Activity', 'Notifications', 'Support'];
                        const erpModules = ['ERP', 'CRM', 'Billing', 'Client', 'Financial', 'Messages', 'Vouchers'];
                        const freelanceModules = ['Freelance', 'Marketplace', 'iSaaS', 'Public', 'Guest'];
                        
                        if (coreModules.includes(parts[0])) return 'app-pages-core';
                        if (erpModules.includes(parts[0])) return 'app-pages-erp';
                        if (freelanceModules.includes(parts[0])) return 'app-pages-freelance';
                        
                        // Everything else
                        return 'app-pages-other';
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
