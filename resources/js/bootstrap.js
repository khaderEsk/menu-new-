import Echo from 'laravel-echo';

window.Pusher = require('pusher-js');

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: 'local',
    wsHost: window.location.hostname,
    wsPort: 8080,
    forceTLS: false,
    disableStats: true,
});

window.Echo.private('orders.1')
    .listen('OrderShipped', (e) => {
        console.log('OrderShipped event received:', e);
    });
