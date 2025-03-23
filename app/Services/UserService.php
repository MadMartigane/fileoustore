<?php

declare(strict_types=1);

namespace App\Services;

use SleekDB\Store;
use Illuminate\Support\Facades\Hash;

class UserService
{
    private Store $store;

    /**
     * Create a new UserService instance.
     */
    public function __construct()
    {
        $databaseDir = storage_path('sleekdb');
        
        // Ensure the directory exists
        if (!file_exists($databaseDir)) {
            mkdir($databaseDir, 0755, true);
        }
        
        $this->store = new Store('users', $databaseDir, [
            'auto_cache' => true,
            'timeout' => false,
        ]);
    }

    /**
     * Create a new user.
     *
     * @param array $data
     * @return array
     */
    public function create(array $data): array
    {
        $userData = [
            'id' => uniqid('user_'),
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'is_admin' => $data['is_admin'] ?? false,
            'email_verified_at' => null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        return $this->store->insert($userData);
    }

    /**
     * Update a user.
     *
     * @param string $id
     * @param array $data
     * @return array|null
     */
    public function update(string $id, array $data): ?array
    {
        $user = $this->findById($id);
        if (!$user) {
            return null;
        }

        $userData = [
            'id' => $user['id'],
        ];

        if (isset($data['name'])) {
            $userData['name'] = $data['name'];
        }

        if (isset($data['email'])) {
            $userData['email'] = $data['email'];
        }

        if (isset($data['password'])) {
            $userData['password'] = Hash::make($data['password']);
        }

        if (isset($data['is_admin'])) {
            $userData['is_admin'] = (bool) $data['is_admin'];
        }

        $userData['updated_at'] = date('Y-m-d H:i:s');

        return $this->store->update($userData);
    }

    /**
     * Find a user by ID.
     *
     * @param string $id
     * @return array|null
     */
    public function findById(string $id): ?array
    {
        return $this->store->findById($id);
    }

    /**
     * Find a user by email.
     *
     * @param string $email
     * @return array|null
     */
    public function findByEmail(string $email): ?array
    {
        $results = $this->store->createQueryBuilder()
            ->where([['email', '=', $email]])
            ->limit(1)
            ->getQuery()
            ->fetch();
        
        return !empty($results) ? $results[0] : null;
    }

    /**
     * Delete a user.
     *
     * @param string $id
     * @return bool
     */
    public function delete(string $id): bool
    {
        return $this->store->deleteById($id);
    }

    /**
     * Get all users.
     *
     * @return array
     */
    public function all(): array
    {
        return $this->store->findAll();
    }

    /**
     * Verify user credentials.
     *
     * @param string $email
     * @param string $password
     * @return array|null
     */
    public function verifyCredentials(string $email, string $password): ?array
    {
        $user = $this->findByEmail($email);
        
        if (!$user || !Hash::check($password, $user['password'])) {
            return null;
        }
        
        return $user;
    }
    
    /**
     * Create a Laravel User model from database array.
     *
     * @param array $userData
     * @return \App\Models\User
     */
    public function createUserModel(array $userData): \App\Models\User
    {
        $user = new \App\Models\User();
        $user->id = $userData['id'];
        $user->name = $userData['name'];
        $user->email = $userData['email'];
        $user->is_admin = $userData['is_admin'] ?? false;
        
        return $user;
    }

    /**
     * Create password reset token.
     *
     * @param string $email
     * @return string|null
     */
    public function createPasswordResetToken(string $email): ?string
    {
        $user = $this->findByEmail($email);
        if (!$user) {
            return null;
        }

        // Create token store if not exists
        $databaseDir = storage_path('sleekdb');
        if (!file_exists($databaseDir)) {
            mkdir($databaseDir, 0755, true);
        }
        
        $tokenStore = new Store('password_reset_tokens', $databaseDir, [
            'auto_cache' => true,
            'timeout' => false,
        ]);

        // Delete any existing tokens for this user
        $existingTokens = $tokenStore->createQueryBuilder()
            ->where([['email', '=', $email]])
            ->getQuery()
            ->fetch();
            
        foreach ($existingTokens as $token) {
            $tokenStore->deleteById($token['_id']);
        }

        // Create new token
        $token = bin2hex(random_bytes(32));
        $tokenStore->insert([
            'email' => $email,
            'token' => $token,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return $token;
    }

    /**
     * Verify password reset token.
     *
     * @param string $email
     * @param string $token
     * @return bool
     */
    public function verifyPasswordResetToken(string $email, string $token): bool
    {
        $databaseDir = storage_path('sleekdb');
        if (!file_exists($databaseDir)) {
            mkdir($databaseDir, 0755, true);
        }
        
        $tokenStore = new Store('password_reset_tokens', $databaseDir, [
            'auto_cache' => true,
            'timeout' => false,
        ]);

        $result = $tokenStore->createQueryBuilder()
            ->where([
                ['email', '=', $email],
                ['token', '=', $token],
            ])
            ->limit(1)
            ->getQuery()
            ->fetch();

        if (empty($result)) {
            return false;
        }

        $result = $result[0];

        // Check if token is expired (1 hour expiration)
        $tokenCreatedAt = strtotime($result['created_at']);
        if ((time() - $tokenCreatedAt) > 3600) {
            return false;
        }

        return true;
    }

    /**
     * Reset password using token.
     *
     * @param string $email
     * @param string $token
     * @param string $newPassword
     * @return bool
     */
    public function resetPassword(string $email, string $token, string $newPassword): bool
    {
        if (!$this->verifyPasswordResetToken($email, $token)) {
            return false;
        }

        $user = $this->findByEmail($email);
        if (!$user) {
            return false;
        }

        // Update password
        $this->update($user['id'], ['password' => $newPassword]);

        // Delete token
        $databaseDir = storage_path('sleekdb');
        if (!file_exists($databaseDir)) {
            mkdir($databaseDir, 0755, true);
        }
        
        $tokenStore = new Store('password_reset_tokens', $databaseDir, [
            'auto_cache' => true,
            'timeout' => false,
        ]);
        
        $tokens = $tokenStore->createQueryBuilder()
            ->where([['email', '=', $email]])
            ->getQuery()
            ->fetch();
            
        foreach ($tokens as $tokenData) {
            $tokenStore->deleteById($tokenData['_id']);
        }

        return true;
    }
}