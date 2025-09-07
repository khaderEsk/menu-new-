<?php

// Protect this with a secret key
$secret = 'my_secure_key';

if (!isset($_GET['key']) || $_GET['key'] !== $secret) {
    http_response_code(403);
    exit('Forbidden');
}

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

try {
    $code = $kernel->call('optimize');
    echo nl2br($kernel->output());
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
