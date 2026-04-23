import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

const pageScheme = window.location.protocol === 'https:' ? 'https' : 'http';
const defaultPort = window.location.port
    ? Number(window.location.port)
    : (pageScheme === 'https' ? 443 : 80);
const configuredHost = import.meta.env.VITE_REVERB_HOST ?? window.location.hostname;
const reverbHost = ['127.0.0.1', 'localhost', '0.0.0.0'].includes(configuredHost)
    ? window.location.hostname
    : configuredHost;
const reverbPort = Number(import.meta.env.VITE_REVERB_PORT ?? defaultPort);
const reverbScheme = import.meta.env.VITE_REVERB_SCHEME ?? pageScheme;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: reverbHost,
    wsPort: reverbPort,
    wssPort: reverbPort,
    forceTLS: reverbScheme === 'https',
    authEndpoint: '/broadcasting/auth',
    enabledTransports: ['ws', 'wss'],
});
