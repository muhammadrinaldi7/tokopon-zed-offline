import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const cfg = (typeof window !== 'undefined' && window.reverbConfig) ? window.reverbConfig : {};
const key = import.meta.env.VITE_REVERB_APP_KEY || cfg.key;

if (key) {
    try {
        const isHttps = (import.meta.env.VITE_REVERB_SCHEME ?? cfg.scheme ?? (typeof window !== 'undefined' && window.location.protocol === 'https:' ? 'https' : 'http')) === 'https';
        const port = Number(import.meta.env.VITE_REVERB_PORT || cfg.port || (isHttps ? 443 : 80));

        window.Echo = new Echo({
            broadcaster: 'reverb',
            key: key,
            wsHost: import.meta.env.VITE_REVERB_HOST || cfg.host || (typeof window !== 'undefined' ? window.location.hostname : 'localhost'),
            wsPort: port,
            wssPort: port,
            forceTLS: isHttps,
            enabledTransports: ['ws', 'wss'],
        });
    } catch (e) {
        console.warn('Echo/Pusher init warning:', e);
    }
} else {
    console.warn('Reverb App Key is missing. WebSocket real-time updates are in fallback polling mode.');
}
