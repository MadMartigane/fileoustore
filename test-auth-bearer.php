<?php

// Test script to verify Bearer token authentication

// Step 1: Create a token for testing
require __DIR__.'/vendor/autoload.php';

$app = require __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Log what we're doing
echo "=== Creating a test token ===\n";

// Get first user from SleekDB
$userService = app(\App\Services\UserService::class);
$users = $userService->all();

if (empty($users)) {
    echo "No users found. Please create a user first.\n";
    exit(1);
}

// Get the first user and create a model from it
$user = $userService->createUserModel($users[0]);
echo "Using user: {$user->name} ({$user->id})\n";

// Create a token
$token = $user->createToken('test-token');
echo "Token created: {$token->plainTextToken}\n";

// Step 2: Test the token with the API
$bearerToken = $token->plainTextToken;

// Test using curl to call the /api/test-auth endpoint
$command = "curl -s -X GET http://localhost:8000/api/test-auth -H 'Accept: application/json' -H 'Authorization: Bearer {$bearerToken}'";
echo "\n=== Testing API endpoint with Bearer token ===\n";
echo "Command: $command\n\n";

// For testing in this script, we'll simulate the request
echo "=== Simulating the request in PHP ===\n";

// Create a request to test auth
$request = \Illuminate\Http\Request::create('/api/test-auth', 'GET');
$request->headers->set('Authorization', "Bearer {$bearerToken}");

// Get the auth middleware
$kernel = app(\Illuminate\Contracts\Http\Kernel::class);
$middleware = app()->make(\Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class);

// Apply middleware manually 
try {
    echo "Attempting to authenticate with token...\n";
    $middleware->handle($request, function ($req) {
        $user = $req->user();
        echo "Authentication result: " . ($user ? "SUCCESS - User: {$user->name}" : "FAILED - No user") . "\n";
        
        // Check the guards that succeeded
        echo "Auth guards: ";
        foreach(['web', 'api', 'sanctum'] as $guard) {
            if (\Illuminate\Support\Facades\Auth::guard($guard)->check()) {
                echo "{$guard}=✓ ";
            } else {
                echo "{$guard}=✗ ";
            }
        }
        echo "\n";
        
        return response()->json([
            'message' => 'Auth test complete',
            'authenticated' => $user ? true : false,
            'user' => $user ? $user->toArray() : null,
        ]);
    });
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Provide instructions for manual testing
echo "\n=== Instructions for manual testing ===\n";
echo "1. Start the Laravel server with: php artisan serve\n";
echo "2. Run this curl command in another terminal:\n\n";
echo "   $command\n\n";
echo "3. You should see a JSON response with authentication details\n";