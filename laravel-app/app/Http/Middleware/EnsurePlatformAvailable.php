<?php

namespace App\Http\Middleware;

use App\Services\PlatformSettingsService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlatformAvailable
{
    public function __construct(private readonly PlatformSettingsService $settings) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->isAlwaysAvailable($request)) {
            return $next($request);
        }

        $maintenance = $this->settings->maintenance();
        if (! $maintenance['enabled']) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => $maintenance['message'],
                'code' => 'PLATFORM_MAINTENANCE',
                'retryable' => true,
            ], 503, ['Retry-After' => '300']);
        }

        return response()->view('maintenance', ['message' => $maintenance['message']], 503, [
            'Retry-After' => '300',
        ]);
    }

    private function isAlwaysAvailable(Request $request): bool
    {
        return $request->is(
            'admin',
            'admin/*',
            'api/health',
            'api/payments/mpesa/callback',
            'deployment-tools-once',
            'deployment-tools-once/*',
            'up',
        );
    }
}
