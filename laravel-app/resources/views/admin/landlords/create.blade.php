@extends('admin.layout')
@section('page-title', 'Emergency Create Landlord')

@section('content')
<div style="max-width:600px;">
    <h2 style="font-size:16px;font-weight:600;margin-bottom:6px;">Emergency Create Landlord</h2>
    <p style="font-size:13px;color:#64748b;margin-bottom:16px;">Preferred production flow: invite the landlord by email so they set their own password. Use this form only for admin correction.</p>
    <div class="card">
        <form method="POST" action="{{ route('admin.landlords.store') }}">
            @csrf
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div class="form-group">
                    <label>First Name</label>
                    <input type="text" name="first_name" value="{{ old('first_name') }}" required>
                    @error('first_name')<div class="form-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" name="last_name" value="{{ old('last_name') }}" required>
                    @error('last_name')<div class="form-error">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" value="{{ old('email') }}" required>
                @error('email')<div class="form-error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label>Phone Number</label>
                <input type="text" name="phone_number" value="{{ old('phone_number') }}">
                @error('phone_number')<div class="form-error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label>Monthly TenantPro Subscription Fee (KSh)</label>
                <input type="number" name="monthly_service_fee" min="0" step="0.01" value="{{ old('monthly_service_fee', 2500) }}" required>
                <div style="font-size:12px;color:#94a3b8;margin-top:5px;">The first month remains a free trial. This amount applies when the subscription is renewed.</div>
                @error('monthly_service_fee')<div class="form-error">{{ $message }}</div>@enderror
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                    @error('password')<div class="form-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="password_confirmation" required>
                </div>
            </div>
            <div style="display:flex;gap:10px;">
                <button type="submit" class="btn btn-primary">Emergency Save Landlord</button>
                <a href="{{ route('admin.invitations.index', ['type' => 'LANDLORD']) }}" class="btn btn-secondary">Use Email Invite</a>
                <a href="{{ route('admin.landlords.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
