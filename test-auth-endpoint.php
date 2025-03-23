<?php

// Create a PHP script that simulates Laravel's routing to test auth

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Get the token from command line argument
$token = $argv[1] ?? null;

if (!$token) {
    echo "No token provided. Use: php test-auth-endpoint.php YOUR_TOKEN\n";
    exit;
}

echo "Testing with token: $token\n";

// Create a request with the token
$request = Illuminate\Http\Request::create('/api/test-auth', 'GET');
$request->headers->set('Authorization', 'Bearer ' . $token);

// Let Laravel handle the request
$response = $kernel->handle($request);

// Output the result
echo "Status code: " . $response->getStatusCode() . "\n";
echo "Content: " . $response->getContent() . "\n";

// Clean up
$kernel->terminate($request, $response);