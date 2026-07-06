@extends('admin.layout')
@section('page-title', 'Add Tenant')

@section('content')
<div style="max-width:680px;">
    <h2 style="font-size:16px;font-weight:600;margin-bottom:16px;">New Tenant</h2>
    <div class="card">
        <form method="POST" action="{{ route('admin.tenants.store') }}">
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
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
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
            </div>
            @php
                $oldUnit = $units->firstWhere('id', old('unit_id'));
                $selectedProperty = old('property_id', request('property_id', $oldUnit?->property_id));
                if (!$selectedProperty && auth()->user()?->role?->name === 'LANDLORD' && $properties->isNotEmpty()) {
                    $selectedProperty = $properties->first()->id;
                }
            @endphp
            <div class="form-group">
                <label>Property</label>
                <select id="propertySelect" name="property_id" required {{ $properties->isEmpty() ? 'disabled' : '' }}>
                    <option value="">- Select Property -</option>
                    @foreach($properties as $property)
                        <option value="{{ $property->id }}" {{ $selectedProperty === $property->id ? 'selected' : '' }}>
                            {{ $property->name }}
                        </option>
                    @endforeach
                </select>
                @error('property_id')<div class="form-error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label>Assign Unit</label>
                <select id="unitSelect" name="unit_id" required {{ $units->isEmpty() ? 'disabled' : '' }}>
                    <option value="">- Select Available Unit -</option>
                    @foreach($units as $unit)
                        <option value="{{ $unit->id }}" data-property-id="{{ $unit->property_id }}" {{ old('unit_id') === $unit->id ? 'selected' : '' }}>
                            Unit {{ $unit->unit_number }} ({{ $unit->rent_amount_formatted }})
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
                <button type="submit" class="btn btn-primary" {{ $units->isEmpty() ? 'disabled' : '' }}>Save Tenant</button>
                <a href="{{ route('admin.tenants.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const property = document.getElementById('propertySelect');
    const unit = document.getElementById('unitSelect');
    const filterUnits = () => {
        const propertyId = property.value;
        Array.from(unit.options).forEach((option, index) => {
            if (index > 0) option.hidden = option.dataset.propertyId !== propertyId;
        });
        if (unit.selectedOptions[0]?.hidden) unit.value = '';
        unit.disabled = !propertyId;
    };
    property.addEventListener('change', filterUnits);
    filterUnits();
});
</script>
@endsection
