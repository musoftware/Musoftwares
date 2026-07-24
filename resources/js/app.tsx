import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { Toaster } from '@/Components/ui/toaster';
import { Toaster as SonnerToaster } from 'sonner';
import { GlobalErrorHandler } from '@/Components/GlobalErrorHandler';
import { MarketplaceModeProvider } from '@/Components/Marketplace/MarketplaceModeContext';
// WebSockets disabled for main SaaS

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// Global Scroll Animation Observer
const initScrollObserver = () => {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
            }
        });
    }, {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    });

    const observeElements = () => {
        document.querySelectorAll('.animate-on-scroll:not(.is-observed)').forEach((el) => {
            el.classList.add('is-observed');
            observer.observe(el);
        });
    };

    // Initial check
    observeElements();

    // Re-check when DOM changes (useful for React/Inertia dynamic rendering)
    const mutationObserver = new MutationObserver(() => {
        observeElements();
    });
    mutationObserver.observe(document.body, { childList: true, subtree: true });
};

// Start the observer
if (typeof window !== 'undefined') {
    window.addEventListener('load', initScrollObserver);
    if (document.readyState === 'complete') {
        initScrollObserver();
    }
}
createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            [`./Pages/${name}.tsx`, `./Pages/${name}.jsx`],
            import.meta.glob(['./Pages/**/*.tsx', './Pages/**/*.jsx', '!./Pages/**/*.test.*', '!./Pages/**/*.spec.*', '!./Pages/**/__tests__/**']),
        ),
    setup({ el, App, props }) {
        const root = createRoot(el);
        
        const pageProps = props.initialPage.props as any;
        if (pageProps.currencies) {
            (window as any).currencies = pageProps.currencies;
        }
        if (pageProps.wallet?.currency) {
            (window as any).defaultCurrency = pageProps.wallet.currency;
        } else if (pageProps.settings?.base_currency) {
            (window as any).defaultCurrency = pageProps.settings.base_currency;
        }

        root.render(
            <MarketplaceModeProvider>
                <App {...props} />
                <Toaster />
                <SonnerToaster position="top-center" richColors />
                <GlobalErrorHandler />
            </MarketplaceModeProvider>
        );
    },
    progress: {
        color: '#4B5563',
    },
});
