import { useState, useEffect } from 'react';
import type { MessagePayload } from 'firebase/messaging';
import axios from 'axios';
import { useToast } from '@/Components/ui/use-toast';
import { __ } from '@/lib/i18n';
import { router } from '@inertiajs/react';

const supportsNotifications = (): boolean => typeof window !== 'undefined' && 'Notification' in window;

async function registerFCMToken(): Promise<void> {
    try {
        const { fetchFcmToken } = await import('@/firebase');
        const token = await fetchFcmToken();
        if (token) {
            await axios.post('/device-tokens', { token });
        }
    } catch (error) {
        console.error('[fcm] Failed to register the device token.', error);
    }
}

function isIosBrowserTab(): boolean {
    const isIos = /iphone|ipad|ipod/.test(window.navigator.userAgent.toLowerCase());
    const isStandalone = (window.navigator as Navigator & { standalone?: boolean }).standalone
        || window.matchMedia('(display-mode: standalone)').matches;
    return isIos && !isStandalone;
}

export function useFCM() {
    const [permission, setPermission] = useState<NotificationPermission>(() =>
        supportsNotifications() ? Notification.permission : 'default',
    );
    const { toast } = useToast();

    // Firebase is only loaded once the user has granted notification permission.
    useEffect(() => {
        if (permission !== 'granted') return;

        registerFCMToken();

        const showForegroundMessage = (payload: MessagePayload) => {
            const url = payload.data?.url;
            toast({
                title: payload.notification?.title || payload.data?.title || __('general.new_notification'),
                description: payload.notification?.body || payload.data?.body || payload.data?.message,
                action: url ? (
                    <button
                        className="text-xs text-indigo-600 hover:text-indigo-800"
                        onClick={() => router.visit(url)}
                    >
                        {__('general.view')}
                    </button>
                ) : undefined
            });

            // Let the navbar reload its notification list
            window.dispatchEvent(new Event('app:new-notification'));
        };

        let unsubscribe: (() => void) | null = null;
        let cancelled = false;

        import('@/firebase')
            .then(({ subscribeToForegroundMessages }) => subscribeToForegroundMessages(showForegroundMessage))
            .then((stop) => {
                if (cancelled) stop();
                else unsubscribe = stop;
            })
            .catch((error) => console.error('[fcm] Failed to subscribe to foreground messages.', error));

        return () => {
            cancelled = true;
            unsubscribe?.();
        };
    }, [permission, toast]);

    const requestPermission = async () => {
        if (typeof window === 'undefined') return;

        if (isIosBrowserTab()) {
            router.visit('/install-app');
            return;
        }

        if (!supportsNotifications()) return;

        const currentPermission = await Notification.requestPermission();
        setPermission(currentPermission);

        if (currentPermission === 'granted') {
            // Clear the dismissed flag so we don't need it any more
            try { localStorage.removeItem('notif_banner_dismissed'); } catch { /* ignore */ }
            toast({
                title: __('general.success'),
                description: __('general.notifications_enabled_successfully')
            });
        }
    };

    return {
        permission,
        requestPermission
    };
}
