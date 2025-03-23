<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\FileStore;
use App\Services\UserService;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register FileStore as a singleton
        $this->app->singleton(FileStore::class, function ($app) {
            return new FileStore();
        });

        // Register UserService as a singleton
        $this->app->singleton(UserService::class, function ($app) {
            return new UserService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Ensure storage directories exist
        $filesDir = storage_path('app/files');
        if (!file_exists($filesDir)) {
            mkdir($filesDir, 0755, true);
        }
        
        // Use the standard Personal Access Token model
        Sanctum::usePersonalAccessTokenModel(\Laravel\Sanctum\PersonalAccessToken::class);
    }
}