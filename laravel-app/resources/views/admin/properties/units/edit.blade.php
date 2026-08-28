@extends('admin.layout')
@section('page-title', 'Edit Unit')

@section('content')
<div style="max-width:600px;">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;">
        <a href="{{ route('admin.properties.show', $property) }}" style="color:#94a3b8;text-decoration:none;font-size:13px;">{{ $property->name }}</a>
        <span style="color:#cbd5e1;">/</span>
        <span style="font-weight:600;">Edit Unit {{ $unit->unit_number }}</span>
    </div>
    <div class="card">
        <form method="POST" action="{{ route('admin.properties.units.update', [$property, $unit]) }}">
            @csrf @method('PUT')
            <div class="form-group">
                <label>Unit Number</label>
                <input type="text" name="unit_number" value="{{ old('unit_number', $unit->unit_number) }}" required>
                @error('unit_number')<div class="form-error">{{ $message }}</div>@enderror
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div class="form-group">
                    <label>Floor</label>
                    <input type="number" name="floor" value="{{ old('floor', $unit->floor) }}">
                    @error('floor')<div class="form-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label>Monthly Rent (KES)</label>
                    <input type="number" name="rent_amount" value="{{ old('rent_amount', $unit->rent_amount) }}" min="0" step="0.01" required>
                    @error('rent_amount')<div class="form-error">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status" required>
                    @foreach(['AVAILABLE' => 'Available', 'OCCUPIED' => 'Occupied', 'UNDER_MAINTENANCE' => 'Under Maintenance'] as $value => $label)
                        <option value="{{ $value }}" {{ old('status', $unit->status) === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @error('status')<div class="form-error">{{ $message }}</div>@enderror
            </div>
            <div style="border-top:1px solid #e2e8f0;margin:8px 0 16px;padding-top:16px;">
                <h3 style="font-size:14px;margin:0 0 5px;">Unit utility overrides <span style="font-weight:normal;color:#64748b;">(optional)</span></h3>
                <p style="font-size:12px;color:#64748b;margin:0 0 12px;">Leave blank to use this property’s water and garbage fees. These values allow different charges per unit.</p>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div class="form-group"><label>Water fee (KES/month)</label><input type="number" name="water_monthly_fee" value="{{ old('water_monthly_fee', $unit->billing_overrides['water_monthly_fee'] ?? '') }}" min="0" step="0.01">@error('water_monthly_fee')<div class="form-error">{{ $message }}</div>@enderror</div>
                    <div class="form-group"><label>Garbage fee (KES/month)</label><input type="number" name="garbage_monthly_fee" value="{{ old('garbage_monthly_fee', $unit->billing_overrides['garbage_monthly_fee'] ?? '') }}" min="0" step="0.01">@error('garbage_monthly_fee')<div class="form-error">{{ $message }}</div>@enderror</div>
                </div>
            </div>
            <div style="display:flex;gap:10px;">
                <button type="submit" class="btn btn-primary">Update Unit</button>
                <a href="{{ route('admin.properties.show', $property) }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
