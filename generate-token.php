<?php

// Script to generate a token for a user directly

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Get all users
$userService = app(\App\Services\UserService::class);

// Find a specific user (using the user_id parameter)
$userId = $_GET['user_id'] ?? 'user_67df0e221c000'; // Default to Admin User
$user = null;

$sleekDbDir = storage_path('sleekdb');
$store = new SleekDB\Store('users', $sleekDbDir, [
    'auto_cache' => true,
    'timeout' => false,
]);

// Find user by using createQueryBuilder (since findById doesn't support string IDs)
$users = $store->createQueryBuilder()
    ->where([['id', '=', $userId]])
    ->getQuery()
    ->fetch();

if (empty($users)) {
    echo "User with ID {$userId} not found!\n";
    exit(1);
}

$user = $users[0];
echo "Found user: {$user['name']} (ID: {$user['id']})\n";

// Create a User model
$userModel = $userService->createUserModel($user);

// Generate a token
$token = $userModel->createToken('api-token')->plainTextToken;

echo "Generated token: {$token}\n";
echo "Use this token in the Authorization header as 'Bearer {$token}'\n";