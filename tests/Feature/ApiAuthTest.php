<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Storage;

class ApiAuthTest extends TestCase
{
    /**
     * Test user registration endpoint.
     */
    public function test_user_can_register(): void
    {
        Storage::fake('sleekdb');

        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'user' => [
                    'id',
                    'name',
                    'email',
                    'created_at',
                    'updated_at',
                ],
            ]);
    }

    /**
     * Test login endpoint.
     */
    public function test_user_can_login(): void
    {
        // This test assumes the user created in test_user_can_register exists
        // In a real test, you would set up the test data first

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user',
                'token',
            ]);
    }
}

