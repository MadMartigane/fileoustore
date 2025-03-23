<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UserService
{
    /**
     * Create a new user.
     *
     * @param array $data
     * @return User
     */
    public function create(array $data): User
    {
        $user = User::create([
            'id' => uniqid('user_'),
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'is_admin' => $data['is_admin'] ?? false,
        ]);

        return $user;
    }

    /**
     * Update a user.
     *
     * @param string $id
     * @param array $data
     * @return User|null
     */
    public function update(string $id, array $data): ?User
    {
        $user = $this->findById($id);
        if (!$user) {
            return null;
        }

        if (isset($data['name'])) {
            $user->name = $data['name'];
        }

        if (isset($data['email'])) {
            $user->email = $data['email'];
        }

        if (isset($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        if (isset($data['is_admin'])) {
            $user->is_admin = (bool) $data['is_admin'];
        }

        $user->save();
        return $user;
    }

    /**
     * Find a user by ID.
     *
     * @param string $id
     * @return User|null
     */
    public function findById(string $id): ?User
    {
        return User::find($id);
    }

    /**
     * Find a user by email.
     *
     * @param string $email
     * @return User|null
     */
    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    /**
     * Delete a user.
     *
     * @param string $id
     * @return bool
     */
    public function delete(string $id): bool
    {
        $user = $this->findById($id);
        if (!$user) {
            return false;
        }

        return (bool) $user->delete();
    }

    /**
     * Get all users.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function all()
    {
        return User::all();
    }

    /**
     * Verify user credentials.
     *
     * @param string $email
     * @param string $password
     * @return User|null
     */
    public function verifyCredentials(string $email, string $password): ?User
    {
        $user = $this->findByEmail($email);
        
        if (!$user || !Hash::check($password, $user->password)) {
            return null;
        }
        
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

        // Delete any existing tokens for this user
        DB::table('password_reset_tokens')->where('email', $email)->delete();

        // Create new token
        $token = Str::random(64);
        DB::table('password_reset_tokens')->insert([
            'email' => $email,
            'token' => $token,
            'created_at' => now(),
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
        $tokenRecord = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->where('token', $token)
            ->first();

        if (!$tokenRecord) {
            return false;
        }

        // Check if token is expired (1 hour expiration)
        $tokenCreatedAt = strtotime($tokenRecord->created_at);
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
        $this->update($user->id, ['password' => $newPassword]);

        // Delete token
        DB::table('password_reset_tokens')->where('email', $email)->delete();

        return true;
    }
}