@extends('admin.layout')
@section('page-title', 'Login')

@section('content')
<div style="max-width:380px;margin:60px auto;">
    <div class="card">
        <h2 style="margin-bottom:20px;font-size:18px;">Admin Login</h2>
        <div style="margin-bottom:14px;padding:10px 12px;border:1px solid rgba(255,255,255,.35);background:rgba(96,165,250,.12);border-radius:10px;font-size:12px;color:#dbeafe;line-height:1.5;">
            Access policy: TENANT accounts are blocked from this portal. Only SUPER_ADMIN, ADMIN, and LANDLORD can sign in.
        </div>
        <form method="POST" action="{{ url()->current() }}">
            @csrf
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus>
                @error('email')<div class="form-error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
                @error('password')<div class="form-error">{{ $message }}</div>@enderror
            </div>
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:16px;color:#e2e8f0;">
                <input type="checkbox" name="remember" id="remember">
                <label for="remember" style="font-size:13px;font-weight:normal;">Remember me</label>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;padding:10px;">Sign In</button>
        </form>

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
