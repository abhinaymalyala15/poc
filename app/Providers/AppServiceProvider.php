<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // cPanel: domain docroot is often ~/public_html while the app lives in ~/poc.
        // Vite reads public_path('build'); without this, Laravel uses poc/public/build but
        // the browser loads /build/* from public_html → 404. Set LARAVEL_PUBLIC_PATH in .env.
        $path = env('LARAVEL_PUBLIC_PATH');
        if (is_string($path) && $path !== '') {
            $resolved = realpath($path);
            if ($resolved !== false && is_dir($resolved)) {
                $this->app->usePublicPath($resolved);
            }
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
