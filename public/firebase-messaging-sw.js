importScripts("https://www.gstatic.com/firebasejs/10.7.0/firebase-app-compat.js");
importScripts("https://www.gstatic.com/firebasejs/10.7.0/firebase-messaging-compat.js");

// We don't have access to process.env here directly, but the API keys are safe to expose
firebase.initializeApp({
    apiKey: "AIzaSyD6BX7ecQo6h1LmyneV_OoaveWKZY6pujI",
    authDomain: "musoftware-c0696.firebaseapp.com",
    projectId: "musoftware-c0696",
    storageBucket: "musoftware-c0696.firebasestorage.app",
    messagingSenderId: "692185121248",
    appId: "1:692185121248:web:5f8e32779b47fc64b6eb85",
    measurementId: "G-18M7NPFFTJ"
});

const messaging = firebase.messaging();

messaging.onBackgroundMessage((payload) => {
    console.log('[firebase-messaging-sw.js] Received background message ', payload);
    
    // The browser will automatically display the notification if it has a 'notification' payload.
    // We only need to manually handle it if it's a data-only payload, or if we want to customize the notification.
    if (!payload.notification) {
        const notificationTitle = payload.data?.title || 'New Notification';
        const notificationOptions = {
            body: payload.data?.body || payload.data?.message,
            icon: '/icon.png', // Ensure this exists or fallback to default
            data: payload.data
        };
        self.registration.showNotification(notificationTitle, notificationOptions);
    }
});

self.addEventListener('notificationclick', function(event) {
    event.notification.close();
    
    console.log('[firebase-messaging-sw.js] notificationclick event data:', event.notification.data);
    
    let url = '/';
    const data = event.notification.data;
    
    if (data) {
        // If data is the payload directly
        if (data.fcmOptions && data.fcmOptions.link) {
            url = data.fcmOptions.link;
        } else if (data.data && data.data.url) {
            url = data.data.url;
        } 
        // If data has FCM_MSG wrapper
        else if (data.FCM_MSG && data.FCM_MSG.fcmOptions && data.FCM_MSG.fcmOptions.link) {
            url = data.FCM_MSG.fcmOptions.link;
        } else if (data.FCM_MSG && data.FCM_MSG.data && data.FCM_MSG.data.url) {
            url = data.FCM_MSG.data.url;
        }
        // Direct URL property (fallback)
        else if (data.url) {
            url = data.url;
        }
    }
    
    console.log('[firebase-messaging-sw.js] Extracted URL to open:', url);
    const urlToOpen = new URL(url, self.location.origin).href;
    event.waitUntil(
        clients.matchAll({
            type: 'window',
            includeUncontrolled: true
        }).then((windowClients) => {
            let matchingClient = null;

            for (let i = 0; i < windowClients.length; i++) {
                const windowClient = windowClients[i];
                if (windowClient.url === urlToOpen) {
                    matchingClient = windowClient;
                    break;
                }
            }

            if (matchingClient) {
                return matchingClient.focus();
            } else {
                return clients.openWindow(urlToOpen);
            }
        })
    );
});
