import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

// Configure Pusher with environment variables
const pusherKey = import.meta.env.VITE_PUSHER_APP_KEY;
const pusherCluster = import.meta.env.VITE_PUSHER_APP_CLUSTER || 'mt1';

const pusherConfig = {
    broadcaster: 'pusher',
    key: pusherKey,
    cluster: pusherCluster,
    forceTLS: true,
    enableTransports: ['ws', 'wss'],
};

// Use modern navigator.storage API if available
if ('storage' in navigator && 'persist' in navigator.storage) {
    navigator.storage.persist().catch(() => {
        console.warn('Persistent storage permission denied');
    });
}

if (pusherKey) {
    window.Echo = new Echo(pusherConfig);
} else {
    console.warn('Pusher not configured: VITE_PUSHER_APP_KEY missing');
}
