@extends('admin.layout')
@section('page-title', 'Edit Unit')

@section('content')
<div style="max-width:940px;">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;">
        <a href="{{ route('admin.properties.show', $property) }}" style="color:#94a3b8;text-decoration:none;font-size:13px;">{{ $property->name }}</a>
        <span style="color:#cbd5e1;">/</span>
        <span style="font-weight:600;">Edit Unit {{ $unit->unit_number }}</span>
    </div>
    <div class="card">
        @php($activeTab = old('unit_tab', 'details'))
        <div class="ui-tabs" role="tablist" aria-label="Edit unit sections" data-ui-tabs data-tab-param="unit_tab" data-initial-tab="{{ $activeTab }}">
            <button id="unit-tab-details" type="button" class="ui-tab {{ $activeTab === 'details' ? 'active' : '' }}" role="tab" aria-selected="{{ $activeTab === 'details' ? 'true' : 'false' }}" aria-controls="unit-panel-details" data-ui-tab="details" data-tab-panel="unit-panel-details">1. Unit details</button>
            <button id="unit-tab-billing" type="button" class="ui-tab {{ $activeTab === 'billing' ? 'active' : '' }}" role="tab" aria-selected="{{ $activeTab === 'billing' ? 'true' : 'false' }}" aria-controls="unit-panel-billing" data-ui-tab="billing" data-tab-panel="unit-panel-billing">2. Billing</button>
            <button id="unit-tab-listing" type="button" class="ui-tab {{ $activeTab === 'listing' ? 'active' : '' }}" role="tab" aria-selected="{{ $activeTab === 'listing' ? 'true' : 'false' }}" aria-controls="unit-panel-listing" data-ui-tab="listing" data-tab-panel="unit-panel-listing">3. Listing &amp; photos</button>
        </div>
        <form enctype="multipart/form-data" method="POST" action="{{ route('admin.properties.units.update', [$property, $unit]) }}">
            @csrf @method('PUT')
            <input type="hidden" name="unit_tab" value="{{ $activeTab }}">
            <section id="unit-panel-details" class="ui-tab-panel {{ $activeTab === 'details' ? 'active' : '' }}" role="tabpanel" aria-labelledby="unit-tab-details">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Unit Number</label>
                        <input type="text" name="unit_number" value="{{ old('unit_number', $unit->unit_number) }}" required>
                        @error('unit_number')<div class="form-error">{{ $message }}</div>@enderror
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
                    <div class="form-group">
                        <label>Floor</label>
                        <input type="number" name="floor" value="{{ old('floor', $unit->floor) }}">
                        @error('floor')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label>Bedrooms</label>
                        <input type="number" name="bedrooms" value="{{ old('bedrooms', $unit->bedrooms) }}" min="0" max="20" placeholder="e.g. 2">
                        <small>0 means studio and appears in the tenant app.</small>
                        @error('bedrooms')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label>Monthly Rent (KES)</label>
                        <input type="number" name="rent_amount" value="{{ old('rent_amount', $unit->rent_amount) }}" min="0" step="0.01" required>
                        @error('rent_amount')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                </div>
            </section>
            <section id="unit-panel-billing" class="ui-tab-panel {{ $activeTab === 'billing' ? 'active' : '' }}" role="tabpanel" aria-labelledby="unit-tab-billing" {{ $activeTab === 'billing' ? '' : 'hidden' }}>
                <h3 style="font-size:15px;margin:0 0 6px;">Unit utility overrides</h3>
                <p style="font-size:13px;color:var(--muted);margin:0 0 16px;">Leave blank to inherit this property’s water and garbage fees. Use this only for a unit with different charges.</p>
                <div class="form-grid">
                    <div class="form-group"><label>Water fee (KES/month)</label><input type="number" name="water_monthly_fee" value="{{ old('water_monthly_fee', $unit->billing_overrides['water_monthly_fee'] ?? '') }}" min="0" step="0.01">@error('water_monthly_fee')<div class="form-error">{{ $message }}</div>@enderror</div>
                    <div class="form-group"><label>Garbage fee (KES/month)</label><input type="number" name="garbage_monthly_fee" value="{{ old('garbage_monthly_fee', $unit->billing_overrides['garbage_monthly_fee'] ?? '') }}" min="0" step="0.01">@error('garbage_monthly_fee')<div class="form-error">{{ $message }}</div>@enderror</div>
                </div>
            </section>
            <section id="unit-panel-listing" class="ui-tab-panel {{ $activeTab === 'listing' ? 'active' : '' }}" role="tabpanel" aria-labelledby="unit-tab-listing" {{ $activeTab === 'listing' ? '' : 'hidden' }}>
                @include('admin.properties.units.marketplace-fields')
            </section>
            <div style="display:flex;gap:10px;">
                <button type="submit" class="btn btn-primary">Update Unit</button>
                <a href="{{ route('admin.properties.show', $property) }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
