import { useState, useEffect } from 'react';
import { getToken, onMessage } from 'firebase/messaging';
import { messaging } from '@/firebase';
import axios from 'axios';
import { useToast } from '@/components/ui/use-toast';
import { __ } from '@/lib/i18n';
import { router } from '@inertiajs/react';

export function useFCM() {
    const [permission, setPermission] = useState<NotificationPermission>(() => {
        if (typeof window !== 'undefined' && 'Notification' in window) {
            return Notification.permission;
        }
        return 'default';
    });
    const { toast } = useToast();

    useEffect(() => {
        if (!('Notification' in window)) {
            console.log('This browser does not support desktop notification');
            return;
        }

        setPermission(Notification.permission);

        if (Notification.permission === 'granted') {
            registerFCMToken();
        }

        if (messaging) {
            const unsubscribe = onMessage(messaging, (payload) => {
                console.log('Message received. ', payload);
                
                toast({
                    title: payload.notification?.title || payload.data?.title || __('general.new_notification'),
                    description: payload.notification?.body || payload.data?.body || payload.data?.message,
                    action: payload.data?.url ? (
                        <button 
                            className="text-xs text-indigo-600 hover:text-indigo-800"
                            onClick={() => {
                                if (payload.data?.url) {
                                    router.visit(payload.data.url);
                                }
                            }}
                        >
                            {__('general.view')}
                        </button>
                    ) : undefined
                });

                // Optionally dispatch an event to reload notifications in the navbar
                window.dispatchEvent(new Event('app:new-notification'));
            });

            return () => unsubscribe();
        }
    }, [toast]);

    const registerFCMToken = async () => {
        if (!messaging) return;
        
        try {
            const currentToken = await getToken(messaging, {
                vapidKey: import.meta.env.VITE_FIREBASE_VAPID_KEY
            });
            
            if (currentToken) {
                await axios.post('/device-tokens', { token: currentToken });
            } else {
                console.log('No registration token available. Request permission to generate one.');
            }
        } catch (err) {
            console.log('An error occurred while retrieving token. ', err);
        }
    };

    const requestPermission = async () => {
        if (typeof window === 'undefined') return;

        const w = window as any;
        const isIos = /iphone|ipad|ipod/.test(w.navigator.userAgent.toLowerCase());
        const isStandalone = w.navigator.standalone || w.matchMedia('(display-mode: standalone)').matches;

        if (isIos && !isStandalone) {
            router.visit('/install-app');
            return;
        }

        if (!('Notification' in window)) {
            return;
        }
        
        const currentPermission = await Notification.requestPermission();
        setPermission(currentPermission);
        
        if (currentPermission === 'granted') {
            registerFCMToken();
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
