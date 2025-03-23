<?php

require __DIR__.'/vendor/autoload.php';

$app = require __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// First, let's create a test token
echo "=== Creating a test token ===\n";

// Get first user from SleekDB
$userService = app(\App\Services\UserService::class);
$users = $userService->all();

if (empty($users)) {
    echo "No users found. Please create a user first.\n";
    exit(1);
}

// Get the first user
$userData = $users[0];
echo "User data: " . json_encode($userData, JSON_PRETTY_PRINT) . "\n";

// Create a user model
$user = $userService->createUserModel($userData);
echo "User model created: " . $user->id . "\n";

// Create a token
try {
    $token = $user->createToken('test-token');
    $plainTextToken = $token->plainTextToken;
    echo "Token created: " . $plainTextToken . "\n";
    
    // Parse the token
    list($id, $token) = explode('|', $plainTextToken, 2);
    echo "Token ID: $id, Token: $token\n";
    
    // Find the token in the database
    $personalAccessToken = \Laravel\Sanctum\PersonalAccessToken::find($id);
    if ($personalAccessToken) {
        echo "Token record found in database\n";
        var_dump($personalAccessToken->toArray());
        
        // Test the original relationship
        echo "\n=== Testing tokenable relation ===\n";
        $tokenable = $personalAccessToken->tokenable;
        echo "Tokenable result: " . ($tokenable ? 'User found' : 'NULL') . "\n";
        
        if ($tokenable) {
            echo "User ID: " . $tokenable->id . "\n";
        } else {
            echo "Checking why tokenable is null...\n";
            
            // Manually try to find the user
            $reflectionClass = new ReflectionClass($personalAccessToken);
            $morphToRelation = $reflectionClass->getMethod('tokenable');
            $morphToRelation->setAccessible(true);
            
            // Get the relation without executing it
            $relation = $morphToRelation->invoke($personalAccessToken);
            echo "Relation class: " . get_class($relation) . "\n";
            
            // Check what Sanctum will look for
            echo "Looking for: {$personalAccessToken->tokenable_type} with ID {$personalAccessToken->tokenable_id}\n";
            
            // Try our direct query instead
            echo "\nDirect query from SleekDB:\n";
            $sleekDbDir = storage_path('sleekdb');
            $store = new \SleekDB\Store('users', $sleekDbDir, [
                'auto_cache' => true,
                'timeout' => false,
            ]);
            
            $foundUsers = $store->createQueryBuilder()
                ->where([['id', '=', $personalAccessToken->tokenable_id]])
                ->getQuery()
                ->fetch();
                
            if (!empty($foundUsers)) {
                echo "User found directly in SleekDB: " . json_encode($foundUsers[0], JSON_PRETTY_PRINT) . "\n";
            } else {
                echo "User NOT found in SleekDB with ID: {$personalAccessToken->tokenable_id}\n";
            }
        }
    } else {
        echo "Token not found in database\n";
    }
    
    // Test token validation
    echo "\n=== Testing token validation ===\n";
    
    // Create a request with the token
    $request = \Illuminate\Http\Request::create('/api/test-auth', 'GET');
    $request->headers->set('Authorization', "Bearer $plainTextToken");
    
    // Create a test route and have Laravel dispatch it
    $router = app('router');
    $router->get('/test-sanctum-token', function() {
        return ['user' => auth()->user() ? auth()->user()->id : 'not authenticated'];
    })->middleware('auth:sanctum');
    
    // Execute the request
    echo "Dispatching request to test route...\n";
    try {
        $response = app()->handle($request);
        echo "Response status: " . $response->getStatusCode() . "\n";
        echo "Response content: " . $response->getContent() . "\n";
    } catch (\Exception $e) {
        echo "Exception: " . $e->getMessage() . "\n";
    }
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}