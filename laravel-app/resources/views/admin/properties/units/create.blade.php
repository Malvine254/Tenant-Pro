@extends('admin.layout')
@section('page-title', 'Add Unit')

@section('content')
<div style="max-width:600px;">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;">
        <a href="{{ route('admin.properties.show', $property) }}" style="color:#94a3b8;text-decoration:none;font-size:13px;">{{ $property->name }}</a>
        <span style="color:#cbd5e1;">/</span>
        <span style="font-weight:600;">Add Unit</span>
    </div>
    <div class="card">
        <form method="POST" action="{{ route('admin.properties.units.store', $property) }}">
            @csrf
            <div style="padding:12px 14px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:7px;margin-bottom:16px;color:#1e40af;font-size:13px;">
                To add several units, enter the first unit number and quantity. For example,
                <strong>101</strong> with quantity <strong>6</strong> creates units 101–106.
            </div>
            <div style="display:grid;grid-template-columns:2fr 1fr;gap:12px;">
                <div class="form-group">
                    <label>First Unit Number</label>
                    <input type="text" name="unit_number" value="{{ old('unit_number') }}" placeholder="e.g. 101 or A01" required>
                    @error('unit_number')<div class="form-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label>Number of Units</label>
                    <input type="number" name="units_count" value="{{ old('units_count', 1) }}" min="1" max="100" required>
                    @error('units_count')<div class="form-error">{{ $message }}</div>@enderror
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div class="form-group">
                    <label>Floor</label>
                    <input type="number" name="floor" value="{{ old('floor') }}">
                    @error('floor')<div class="form-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label>Monthly Rent (KES)</label>
                    <input type="number" name="rent_amount" value="{{ old('rent_amount') }}" min="0" step="0.01" required>
                    @error('rent_amount')<div class="form-error">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status" required>
                    @foreach(['AVAILABLE' => 'Available', 'OCCUPIED' => 'Occupied', 'UNDER_MAINTENANCE' => 'Under Maintenance'] as $value => $label)
                        <option value="{{ $value }}" {{ old('status', 'AVAILABLE') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @error('status')<div class="form-error">{{ $message }}</div>@enderror
            </div>
            <div style="display:flex;gap:10px;">
                <button type="submit" class="btn btn-primary">Create Unit(s)</button>
                <a href="{{ route('admin.properties.show', $property) }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
