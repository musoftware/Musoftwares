import { useEffect, useState } from 'react';

interface BeforeInstallPromptEvent extends Event {
    readonly platforms: string[];
    readonly userChoice: Promise<{
        outcome: 'accepted' | 'dismissed';
        platform: string;
    }>;
    prompt(): Promise<void>;
}

export function usePWAInstall() {
    const [deferredPrompt, setDeferredPrompt] = useState<BeforeInstallPromptEvent | null>(null);
    const [isInstallable, setIsInstallable] = useState(false);
    const [isStandalone, setIsStandalone] = useState(false);

    useEffect(() => {
        // 1. Detect if currently running in standalone (PWA) mode
        const checkStandalone = () => {
            const isStandaloneMedia = window.matchMedia('(display-mode: standalone)').matches;
            const isAppleStandalone = (window.navigator as any).standalone === true;
            const isAndroidPwa = document.referrer.startsWith('android-app://');
            
            setIsStandalone(isStandaloneMedia || isAppleStandalone || isAndroidPwa);
        };

        checkStandalone();

        // 2. Capture the beforeinstallprompt event (Chrome/Android/Windows/Edge)
        const handleBeforeInstallPrompt = (e: Event) => {
            e.preventDefault();
            setDeferredPrompt(e as BeforeInstallPromptEvent);
            setIsInstallable(true);
            console.debug('[PWA] beforeinstallprompt event captured.');
        };

        window.addEventListener('beforeinstallprompt', handleBeforeInstallPrompt);

        // 3. Listen to display-mode changes dynamically
        const mediaQuery = window.matchMedia('(display-mode: standalone)');
        const handleDisplayModeChange = (e: MediaQueryListEvent) => {
            setIsStandalone(e.matches);
        };

        if (mediaQuery.addEventListener) {
            mediaQuery.addEventListener('change', handleDisplayModeChange);
        }

        return () => {
            window.removeEventListener('beforeinstallprompt', handleBeforeInstallPrompt);
            if (mediaQuery.removeEventListener) {
                mediaQuery.removeEventListener('change', handleDisplayModeChange);
            }
        };
    }, []);

    const install = async () => {
        if (!deferredPrompt) {
            console.warn('[PWA] Install prompt is not available.');
            return false;
        }

        try {
            await deferredPrompt.prompt();
            const choiceResult = await deferredPrompt.userChoice;
            console.debug(`[PWA] User choice outcome: ${choiceResult.outcome}`);
            
            // Clear prompt so it cannot be called again
            setDeferredPrompt(null);
            setIsInstallable(false);
            
            return choiceResult.outcome === 'accepted';
        } catch (err) {
            console.error('[PWA] Installation prompt failed:', err);
            return false;
        }
    };

    return {
        isInstallable,
        isStandalone,
        install
    };
}
