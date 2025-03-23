<?php

// Direct test of Sanctum authentication without HTTP

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

echo "Testing Sanctum token authentication directly...\n";

// Step 1: Create a mock request with a token
$token = "14|51VrRhheHMAdktbAn4US1UGBGTsV9SLZZwZmX5Et655241cf";

$request = Illuminate\Http\Request::create('/api/test-auth', 'GET');
$request->headers->set('Authorization', 'Bearer ' . $token);

// Step 2: Manually authenticate with Sanctum
$guard = auth('sanctum');

// Debug the request
echo "Checking token: " . $request->bearerToken() . "\n";

if ($guard->check()) {
    $user = $guard->user();
    echo "Authentication successful!\n";
    echo "User ID: " . $user->id . "\n";
    echo "User Name: " . $user->name . "\n";
    echo "User Email: " . $user->email . "\n";
} else {
    echo "Authentication failed!\n";
    
    // Debug the token
    $tokenParts = explode('|', $token);
    if (count($tokenParts) === 2) {
        $tokenId = $tokenParts[0];
        echo "Token ID: " . $tokenId . "\n";
        
        // Check if token exists in database
        $tokenModel = \Laravel\Sanctum\Sanctum::$personalAccessTokenModel::find($tokenId);
        if ($tokenModel) {
            echo "Token exists in database!\n";
            echo "Token ID: " . $tokenModel->id . "\n";
            echo "Tokenable ID: " . $tokenModel->tokenable_id . "\n";
            echo "Tokenable Type: " . $tokenModel->tokenable_type . "\n";
            
            // Find the user in SleekDB
            $userService = app(\App\Services\UserService::class);
            $users = $userService->all();
            echo "Found " . count($users) . " users in SleekDB.\n";
            
            // Check if the tokenable_id exists in SleekDB
            $userFound = false;
            foreach ($users as $user) {
                if ($user['id'] === $tokenModel->tokenable_id) {
                    echo "Found matching user in SleekDB: " . $user['name'] . "\n";
                    $userFound = true;
                    break;
                }
            }
            
            if (!$userFound) {
                echo "User with ID " . $tokenModel->tokenable_id . " NOT found in SleekDB!\n";
            }
            
            // Try to manually create a user model
            $sleekDbDir = storage_path('sleekdb');
            $store = new SleekDB\Store('users', $sleekDbDir, [
                'auto_cache' => true,
                'timeout' => false,
            ]);
            
            $foundUsers = $store->createQueryBuilder()
                ->where([['id', '=', $tokenModel->tokenable_id]])
                ->getQuery()
                ->fetch();
                
            if (!empty($foundUsers)) {
                echo "User found in SleekDB directly!\n";
                $user = $foundUsers[0];
                echo "User data: " . json_encode($user) . "\n";
                
                // Create a user model
                $userModel = new \App\Models\User();
                $userModel->id = $user['id'];
                $userModel->name = $user['name'];
                $userModel->email = $user['email'];
                $userModel->is_admin = $user['is_admin'] ?? false;
                $userModel->exists = true;
                
                echo "User model created: " . json_encode($userModel->toArray()) . "\n";
                
                // Try to authenticate with this user model
                if ($guard->onceUsingId($userModel->id)) {
                    echo "Manual authentication successful!\n";
                } else {
                    echo "Manual authentication failed!\n";
                }
            } else {
                echo "User NOT found in SleekDB with direct query!\n";
            }
        } else {
            echo "Token does NOT exist in database!\n";
        }
    } else {
        echo "Invalid token format!\n";
    }
}