import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import tailwindcss from '@tailwindcss/vite';


/**
 * Packages every page needs. They (and their own dependencies) go into one
 * long-cached vendor chunk. Everything else is split by Rollup, so heavy
 * libraries (pdf, charts, editors, firebase...) only load on pages that use them.
 */
const CORE_VENDOR_PACKAGES = new Set([
    'react',
    'react-dom',
    'scheduler',
    '@inertiajs/core',
    '@inertiajs/react',
    'axios',
    'sonner',
    'zustand',
    'clsx',
    'tailwind-merge',
    'class-variance-authority',
]);

const NODE_MODULES_PACKAGE = /node_modules\/((?:@[^/]+\/)?[^/]+)/g;

function packageNameOf(id) {
    const matches = [...id.replace(/\\/g, '/').matchAll(NODE_MODULES_PACKAGE)];
    return matches.length > 0 ? matches[matches.length - 1][1] : null;
}

const coreModuleCache = new Map();

// A dependency of a core package is core too, so the core chunk never imports other chunks.
function isCoreVendorModule(id, getModuleInfo, visiting = new Set()) {
    if (coreModuleCache.has(id)) return coreModuleCache.get(id);
    const packageName = packageNameOf(id);
    if (!packageName || visiting.has(id)) return false;
    if (CORE_VENDOR_PACKAGES.has(packageName)) return true;

    visiting.add(id);
    const importers = getModuleInfo(id)?.importers ?? [];
    const isCore = importers.some((importer) => isCoreVendorModule(importer, getModuleInfo, visiting));
    coreModuleCache.set(id, isCore);
    return isCore;
}

function assignVendorChunk(id, { getModuleInfo }) {
    const packageName = packageNameOf(id);
    if (!packageName) return undefined;
    if (packageName === 'lucide-react') return 'lib-icons';
    if (isCoreVendorModule(id, getModuleInfo)) return 'vendor-core';
    return undefined;
}

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
                manualChunks: assignVendorChunk,
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
