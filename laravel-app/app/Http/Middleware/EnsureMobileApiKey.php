<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMobileApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $expectedKey = (string) config('deployment.mobile_api_key', '');

        if ($expectedKey === '') {
            return response()->json([
                'message' => 'Mobile API key is not configured. Set MOBILE_API_KEY in .env, then clear and rebuild Laravel config cache.',
            ], 503);
        }

        $providedKey = (string) $request->header('X-Mobile-App-Key', '');

        if (!hash_equals($expectedKey, $providedKey)) {
            return response()->json([
                'message' => 'Invalid mobile API key.',
            ], 401);
        }

        return $next($request);
    }
}
