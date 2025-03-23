<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Illuminate\Http\Request::create(
        '/api/login', 
        'POST',
        [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]
    )
);

echo "Status Code: " . $response->getStatusCode() . "\n";
echo "Content: " . $response->getContent() . "\n";