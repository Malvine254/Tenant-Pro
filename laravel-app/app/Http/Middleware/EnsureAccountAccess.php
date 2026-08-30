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
                if ($this->isRestrictedAccountRouteAllowed($request)) {
                    return $next($request);
                }

                return response()->json([
                    'message' => $evaluation['message'] ?? 'Your service subscription is inactive.',
                    'code' => 'SUBSCRIPTION_PAST_DUE',
                    'subscriptionStatus' => $evaluation['status'] ?? LandlordSubscriptionService::STATUS_PAST_DUE,
                    'servicePaidUntil' => $user->service_paid_until?->toISOString(),
                    'trialEndsAt' => $user->trial_ends_at?->toISOString(),
                ], 403);
            }
        }

        if (!$user->hasActiveServiceAccess()) {
            if ($user->isLandlord()) {
                $message = $this->subscriptionService->evaluate($user)['message'] ?? 'Your service subscription is inactive.';
                return response()->json(['message' => $message], 403);
            }

            // A tenant still owns their account when their landlord is suspended.
            // Keep only account, notification and logout routes available while
            // preventing access to landlord, unit, billing and support data.
            if ($this->isRestrictedTenantRouteAllowed($request)) {
                return $next($request);
            }

            return response()->json([
                'message' => 'Your services are temporarily unavailable because the property owner account is inactive or past due.',
                'code' => 'LANDLORD_ACCESS_SUSPENDED',
            ], 403);
        }

        return $next($request);
    }

    private function isRestrictedTenantRouteAllowed(Request $request): bool
    {
        return $this->isRestrictedAccountRouteAllowed($request);
    }

    private function isRestrictedAccountRouteAllowed(Request $request): bool
    {
        return match (true) {
            $request->isMethod('GET') && $request->is('api/auth/me') => true,
            in_array($request->method(), ['GET', 'PATCH'], true) && $request->is('api/users/me/profile') => true,
            $request->isMethod('POST') && $request->is('api/users/me/password') => true,
            $request->isMethod('POST') && $request->is('api/users/me/profile-image') => true,
            $request->isMethod('POST') && $request->is('api/users/device-token') => true,
            $request->isMethod('POST') && $request->is('api/auth/logout') => true,
            $request->isMethod('GET') && $request->is('api/notifications') => true,
            in_array($request->method(), ['PATCH', 'POST', 'DELETE'], true) && $request->is('api/notifications/*') => true,
            default => false,
        };
    }
}
