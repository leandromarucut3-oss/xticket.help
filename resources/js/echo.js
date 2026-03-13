import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

// Configure Pusher with modern storage API
const pusherConfig = {
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: 'ap1',
    forceTLS: true,
    enableTransports: ['ws', 'wss'],
};

// Use modern navigator.storage API if available
if ('storage' in navigator && 'persist' in navigator.storage) {
    navigator.storage.persist().catch(() => {
        console.warn('Persistent storage permission denied');
    });
}

window.Echo = new Echo(pusherConfig);
