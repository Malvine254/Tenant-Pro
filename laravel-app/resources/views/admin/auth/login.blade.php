@extends('admin.layout')
@section('page-title', 'Login')

@section('content')
<div class="auth-shell">
    <div class="auth-brand"><img class="auth-brand-image" src="{{ asset('images/starmax-tenant-logo.png') }}" alt="Starmax Tenant Services"></div>
    <div class="card">
        <h2 style="margin-bottom:6px;font-size:22px;">Welcome back</h2>
        <p class="muted" style="font-size:13px;margin-bottom:20px;">Sign in to your secure property operations workspace.</p>
        <form method="POST" action="{{ url()->current() }}">
            @csrf
            <div class="form-group">
                <label for="admin-email">Email address</label>
                <input id="admin-email" type="email" name="email" value="{{ old('email') }}" autocomplete="username" required autofocus>
                @error('email')<div class="form-error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label for="admin-password">Password</label>
                <input id="admin-password" type="password" name="password" autocomplete="current-password" required>
                @error('password')<div class="form-error">{{ $message }}</div>@enderror
            </div>
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:16px;color:#e2e8f0;">
                <input type="checkbox" name="remember" id="remember">
                <label for="remember" style="font-size:13px;font-weight:normal;">Remember me</label>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;" data-loading-text="Signing in…">Sign in securely</button>
        </form>
        <div style="text-align:center;margin-top:14px;">
            <a href="{{ route('admin.password.request') }}" style="font-size:13px;">Forgot password?</a>
        </div>

        @if(app()->environment('local', 'testing'))
            <div style="margin-top:14px;padding:10px 12px;border:1px dashed rgba(255,255,255,.32);background:rgba(255,255,255,.03);border-radius:10px;">
                <div style="font-size:11px;font-weight:800;letter-spacing:.04em;text-transform:uppercase;color:#94a3b8;margin-bottom:6px;">Demo Accounts (Local)</div>
                <div style="font-size:12px;color:#e2e8f0;line-height:1.6;">
                    SUPER_ADMIN: {{ env('SUPER_ADMIN_EMAIL', 'superadmin@starmaxltd.com') }}<br>
                    ADMIN: {{ env('DEMO_ADMIN_EMAIL', 'demo.admin@starmaxltd.com') }}<br>
                    LANDLORD: {{ env('DEMO_LANDLORD_EMAIL', 'demo.landlord@starmaxltd.com') }}<br>
                    Password: {{ env('DEMO_ACCOUNT_PASSWORD', 'Demo@1234') }}
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
