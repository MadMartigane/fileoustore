<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class GenerateApiToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sanctum:token {email : The email of the user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a Sanctum API token for a user';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->argument('email');

        // Find the user
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User with email {$email} not found.");
            return 1;
        }

        // Revoke existing tokens
        $user->tokens()->delete();
        
        // Generate a new token
        $token = $user->createToken('api-token')->plainTextToken;

        $this->info("Generated token for user {$user->name} (ID: {$user->id}):");
        $this->newLine();
        $this->line($token);
        $this->newLine();
        $this->info("You can use this token in the Authorization header as 'Bearer {$token}'");

        return 0;
    }
}