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
        // Ensure SleekDB storage directory exists
        $sleekDbDir = storage_path('sleekdb');
        if (!file_exists($sleekDbDir)) {
            mkdir($sleekDbDir, 0755, true);
        }
        
        // Sanctum configuration will be handled by the default Laravel settings
    }
}