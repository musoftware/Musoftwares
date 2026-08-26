import { useAppStore } from '@/store/useAppStore';

export type ThemeMode = 'light' | 'dark' | 'system';

/**
 * Checks whether dark mode is currently active based on explicit selection or system preference.
 */
export function isDarkActive(theme: ThemeMode = useAppStore.getState().theme): boolean {
    if (typeof window === 'undefined') return false;

    if (theme === 'dark') {
        return true;
    }
    if (theme === 'light') {
        return false;
    }
    // Auto (System) mode
    return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
}

/**
 * Applies the given theme mode to document.documentElement and meta tags.
 */
export function applyTheme(theme: ThemeMode): void {
    if (typeof window === 'undefined' || !document.documentElement) return;

    const isDark = isDarkActive(theme);
    const root = document.documentElement;

    if (isDark) {
        root.classList.add('dark');
        root.style.colorScheme = 'dark';
    } else {
        root.classList.remove('dark');
        root.style.colorScheme = 'light';
    }

    // Keep meta theme-color in sync
    const metaThemeColor = document.querySelector('meta[name="theme-color"]');
    if (metaThemeColor) {
        metaThemeColor.setAttribute('content', isDark ? '#090d16' : '#ffffff');
    }
}

/**
 * Initializes real-time theme listener and zustand subscriber.
 */
export function initTheme(): () => void {
    if (typeof window === 'undefined') return () => {};

    // 1. Initial application
    const initialTheme = useAppStore.getState().theme || 'system';
    applyTheme(initialTheme);

    // 2. React to Zustand theme state changes
    const unsubscribeStore = useAppStore.subscribe(
        (state) => state.theme,
        (newTheme) => {
            applyTheme(newTheme);
        }
    );

    // 3. React to OS / System color scheme changes in real-time
    let unsubscribeMedia = () => {};
    if (window.matchMedia) {
        const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        const handleMediaChange = () => {
            const currentTheme = useAppStore.getState().theme;
            if (currentTheme === 'system') {
                applyTheme('system');
            }
        };

        if (mediaQuery.addEventListener) {
            mediaQuery.addEventListener('change', handleMediaChange);
            unsubscribeMedia = () => mediaQuery.removeEventListener('change', handleMediaChange);
        } else if ((mediaQuery as any).addListener) {
            (mediaQuery as any).addListener(handleMediaChange);
            unsubscribeMedia = () => (mediaQuery as any).removeListener(handleMediaChange);
        }
    }

    return () => {
        unsubscribeStore();
        unsubscribeMedia();
    };
}
