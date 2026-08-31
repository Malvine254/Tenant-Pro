@extends('admin.layout')
@section('page-title', 'Reset Password')

@section('content')
<div class="auth-shell">
    <div class="auth-brand"><span class="brand-mark" aria-hidden="true">TP</span><strong>TenantPro</strong></div>
    <div class="card">
        <h2 style="margin-bottom:10px;font-size:18px;">Reset your password</h2>
        <p style="font-size:13px;color:#94a3b8;line-height:1.5;margin-bottom:20px;">Enter your admin or landlord account email and we will send a secure reset link.</p>
        @if(session('status'))<div class="alert-success" role="status">{{ session('status') }}</div>@endif
        <form method="POST" action="{{ route('admin.password.email') }}">
            @csrf
            <div class="form-group">
                <label for="reset-email">Email address</label>
                <input id="reset-email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required autofocus>
                @error('email')<div class="form-error">{{ $message }}</div>@enderror
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;" data-loading-text="Sending reset link…">Email reset link</button>
        </form>
        <div style="text-align:center;margin-top:14px;"><a href="{{ route('admin.login') }}" style="font-size:13px;">Back to sign in</a></div>
    </div>
</div>
@endsection
