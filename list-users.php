<?php

// Script to list all users in SleekDB store

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Get all users
$userService = app(\App\Services\UserService::class);
$users = $userService->all();

echo "Found " . count($users) . " users in SleekDB store:\n";
foreach ($users as $user) {
    echo "ID: " . $user['id'] . "\n";
    echo "Name: " . $user['name'] . "\n";
    echo "Email: " . $user['email'] . "\n";
    echo "Admin: " . ($user['is_admin'] ? 'Yes' : 'No') . "\n";
    echo "Created at: " . $user['created_at'] . "\n";
    echo "---------------------------------------------------\n";
}