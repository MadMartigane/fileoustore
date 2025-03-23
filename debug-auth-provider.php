<?php

require __DIR__.'/vendor/autoload.php';

$app = require __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Debug Auth providers
echo "=== Auth Configuration ===\n";
$authConfig = config('auth');
echo "Default Guard: " . $authConfig['defaults']['guard'] . "\n";
echo "Default Provider: " . $authConfig['guards'][$authConfig['defaults']['guard']]['provider'] . "\n\n";

echo "=== Guards ===\n";
foreach ($authConfig['guards'] as $name => $config) {
    echo "Guard: $name\n";
    echo "  Driver: " . $config['driver'] . "\n";
    echo "  Provider: " . $config['provider'] . "\n";
}
echo "\n";

echo "=== Providers ===\n";
foreach ($authConfig['providers'] as $name => $config) {
    echo "Provider: $name\n";
    echo "  Driver: " . $config['driver'] . "\n";
    if (isset($config['model'])) {
        echo "  Model: " . $config['model'] . "\n";
    }
}
echo "\n";

// Check if our provider is registered
echo "=== Auth Providers in Container ===\n";
try {
    // Attempt to create our custom provider
    $provider = \Illuminate\Support\Facades\Auth::createUserProvider('sleekdb');
    if ($provider === null) {
        echo "Error: SleekDB provider returned null - provider not found or not registered correctly\n";
        
        // Directly check the providers in the Auth factory
        $reflectionClass = new \ReflectionClass(\Illuminate\Support\Facades\Auth::getFacadeRoot());
        $reflectionProperty = $reflectionClass->getProperty('customProviderCreators');
        $reflectionProperty->setAccessible(true);
        $providerCreators = $reflectionProperty->getValue(\Illuminate\Support\Facades\Auth::getFacadeRoot());
        
        echo "Custom providers defined: " . json_encode(array_keys($providerCreators ?? [])) . "\n";
    } else {
        echo "SleekDB provider created successfully: " . get_class($provider) . "\n";
    }
} catch (\Exception $e) {
    echo "Error creating SleekDB provider: " . $e->getMessage() . "\n";
}

// Check if the AuthServiceProvider was booted
echo "\n=== Service Providers Status ===\n";
$providers = app()->getLoadedProviders();
foreach ($providers as $provider => $loaded) {
    if (strpos($provider, 'Auth') !== false || strpos($provider, 'Provider') !== false) {
        echo "$provider: " . ($loaded ? 'Loaded' : 'Not Loaded') . "\n";
    }
}

// Test token validation
echo "\n=== Sanctum Token Test ===\n";
// Try to get a user record
$userService = app(\App\Services\UserService::class);
$users = $userService->all();
if (count($users) > 0) {
    $user = $userService->createUserModel($users[0]);
    echo "Creating test token for user: " . $user->id . "\n";
    
    try {
        $token = $user->createToken('test-token');
        echo "Token created: " . $token->plainTextToken . "\n";
        
        // Now try to validate this token
        $tokenId = explode('|', $token->plainTextToken)[0];
        echo "Token ID: $tokenId\n";
        
        $personalAccessToken = \Laravel\Sanctum\PersonalAccessToken::find($tokenId);
        if ($personalAccessToken) {
            echo "Token found in database\n";
            
            // Try to get the user from token
            $tokenable = $personalAccessToken->tokenable;
            echo "Tokenable user: " . ($tokenable ? $tokenable->id : 'NULL') . "\n";
        } else {
            echo "Token not found in database\n";
        }
    } catch (\Exception $e) {
        echo "Error creating/testing token: " . $e->getMessage() . "\n";
        echo "Stack trace: " . $e->getTraceAsString() . "\n";
    }
} else {
    echo "No users found for token test\n";
}