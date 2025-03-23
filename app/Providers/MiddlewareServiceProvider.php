<?php

declare(strict_types=1);

namespace App\Providers;

use App\Http\Middleware\AdminMiddleware;
use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;

class MiddlewareServiceProvider extends ServiceProvider
{
    /**
     * Register any middleware services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any middleware services.
     */
    public function boot(): void
    {
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('admin', AdminMiddleware::class);
    }
}