@extends('admin.layout')
@section('page-title', 'Login')

@section('content')
<div style="max-width:380px;margin:60px auto;">
    <div class="card">
        <h2 style="margin-bottom:20px;font-size:18px;">Admin Login</h2>
        <form method="POST" action="{{ route('admin.login.post') }}">
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
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:16px;">
                <input type="checkbox" name="remember" id="remember">
                <label for="remember" style="font-size:13px;font-weight:normal;">Remember me</label>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;padding:10px;">Sign In</button>
        </form>
    </div>
</div>
@endsection
