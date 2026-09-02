@extends('admin.layout')
@section('page-title', 'Choose New Password')

@section('content')
<div class="auth-shell">
    <div class="auth-brand"><img class="auth-brand-image" src="{{ asset('images/starmax-tenant-logo.png') }}" alt="Starmax Tenant Services"></div>
    <div class="card">
        <h2 style="margin-bottom:10px;font-size:18px;">Choose a new password</h2>
        <p style="font-size:13px;color:#94a3b8;margin-bottom:20px;">Use at least 10 characters with uppercase, lowercase, and a number.</p>
        <form method="POST" action="{{ route('admin.password.update') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div class="form-group"><label for="new-password-email">Email address</label><input id="new-password-email" type="email" name="email" value="{{ old('email', $email) }}" autocomplete="email" required autofocus>@error('email')<div class="form-error">{{ $message }}</div>@enderror</div>
            <div class="form-group"><label for="new-password">New password</label><input id="new-password" type="password" name="password" autocomplete="new-password" minlength="10" required>@error('password')<div class="form-error">{{ $message }}</div>@enderror</div>
            <div class="form-group"><label for="new-password-confirmation">Confirm new password</label><input id="new-password-confirmation" type="password" name="password_confirmation" autocomplete="new-password" minlength="10" required></div>
            <button type="submit" class="btn btn-primary" style="width:100%;" data-loading-text="Resetting password…">Reset password</button>
        </form>
    </div>
</div>
@endsection
