<?php

namespace App\Http\Middleware;

use App\Services\LandlordSubscriptionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountAccess
{
    public function __construct(
        private readonly LandlordSubscriptionService $subscriptionService,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (!$user->is_active) {
            return response()->json([
                'message' => 'Account is inactive.',
                'code' => 'ACCOUNT_SUSPENDED',
            ], 403);
        }

        if ($user->isLandlord()) {
            $evaluation = $this->subscriptionService->evaluate($user);
            if (!$evaluation['allowed']) {
                return response()->json(['message' => $evaluation['message'] ?? 'Your service subscription is inactive.'], 403);
            }
        }

        if (!$user->hasActiveServiceAccess()) {
            if ($user->isLandlord()) {
                $message = $this->subscriptionService->evaluate($user)['message'] ?? 'Your service subscription is inactive.';
                return response()->json(['message' => $message], 403);
            }

            return response()->json([
                'message' => 'Your services are temporarily unavailable because the property owner account is inactive or past due.',
                'code' => 'LANDLORD_ACCESS_SUSPENDED',
            ], 403);
        }

        return $next($request);
    }
}
