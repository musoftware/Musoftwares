import type { MessagePayload, Messaging } from 'firebase/messaging';

const firebaseConfig = {
    apiKey: import.meta.env.VITE_FIREBASE_API_KEY,
    authDomain: import.meta.env.VITE_FIREBASE_AUTH_DOMAIN,
    projectId: import.meta.env.VITE_FIREBASE_PROJECT_ID,
    storageBucket: import.meta.env.VITE_FIREBASE_STORAGE_BUCKET,
    messagingSenderId: import.meta.env.VITE_FIREBASE_MESSAGING_SENDER_ID,
    appId: import.meta.env.VITE_FIREBASE_APP_ID,
    measurementId: import.meta.env.VITE_FIREBASE_MEASUREMENT_ID
};

let messagingPromise: Promise<Messaging | null> | null = null;

/**
 * Load the Firebase SDK on demand. Firebase is only downloaded when a page
 * actually needs push messaging (permission granted or being requested).
 */
async function createMessaging(): Promise<Messaging | null> {
    if (typeof window === 'undefined' || !('serviceWorker' in navigator)) return null;

    const [{ initializeApp }, { getMessaging, isSupported }] = await Promise.all([
        import('firebase/app'),
        import('firebase/messaging'),
    ]);

    if (!(await isSupported())) return null;

    return getMessaging(initializeApp(firebaseConfig));
}

export function getFirebaseMessaging(): Promise<Messaging | null> {
    messagingPromise ??= createMessaging().catch((error) => {
        console.error('[firebase] Failed to initialize messaging.', error);
        messagingPromise = null;
        return null;
    });
    return messagingPromise;
}

/** Returns the FCM device token, or null when messaging is not available. */
export async function fetchFcmToken(): Promise<string | null> {
    const messaging = await getFirebaseMessaging();
    if (!messaging) return null;

    const { getToken } = await import('firebase/messaging');
    const token = await getToken(messaging, { vapidKey: import.meta.env.VITE_FIREBASE_VAPID_KEY });
    return token || null;
}

/** Subscribe to foreground push messages. Returns an unsubscribe function. */
export async function subscribeToForegroundMessages(
    handler: (payload: MessagePayload) => void,
): Promise<() => void> {
    const messaging = await getFirebaseMessaging();
    if (!messaging) return () => {};

    const { onMessage } = await import('firebase/messaging');
    return onMessage(messaging, handler);
}
