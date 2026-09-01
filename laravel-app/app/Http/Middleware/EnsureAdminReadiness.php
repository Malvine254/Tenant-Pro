<?php

namespace App\Http\Middleware;

use App\Services\AdminReadinessService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminReadiness
{
    public function __construct(private readonly AdminReadinessService $readiness) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || $request->isMethodSafe()) {
            return $next($request);
        }

        $routeName = (string) $request->route()?->getName();
        if ($this->isCorrectionRoute($routeName)) {
            return $next($request);
        }

        $status = $this->readiness->for($user);
        if (! ($status['checkpoints']['profile']['complete'] ?? true)) {
            return redirect()->route('admin.settings.index', ['tab' => 'account'])
                ->with('error', 'Complete your account profile before performing operational actions.');
        }

        if ($user->isLandlord()
            && in_array($routeName, [
                'admin.invitations.tenants.store',
                'admin.tenants.store',
                'admin.tenants.assign.store',
            ], true)
            && ! ($status['checkpoints']['payment']['complete'] ?? false)) {
            $target = $user->isLandlordOwner() ? ['tab' => 'payment'] : [];

            return redirect()->route('admin.settings.index', $target)
                ->with('error', $status['checkpoints']['payment']['message']);
        }

        if ($user->role?->name === 'SUPER_ADMIN'
            && in_array($routeName, ['admin.settings.daraja.test', 'admin.mpesa.sandbox-test.store'], true)
            && ! ($status['checkpoints']['daraja']['complete'] ?? false)) {
            return redirect()->route('admin.settings.index', ['tab' => 'daraja'])
                ->with('error', 'Complete all Daraja credentials before testing M-Pesa operations.');
        }

        return $next($request);
    }

    private function isCorrectionRoute(string $routeName): bool
    {
        return in_array($routeName, [
            'admin.logout',
            'admin.settings.account',
            'admin.settings.password',
            'admin.settings.payment',
            'admin.settings.daraja',
            'admin.settings.tenant-preferences',
            'admin.settings.maintenance',
        ], true);
    }
}
