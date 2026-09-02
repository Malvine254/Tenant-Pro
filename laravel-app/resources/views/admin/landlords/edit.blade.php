@extends('admin.layout')
@section('page-title', 'Edit Landlord')

@section('content')
<div style="max-width:600px;">
    <h2 style="font-size:16px;font-weight:600;margin-bottom:16px;">Edit Landlord</h2>
    <div class="card">
        <form method="POST" action="{{ route('admin.landlords.update', $landlord) }}">
            @csrf @method('PUT')
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div class="form-group">
                    <label>First Name</label>
                    <input type="text" name="first_name" value="{{ old('first_name', $landlord->first_name) }}" required>
                    @error('first_name')<div class="form-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" name="last_name" value="{{ old('last_name', $landlord->last_name) }}" required>
                    @error('last_name')<div class="form-error">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" value="{{ old('email', $landlord->email) }}" required>
                @error('email')<div class="form-error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label>Phone Number</label>
                <input type="text" name="phone_number" value="{{ old('phone_number', $landlord->phone_number) }}">
                @error('phone_number')<div class="form-error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label>Monthly Starmax Tenant Services Subscription Fee (KSh)</label>
                <input type="number" name="monthly_service_fee" min="0" step="0.01" value="{{ old('monthly_service_fee', $landlord->monthly_service_fee) }}" required>
                <div style="font-size:12px;color:#94a3b8;margin-top:5px;">Renewal confirmation emails and the subscription dashboard use this amount.</div>
                @error('monthly_service_fee')<div class="form-error">{{ $message }}</div>@enderror
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="password">
                    @error('password')<div class="form-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label>Confirm New Password</label>
                    <input type="password" name="password_confirmation">
                </div>
            </div>
            <div class="form-group" style="display:flex;align-items:center;gap:8px;">
                <input type="checkbox" name="is_active" value="1" id="is_active" style="width:auto;" {{ old('is_active', $landlord->is_active) ? 'checked' : '' }}>
                <label for="is_active" style="margin:0;">Active account</label>
            </div>
            <div style="display:flex;gap:10px;">
                <button type="submit" class="btn btn-primary">Update Landlord</button>
                <a href="{{ route('admin.landlords.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
