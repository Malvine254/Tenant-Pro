@extends('admin.layout')
@section('page-title', 'Assign Tenant')

@section('content')
<div style="max-width:680px;">
    <h2 style="font-size:16px;font-weight:600;margin-bottom:16px;">Assign Existing Tenant Account</h2>
    <div class="card">
        <form method="POST" action="{{ route('admin.tenants.assign.store') }}">
            @csrf
            <div class="form-group">
                <label>Tenant Account</label>
                <select name="user_id" required {{ $tenantUsers->isEmpty() ? 'disabled' : '' }}>
                    <option value="">- Select Tenant -</option>
                    @foreach($tenantUsers as $tenantUser)
                        <option value="{{ $tenantUser->id }}" {{ old('user_id', request('user_id')) === $tenantUser->id ? 'selected' : '' }}>
                            {{ $tenantUser->name }} ({{ $tenantUser->email }})
                        </option>
                    @endforeach
                </select>
                @if($tenantUsers->isEmpty())
                    <div class="form-error">No unassigned tenant accounts found. Ask the tenant to register in the Android app or create a new tenant.</div>
                @endif
                @error('user_id')<div class="form-error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label>Available Unit</label>
                <select name="unit_id" required {{ $units->isEmpty() ? 'disabled' : '' }}>
                    <option value="">- Select Available Unit -</option>
                    @foreach($units as $unit)
                        <option value="{{ $unit->id }}" {{ old('unit_id') === $unit->id ? 'selected' : '' }}>
                            {{ $unit->property?->name }} - Unit {{ $unit->unit_number }} ({{ $unit->rent_amount_formatted }})
                        </option>
                    @endforeach
                </select>
                @if($units->isEmpty())
                    <div class="form-error">No available units found. Add a property unit first.</div>
                @endif
                @error('unit_id')<div class="form-error">{{ $message }}</div>@enderror
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div class="form-group">
                    <label>Move-in Date</label>
                    <input type="date" name="move_in_date" value="{{ old('move_in_date', now()->toDateString()) }}" required>
                    @error('move_in_date')<div class="form-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label>Move-out Date</label>
                    <input type="date" name="move_out_date" value="{{ old('move_out_date') }}">
                    @error('move_out_date')<div class="form-error">{{ $message }}</div>@enderror
                </div>
            </div>
            <div style="display:flex;gap:10px;">
                <button type="submit" class="btn btn-primary" {{ $tenantUsers->isEmpty() || $units->isEmpty() ? 'disabled' : '' }}>Assign Tenant</button>
                <a href="{{ route('admin.tenants.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
