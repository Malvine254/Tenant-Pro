<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Shared-hosting rewrites may execute public/index.php through an
        // internal /laravel-app/public path. Never let that filesystem path
        // leak into generated links, redirects, assets, or form actions.
        if ($this->app->environment('production')) {
            $rootUrl = rtrim((string) config('app.url'), '/');
            if (filter_var($rootUrl, FILTER_VALIDATE_URL)) {
                URL::forceRootUrl($rootUrl);
                if (parse_url($rootUrl, PHP_URL_SCHEME) === 'https') {
                    URL::forceScheme('https');
                }
            }
        }
    }
}
