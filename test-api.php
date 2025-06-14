<?php

// Create a script to test the API with a Sanctum token

$token = "14|51VrRhheHMAdktbAn4US1UGBGTsV9SLZZwZmX5Et655241cf";
$baseUrl = "http://localhost:8000/api";

// Test endpoints with token
$endpoints = ["/test-auth", "/users", "/files"];

// Function to make API request
function makeRequest($url, $method = "GET", $token = null, $data = null)
{
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

    $headers = ["Accept: application/json"];

    if ($token) {
        $headers[] = "Authorization: Bearer {$token}";
    }

    if ($data) {
        $headers[] = "Content-Type: application/json";
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);

    curl_close($ch);

    return [
        "status" => $statusCode,
        "response" => $response ? json_decode($response, true) : null,
        "error" => $error,
    ];
}

// Test login to get a fresh token
echo "Testing login endpoint...\n";
$loginResult = makeRequest("{$baseUrl}/login", "POST", null, [
    "email" => "admin@example.com",
    "password" => "password123",
]);

echo "Login Status: " . $loginResult["status"] . "\n";
if ($loginResult["status"] === 200) {
    echo "Login successful!\n";
    if (isset($loginResult["response"]["token"])) {
        $token = $loginResult["response"]["token"];
        echo "New token: {$token}\n";
    }
} else {
    echo "Login failed!\n";
    echo "Response: " . print_r($loginResult["response"], true) . "\n";
}

echo "\n";

// Test each endpoint
foreach ($endpoints as $endpoint) {
    echo "Testing {$endpoint}...\n";
    $result = makeRequest("{$baseUrl}{$endpoint}", "GET", $token);

    echo "Status: " . $result["status"] . "\n";
    if ($result["error"]) {
        echo "Error: " . $result["error"] . "\n";
    }

    if ($result["response"]) {
        echo "Response: " . print_r($result["response"], true) . "\n";
    }

    echo "\n";
}
