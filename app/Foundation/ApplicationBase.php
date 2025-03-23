<?php

declare(strict_types=1);

namespace App\Foundation;

use Illuminate\Foundation\Application as LaravelApplication;

class ApplicationBase extends LaravelApplication
{
    /**
     * Get the namespace for the application.
     *
     * @return string
     */
    public function getNamespace(): string
    {
        return 'App\\';
    }
}