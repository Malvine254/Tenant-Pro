<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\LandlordSubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthAdminController extends Controller
{
    public function __construct(
        private readonly LandlordSubscriptionService $subscriptionService,
    ) {
    }

    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('admin.dashboard');
        }
        return view('admin.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            
            $role = Auth::user()?->role?->name;
            if (!in_array($role, ['SUPER_ADMIN', 'ADMIN', 'LANDLORD'], true)) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()
                    ->withErrors(['email' => 'This account does not have access to the admin portal.'])
                    ->onlyInput('email');
            }

            if (!Auth::user()?->is_active) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()
                    ->withErrors(['email' => 'This account is inactive. Contact a system administrator.'])
                    ->onlyInput('email');
            }

            if ($role === 'LANDLORD') {
                $evaluation = $this->subscriptionService->evaluate(Auth::user());
                if (!$evaluation['allowed']) {
                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    return back()
                        ->withErrors(['email' => $evaluation['message']])
                        ->onlyInput('email');
                }
            }

            $request->session()->regenerate();
            return redirect()->intended(route('admin.dashboard'));
        }

        return back()->withErrors(['email' => 'Invalid credentials.'])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('admin.login');
    }
}
