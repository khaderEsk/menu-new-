<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Laravel Reverb Test</title>
</head>
<body>
    <h1>🛰️ Listening for OrderShipped...</h1>
    <div id="output"></div>

    <!-- إضافة مكتبة Pusher -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pusher/8.2.0/pusher.min.js"></script>

    <!-- Laravel Echo -->
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.11.3/dist/echo.iife.js"></script>

    <script>
        // إعداد Laravel Echo
        window.Pusher = Pusher;

        window.Echo = new Echo({
            broadcaster: 'pusher',
            key: 'bqfkpognxb0xxeax5bjc',
            cluster: 'mt1',
            wsHost: window.location.hostname,
            wsPort: 8080,
            forceTLS: false,
            disableStats: true,
            enabledTransports: ['ws', 'wss']
        });

        // الاستماع للقناة الخاصة
        // window.Echo.private('orders.1')
        //     .listen('OrderShipped', (e) => {
        //         console.log('📦 OrderShipped event received:', e);
        //         document.getElementById('output').innerText = '📦 Order shipped with data: ' + JSON.stringify(e);
        //     });

        // window.Echo.channel('orders')
        // .listen('OrderShipped', (e) => {
        //     console.log('📦 Public order shipped:', e);
        // });

        // window.Echo.channel('message')
        // .listen('TableUpdatedEvent', (e) => {
        //     console.log('📦 Public order shipped:', e);
        // });

        // window.Echo.channel('locationUpdated')
        // .listen('LocationUpdated', (e) => {
        //     console.log('📦 Public order shipped:', e);
        // });

        window.Echo.channel('restaurant46')
        .listen('TableUpdatedEvent', (e) => {
            console.log('📦 Public order shipped:', e);
        });

    </script>
</body>
</html>
