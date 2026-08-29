<?php

namespace App\Http\Middleware;

use App\Services\LandlordSubscriptionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminRole
{
    public function __construct(
        private readonly LandlordSubscriptionService $subscriptionService,
    ) {
    }

    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(401);
        }

        $role = $user->role?->name;
        if (!$role || !in_array($role, $roles, true)) {
            abort(403, 'You are not authorized to access this area.');
        }

        abort_if(!$user->is_active, 403, 'Your account is suspended.');

        if ($role === 'LANDLORD') {
            $evaluation = $this->subscriptionService->evaluate($user);
            if (!$evaluation['allowed']) {
                abort(403, $evaluation['message'] ?? 'Your subscription is not active.');
            }
        }

        return $next($request);
    }
}
