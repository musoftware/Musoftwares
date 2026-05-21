import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { Toaster } from '@/Components/ui/toaster';
import { GlobalErrorHandler } from '@/Components/GlobalErrorHandler';
import { FreelanceModeProvider } from '@/Components/Freelance/FreelanceModeContext';
import { MarketplaceModeProvider } from '@/Components/Marketplace/MarketplaceModeContext';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            [`./Pages/${name}.tsx`, `./Pages/${name}.jsx`],
            import.meta.glob(['./Pages/**/*.tsx', './Pages/**/*.jsx']),
        ),
    setup({ el, App, props }) {
        const root = createRoot(el);

        root.render(
            <MarketplaceModeProvider>
                <FreelanceModeProvider>
                    <App {...props} />
                    <Toaster />
                    <GlobalErrorHandler />
                </FreelanceModeProvider>
            </MarketplaceModeProvider>
        );
    },
    progress: {
        color: '#4B5563',
    },
});
