<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\LandlordSubscriptionService;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

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

    public function showForgotPassword()
    {
        return view('admin.auth.forgot-password');
    }

    public function sendPasswordResetLink(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'email']]);
        $user = User::query()->with('role')->whereRaw('LOWER(email) = ?', [strtolower(trim($data['email']))])->first();

        if ($user && in_array($user->role?->name, ['SUPER_ADMIN', 'ADMIN', 'LANDLORD'], true)) {
            Password::sendResetLink(['email' => $user->email]);
        }

        return back()->with('status', 'If an eligible account exists for that email, a reset link has been sent.');
    }

    public function showResetPassword(Request $request, string $token)
    {
        return view('admin.auth.reset-password', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function resetPassword(Request $request)
    {
        $data = $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $eligible = User::query()->with('role')
            ->whereRaw('LOWER(email) = ?', [strtolower(trim($data['email']))])
            ->first();
        if (!$eligible || !in_array($eligible->role?->name, ['SUPER_ADMIN', 'ADMIN', 'LANDLORD'], true)) {
            return back()->withInput($request->only('email'))->withErrors(['email' => 'This reset link is not valid for the admin portal.']);
        }

        $status = Password::reset($data, function (User $user, string $password) {
            $user->forceFill([
                'password' => Hash::make($password),
                'remember_token' => Str::random(60),
            ])->save();
        });

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('admin.login')->with('status', 'Password reset successfully. Please sign in.');
        }

        return back()->withInput($request->only('email'))->withErrors(['email' => __($status)]);
    }
}
