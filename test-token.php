<?php

// Create a test script to help debug Sanctum authentication issues

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Set up a request with a token for testing
$token = $_GET['token'] ?? null;

if (!$token) {
    echo "No token provided. Use ?token=yourtoken in the URL.";
    exit;
}

echo "Testing token: " . $token . "<br>";

// Create a test request with the Authorization header
$request = Illuminate\Http\Request::create('/api/test-auth', 'GET');
$request->headers->set('Authorization', 'Bearer ' . $token);

// Try to authenticate the request
try {
    $user = null;
    $guard = auth('sanctum');
    
    if ($guard->check()) {
        $user = $guard->user();
        echo "Authentication successful!<br>";
        echo "User ID: " . $user->id . "<br>";
        echo "User Name: " . $user->name . "<br>";
        echo "User Email: " . $user->email . "<br>";
    } else {
        echo "Authentication failed!<br>";
        
        // Check if the token exists in the database
        $tokenParts = explode('|', $token);
        if (count($tokenParts) === 2) {
            $tokenId = $tokenParts[0];
            echo "Token ID: " . $tokenId . "<br>";
            
            $tokenModel = \Laravel\Sanctum\PersonalAccessToken::find($tokenId);
            if ($tokenModel) {
                echo "Token exists in database!<br>";
                echo "Tokenable ID: " . $tokenModel->tokenable_id . "<br>";
                echo "Tokenable Type: " . $tokenModel->tokenable_type . "<br>";
                
                // Check if user exists in SleekDB
                $userService = app(\App\Services\UserService::class);
                $sleekDbUser = $userService->findById($tokenModel->tokenable_id);
                if ($sleekDbUser) {
                    echo "User exists in SleekDB!<br>";
                    echo "<pre>" . print_r($sleekDbUser, true) . "</pre>";
                } else {
                    echo "User DOES NOT exist in SleekDB!<br>";
                }
            } else {
                echo "Token DOES NOT exist in database!<br>";
            }
        } else {
            echo "Invalid token format!<br>";
        }
    }
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "<br>";
}