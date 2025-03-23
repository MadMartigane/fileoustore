<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\File;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use SleekDB\Store;

class MigrateToSqlite extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:from-sleekdb';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate data from SleekDB to SQLite';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Make sure the migrations have been run
        $this->info('Checking if SQLite tables exist');
        try {
            if (!Schema::hasTable('users')) {
                $this->error('SQLite tables not found. Please run migrations first: php artisan migrate');
                return 1;
            }
        } catch (\Exception $e) {
            $this->error('Error checking SQLite tables: ' . $e->getMessage());
            $this->error('Please run migrations first: php artisan migrate');
            return 1;
        }

        // Check if SleekDB is installed
        if (!class_exists('\SleekDB\Store')) {
            $this->error('SleekDB not found. Please run: composer require rakibtg/sleekdb');
            return 1;
        }

        $this->info('Starting migration from SleekDB to SQLite');

        // Migrate users
        $this->migrateUsers();

        // Migrate files
        $this->migrateFiles();

        // Migrate password reset tokens
        $this->migratePasswordResetTokens();

        $this->info('Migration completed successfully!');

        return 0;
    }

    /**
     * Migrate users from SleekDB to SQLite.
     */
    private function migrateUsers()
    {
        $this->info('Migrating users...');

        $sleekDbDir = storage_path('sleekdb');
        if (!file_exists($sleekDbDir)) {
            $this->warn('SleekDB directory not found. Skipping user migration.');
            return;
        }

        try {
            $store = new Store('users', $sleekDbDir, [
                'auto_cache' => true,
                'timeout' => false,
            ]);

            $users = $store->findAll();
            $count = 0;

            foreach ($users as $userData) {
                // Skip if the user already exists in the database
                if (User::where('email', $userData['email'])->exists()) {
                    $this->warn("User with email {$userData['email']} already exists. Skipping.");
                    continue;
                }

                $user = new User();
                $user->id = $userData['id'];
                $user->name = $userData['name'];
                $user->email = $userData['email'];
                $user->password = $userData['password'];
                $user->is_admin = $userData['is_admin'] ?? false;
                $user->email_verified_at = $userData['email_verified_at'] ?? null;
                $user->created_at = $userData['created_at'] ?? null;
                $user->updated_at = $userData['updated_at'] ?? null;
                $user->save();

                $count++;
            }

            $this->info("Migrated $count users");
        } catch (\Exception $e) {
            $this->error('Error migrating users: ' . $e->getMessage());
        }
    }

    /**
     * Migrate files from SleekDB to SQLite.
     */
    private function migrateFiles()
    {
        $this->info('Migrating files...');

        $sleekDbDir = storage_path('sleekdb');
        if (!file_exists($sleekDbDir)) {
            $this->warn('SleekDB directory not found. Skipping file migration.');
            return;
        }

        try {
            $store = new Store('files', $sleekDbDir, [
                'auto_cache' => true,
                'timeout' => false,
            ]);

            $files = $store->findAll();
            $count = 0;

            foreach ($files as $fileData) {
                // Skip if the file already exists in the database
                if (File::where('id', $fileData['id'])->exists()) {
                    $this->warn("File with ID {$fileData['id']} already exists. Skipping.");
                    continue;
                }

                $file = new File();
                $file->id = $fileData['id'];
                $file->name = $fileData['name'];
                $file->path = $fileData['path'];
                $file->mime_type = $fileData['mime_type'];
                $file->size = $fileData['size'];
                $file->owner_id = $fileData['owner_id'];
                $file->shared_with = $fileData['shared_with'] ?? [];
                $file->permissions = $fileData['permissions'] ?? [];
                $file->created_at = $fileData['created_at'] ?? null;
                $file->updated_at = $fileData['updated_at'] ?? null;
                $file->save();

                $count++;
            }

            $this->info("Migrated $count files");
        } catch (\Exception $e) {
            $this->error('Error migrating files: ' . $e->getMessage());
        }
    }

    /**
     * Migrate password reset tokens from SleekDB to SQLite.
     */
    private function migratePasswordResetTokens()
    {
        $this->info('Migrating password reset tokens...');

        $sleekDbDir = storage_path('sleekdb');
        if (!file_exists($sleekDbDir)) {
            $this->warn('SleekDB directory not found. Skipping token migration.');
            return;
        }

        try {
            $store = new Store('password_reset_tokens', $sleekDbDir, [
                'auto_cache' => true,
                'timeout' => false,
            ]);

            $tokens = $store->findAll();
            $count = 0;

            foreach ($tokens as $tokenData) {
                // Skip if we already have a token for this email
                if (DB::table('password_reset_tokens')->where('email', $tokenData['email'])->exists()) {
                    $this->warn("Token for email {$tokenData['email']} already exists. Skipping.");
                    continue;
                }

                DB::table('password_reset_tokens')->insert([
                    'email' => $tokenData['email'],
                    'token' => $tokenData['token'],
                    'created_at' => $tokenData['created_at'] ?? now(),
                ]);

                $count++;
            }

            $this->info("Migrated $count password reset tokens");
        } catch (\Exception $e) {
            $this->error('Error migrating password reset tokens: ' . $e->getMessage());
        }
    }
}