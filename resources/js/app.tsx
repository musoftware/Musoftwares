import '../css/app.css';
import './bootstrap';

import { createInertiaApp, router } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { Toaster } from '@/Components/ui/toaster';
import { Toaster as SonnerToaster } from 'sonner';
import { GlobalErrorHandler } from '@/Components/GlobalErrorHandler';
import { MarketplaceModeProvider } from '@/Components/Marketplace/MarketplaceModeContext';
import { getLoadedLocale, loadTranslations, syncDocumentDirection } from '@/lib/i18n';
import { initTheme, applyTheme } from '@/lib/theme';
import { useAppStore } from '@/store/useAppStore';
import { initAllMouseScrollContainers } from '@/lib/mouseScroll';

type SharedPageProps = {
    locale?: string;
    currencies?: SharedCurrency[];
    wallet?: { currency?: string };
    settings?: { base_currency?: string };
};

/** Expose currency data from page props to the money formatting helpers. */
function syncGlobalCurrencyState(props: SharedPageProps): void {
    if (props.currencies) {
        window.currencies = props.currencies;
    }
    const defaultCurrency = props.wallet?.currency || props.settings?.base_currency;
    if (defaultCurrency) {
        window.defaultCurrency = defaultCurrency;
    }
}

// Initialize Auto / System / Dark / Light theme reactivity
if (typeof window !== 'undefined') {
    initTheme();
    initAllMouseScrollContainers();
}

// Listen for Inertia page transitions to keep document lang & dir synced
router.on('navigate', (event) => {
    const page = event.detail.page;
    const props = (page.props ?? {}) as SharedPageProps;
    const loadedLocale = getLoadedLocale();
    if (props.locale && loadedLocale && props.locale !== loadedLocale) {
        // Only the active locale is bundled in memory; reload to fetch the new one.
        window.location.reload();
        return;
    }
    if (props.locale) {
        syncDocumentDirection(props.locale);
    }
    syncGlobalCurrencyState(props);

    // Keep public pages strictly locked to light theme during client-side navigation
    if (page?.component?.startsWith('Public/') || page?.component === 'Frontend/Contract/Show') {
        document.documentElement.classList.remove('dark');
        document.documentElement.classList.add('light');
        document.documentElement.setAttribute('data-theme', 'light');
        document.documentElement.setAttribute('data-theme-fixed', 'true');
        document.documentElement.style.colorScheme = 'light';
        const metaThemeColor = document.querySelector('meta[name="theme-color"]');
        if (metaThemeColor) {
            metaThemeColor.setAttribute('content', '#ffffff');
        }
    } else {
        document.documentElement.removeAttribute('data-theme-fixed');
        document.documentElement.removeAttribute('data-theme');
        applyTheme(useAppStore.getState().theme || 'system');
    }
});

// Global DRY fix: Intercept any non-Inertia (Blade) responses and perform clean full-page navigation
// This completely prevents Inertia from opening an iframe modal for Blade pages
router.on('invalid', (event: any) => {
    event.preventDefault();
    const targetUrl = 
        event.detail?.response?.request?.responseURL || 
        event.detail?.response?.config?.url || 
        event.detail?.visit?.url?.href || 
        window.location.href;
    if (targetUrl) {
        window.location.href = targetUrl;
    }
});

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// Global Scroll Animation Observer
const ANIMATE_SELECTOR = '.animate-on-scroll:not(.is-observed)';

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

    const observeElement = (el: Element) => {
        el.classList.add('is-observed');
        observer.observe(el);
    };

    const scanSubtree = (root: ParentNode) => {
        if (root instanceof Element && root.matches(ANIMATE_SELECTOR)) {
            observeElement(root);
        }
        root.querySelectorAll(ANIMATE_SELECTOR).forEach(observeElement);
        initAllMouseScrollContainers(root);
    };

    scanSubtree(document);

    // Batch DOM changes into one scan per animation frame, and only look at added nodes.
    let pendingNodes: Element[] = [];
    let frameRequested = false;

    const flushPendingNodes = () => {
        const nodes = pendingNodes;
        pendingNodes = [];
        frameRequested = false;
        nodes.filter((node) => node.isConnected).forEach(scanSubtree);
    };

    const mutationObserver = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (node instanceof Element) pendingNodes.push(node);
            });
        });
        if (pendingNodes.length === 0 || frameRequested) return;
        frameRequested = true;
        requestAnimationFrame(flushPendingNodes);
    });
    mutationObserver.observe(document.body, { childList: true, subtree: true });
};

// Start the observer once the page has loaded
if (typeof window !== 'undefined') {
    if (document.readyState === 'complete') {
        initScrollObserver();
    } else {
        window.addEventListener('load', initScrollObserver, { once: true });
    }
}

const appElement = document.getElementById('app');

async function bootInertiaApp(pageData: string): Promise<void> {
    const initialLocale = (JSON.parse(pageData)?.props as SharedPageProps | undefined)?.locale
        || document.documentElement.lang
        || 'en';
    await loadTranslations(initialLocale);

    await createInertiaApp({
        title: (title) => `${title} - ${appName}`,
        resolve: (name) =>
            resolvePageComponent(
                [`./Pages/${name}.tsx`, `./Pages/${name}.jsx`],
                import.meta.glob(['./Pages/**/*.tsx', './Pages/**/*.jsx', '!./Pages/**/*.test.*', '!./Pages/**/*.spec.*', '!./Pages/**/__tests__/**']),
            ),
        setup({ el, App, props }) {
            const root = createRoot(el);
            
            const pageProps = props.initialPage.props as SharedPageProps;
            if (pageProps.locale) {
                syncDocumentDirection(pageProps.locale);
            }
            syncGlobalCurrencyState(pageProps);

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
}

if (appElement?.dataset.page) {
    bootInertiaApp(appElement.dataset.page).catch((error) => {
        console.error('[app] Failed to start the application.', error);
    });
}
