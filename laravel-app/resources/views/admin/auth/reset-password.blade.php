@extends('admin.layout')
@section('page-title', 'Choose New Password')

@section('content')
<div style="max-width:380px;margin:60px auto;">
    <div class="card">
        <h2 style="margin-bottom:10px;font-size:18px;">Choose a new password</h2>
        <p style="font-size:13px;color:#94a3b8;margin-bottom:20px;">Use at least 8 characters.</p>
        <form method="POST" action="{{ route('admin.password.update') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div class="form-group"><label>Email address</label><input type="email" name="email" value="{{ old('email', $email) }}" required autofocus>@error('email')<div class="form-error">{{ $message }}</div>@enderror</div>
            <div class="form-group"><label>New password</label><input type="password" name="password" required>@error('password')<div class="form-error">{{ $message }}</div>@enderror</div>
            <div class="form-group"><label>Confirm new password</label><input type="password" name="password_confirmation" required></div>
            <button type="submit" class="btn btn-primary" style="width:100%;padding:10px;">Reset password</button>
        </form>
    </div>
</div>
@endsection
