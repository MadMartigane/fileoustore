<?php

// A simple script to analyze the Sanctum token

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Get token from command line
$token = $argv[1] ?? "14|51VrRhheHMAdktbAn4US1UGBGTsV9SLZZwZmX5Et655241cf";

// Analyze the token
$tokenParts = explode('|', $token);
if (count($tokenParts) !== 2) {
    die("Invalid token format. Token should be in the format 'id|token'.\n");
}

// Get token ID
$tokenId = $tokenParts[0];
echo "Token ID: {$tokenId}\n";

// Check if token exists in database
$tokenModel = Laravel\Sanctum\PersonalAccessToken::find($tokenId);
if (!$tokenModel) {
    die("Token not found in database.\n");
}

echo "Token found in database!\n";
echo "Token created at: {$tokenModel->created_at}\n";
echo "Tokenable ID: {$tokenModel->tokenable_id}\n";
echo "Tokenable Type: {$tokenModel->tokenable_type}\n";

// Look up the user in SleekDB
$sleekDbDir = storage_path('sleekdb');
$store = new SleekDB\Store('users', $sleekDbDir, [
    'auto_cache' => true,
    'timeout' => false,
]);

$users = $store->createQueryBuilder()
    ->where([['id', '=', $tokenModel->tokenable_id]])
    ->getQuery()
    ->fetch();

if (empty($users)) {
    echo "User not found in SleekDB.\n";
} else {
    $user = $users[0];
    echo "User found in SleekDB:\n";
    echo "ID: {$user['id']}\n";
    echo "Name: {$user['name']}\n";
    echo "Email: {$user['email']}\n";
    echo "Is Admin: " . ($user['is_admin'] ? 'Yes' : 'No') . "\n";
}

// Try to manually verify the token
$hashableToken = $tokenParts[1];
$hash = hash('sha256', $hashableToken);

echo "\nToken Verification:\n";
echo "Database token hash: {$tokenModel->token}\n";
echo "Computed token hash: {$hash}\n";
echo "Hash matches: " . ($tokenModel->token === $hash ? 'Yes' : 'No') . "\n";