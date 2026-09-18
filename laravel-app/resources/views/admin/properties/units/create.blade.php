@extends('admin.layout')
@section('page-title', 'Add Unit')

@section('content')
<div style="max-width:940px;">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;">
        <a href="{{ route('admin.properties.show', $property) }}" style="color:#94a3b8;text-decoration:none;font-size:13px;">{{ $property->name }}</a>
        <span style="color:#cbd5e1;">/</span>
        <span style="font-weight:600;">Add Unit</span>
    </div>
    <div class="card">
        @php($activeTab = old('unit_tab', 'details'))
        <div class="ui-tabs" role="tablist" aria-label="Add unit sections" data-ui-tabs data-tab-param="unit_tab" data-initial-tab="{{ $activeTab }}">
            <button id="create-unit-tab-details" type="button" class="ui-tab {{ $activeTab === 'details' ? 'active' : '' }}" role="tab" aria-selected="{{ $activeTab === 'details' ? 'true' : 'false' }}" aria-controls="create-unit-panel-details" data-ui-tab="details" data-tab-panel="create-unit-panel-details">1. Unit details</button>
            <button id="create-unit-tab-billing" type="button" class="ui-tab {{ $activeTab === 'billing' ? 'active' : '' }}" role="tab" aria-selected="{{ $activeTab === 'billing' ? 'true' : 'false' }}" aria-controls="create-unit-panel-billing" data-ui-tab="billing" data-tab-panel="create-unit-panel-billing">2. Billing</button>
            <button id="create-unit-tab-listing" type="button" class="ui-tab {{ $activeTab === 'listing' ? 'active' : '' }}" role="tab" aria-selected="{{ $activeTab === 'listing' ? 'true' : 'false' }}" aria-controls="create-unit-panel-listing" data-ui-tab="listing" data-tab-panel="create-unit-panel-listing">3. Listing details</button>
        </div>
        <form method="POST" action="{{ route('admin.properties.units.store', $property) }}">
            @csrf
            <input type="hidden" name="unit_tab" value="{{ $activeTab }}">
            <section id="create-unit-panel-details" class="ui-tab-panel {{ $activeTab === 'details' ? 'active' : '' }}" role="tabpanel" aria-labelledby="create-unit-tab-details">
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
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
                <div class="form-group">
                    <label>Floor</label>
                    <input type="number" name="floor" value="{{ old('floor') }}">
                    @error('floor')<div class="form-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label>Bedrooms</label>
                    <input type="number" name="bedrooms" value="{{ old('bedrooms') }}" min="0" max="20" placeholder="e.g. 2">
                    <small style="color:#64748b;">0 = studio. Shown to tenants on the public listing.</small>
                    @error('bedrooms')<div class="form-error">{{ $message }}</div>@enderror
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
            </section>
            <section id="create-unit-panel-billing" class="ui-tab-panel {{ $activeTab === 'billing' ? 'active' : '' }}" role="tabpanel" aria-labelledby="create-unit-tab-billing" {{ $activeTab === 'billing' ? '' : 'hidden' }}>
                <h3 style="font-size:15px;margin:0 0 6px;">Unit utility overrides</h3>
                <p style="font-size:13px;color:var(--muted);margin:0 0 16px;">Leave blank to inherit this property’s water and garbage fees. Use this only for units with different charges.</p>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div class="form-group"><label>Water fee (KES/month)</label><input type="number" name="water_monthly_fee" value="{{ old('water_monthly_fee') }}" min="0" step="0.01">@error('water_monthly_fee')<div class="form-error">{{ $message }}</div>@enderror</div>
                    <div class="form-group"><label>Garbage fee (KES/month)</label><input type="number" name="garbage_monthly_fee" value="{{ old('garbage_monthly_fee') }}" min="0" step="0.01">@error('garbage_monthly_fee')<div class="form-error">{{ $message }}</div>@enderror</div>
                </div>
            </section>
            <section id="create-unit-panel-listing" class="ui-tab-panel {{ $activeTab === 'listing' ? 'active' : '' }}" role="tabpanel" aria-labelledby="create-unit-tab-listing" {{ $activeTab === 'listing' ? '' : 'hidden' }}>
                @include('admin.properties.units.marketplace-fields')
            </section>
            <div style="display:flex;gap:10px;">
                <button type="submit" class="btn btn-primary">Create Unit(s)</button>
                <a href="{{ route('admin.properties.show', $property) }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
