import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

const pageScheme = window.location.protocol === 'https:' ? 'https' : 'http';
const appUrl = (import.meta.env.VITE_APP_URL ?? window.location.origin).replace(/\/$/, '');
const appOrigin = new URL(appUrl, window.location.origin);
const configuredHost = import.meta.env.VITE_REVERB_HOST ?? appOrigin.hostname;
const reverbScheme = import.meta.env.VITE_REVERB_SCHEME ?? appOrigin.protocol.replace(':', '') ?? pageScheme;
const defaultPort = appOrigin.port
    ? Number(appOrigin.port)
    : (reverbScheme === 'https' ? 443 : 80);
const reverbHost = ['127.0.0.1', 'localhost', '0.0.0.0'].includes(configuredHost)
    ? appOrigin.hostname
    : configuredHost;
const reverbPort = Number(import.meta.env.VITE_REVERB_PORT ?? defaultPort);

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: reverbHost,
    wsPort: reverbPort,
    wssPort: reverbPort,
    forceTLS: reverbScheme === 'https',
    authEndpoint: `${appOrigin.origin}/broadcasting/auth`,
    enabledTransports: ['ws', 'wss'],
});
