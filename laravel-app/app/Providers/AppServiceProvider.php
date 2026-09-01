<?php

namespace App\Providers;

use App\Services\AdminReadinessService;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

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
        View::composer('admin.layout', function ($view): void {
            $user = auth()->user();
            $view->with([
                'adminReadiness' => $user ? app(AdminReadinessService::class)->for($user) : null,
                'adminUnreadNotifications' => $user
                    ? $user->appNotifications()->where('is_read', false)->count()
                    : 0,
                'adminNotificationPreview' => $user
                    ? $user->appNotifications()->latest()->limit(5)->get()
                    : collect(),
            ]);
        });

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
