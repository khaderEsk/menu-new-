<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Laravel Reverb Test - Sender</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pusher/8.2.0/pusher.min.js"></script>

    <!-- Laravel Echo -->
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.11.3/dist/echo.iife.js"></script>
</head>
<body>
    <h1>🛰️ Sending Location Update...</h1>
    <h1>إرسال الموقع باستخدام Whisper</h1>
    <button id="sendLocation">إرسال الموقع</button>
    <!-- إضافة مكتبة Pusher -->

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

        const channel =  window.Echo.private('locationUpdated');

        // إرسال البيانات عند الضغط على الزر
        document.getElementById('sendLocation').addEventListener('click', () => {
            channel.whisper('LocationUpdated', {
                user_id: 1,
                latitude: 25.276987,
                longitude: 55.296249,
                token: 'abc123'
            });
        });

    </script>
</body>
</html>
