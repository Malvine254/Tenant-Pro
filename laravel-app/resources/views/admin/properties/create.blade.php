@extends('admin.layout')
@section('page-title', 'Add Property')

@section('content')
<div style="max-width:600px;">
    <h2 style="font-size:16px;font-weight:600;margin-bottom:16px;">New Property</h2>
    <div class="card">
        <form method="POST" action="{{ route('admin.properties.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="form-group">
                <label>Landlord</label>
                <select name="landlord_id" required {{ $landlords->isEmpty() ? 'disabled' : '' }}>
                    <option value="">- Select Landlord -</option>
                    @foreach($landlords as $landlord)
                        <option value="{{ $landlord->id }}" {{ old('landlord_id', request('landlord_id')) == $landlord->id ? 'selected' : '' }}>{{ $landlord->name }} ({{ $landlord->email }})</option>
                    @endforeach
                </select>
                @if($landlords->isEmpty())
                    <div class="form-error">No landlords exist yet. <a href="{{ route('admin.landlords.create') }}">Add a landlord first</a>.</div>
                @else
                    @if(auth()->user()?->role?->name !== 'LANDLORD')
                        <div style="font-size:12px;margin-top:5px;color:#64748b;"><a href="{{ route('admin.landlords.create') }}">Add a new landlord</a></div>
                    @endif
                @endif
                @error('landlord_id')<div class="form-error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label>Property Name</label>
                <input type="text" name="name" value="{{ old('name') }}" required>
                @error('name')<div class="form-error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="3">{{ old('description') }}</textarea>
            </div>

            <div class="form-group">
                <label>Property Cover Image</label>
                <input type="file" name="cover_image" accept="image/*" style="padding:8px;">
                <small style="color:#64748b;">JPG, PNG up to 5MB</small>
                @error('cover_image')<div class="form-error">{{ $message }}</div>@enderror
            </div>

            <div class="form-group" style="padding:14px;border:1px solid var(--line);border-radius:12px;background:rgba(79,70,229,.07);">
                <label style="display:flex;align-items:flex-start;gap:10px;margin:0;cursor:pointer;">
                    <input type="checkbox" name="is_publicly_listed" value="1" style="width:auto;margin-top:3px;" {{ old('is_publicly_listed') ? 'checked' : '' }}>
                    <span><strong style="display:block;">Publish on Starmax Homes</strong><small style="display:block;margin-top:4px;color:var(--muted);font-weight:400;line-height:1.5;">Show this property and its available units on the public tenant marketplace. A cover photo and at least one unit are required.</small></span>
                </label>
                @error('is_publicly_listed')<div class="form-error">{{ $message }}</div>@enderror
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div class="form-group">
                    <label>Address Line</label>
                    <input type="text" name="address_line" value="{{ old('address_line') }}" required>
                    @error('address_line')<div class="form-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label>City</label>
                    <input type="text" name="city" value="{{ old('city') }}" required>
                    @error('city')<div class="form-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label>State / County</label>
                    <input type="text" name="state" value="{{ old('state') }}">
                </div>
                <div class="form-group">
                    <label>Country</label>
                    <input type="text" name="country" value="{{ old('country', 'Kenya') }}">
                </div>
            </div>

            <div style="border-top:1px solid #e2e8f0;margin:6px 0 18px;padding-top:18px;">
                <h3 style="font-size:15px;font-weight:600;margin-bottom:4px;">Recurring tenant bills</h3>
                <p style="font-size:12px;color:#64748b;margin-bottom:14px;">Water and garbage invoices are created for every active tenant each month. Electricity is prepaid by default and is not added to the monthly invoice.</p>
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
                    <div class="form-group">
                        <label>Water fee (KES/month)</label>
                        <input type="number" name="water_monthly_fee" value="{{ old('water_monthly_fee', 0) }}" min="0" step="0.01" required>
                        @error('water_monthly_fee')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label>Garbage fee (KES/month)</label>
                        <input type="number" name="garbage_monthly_fee" value="{{ old('garbage_monthly_fee', 0) }}" min="0" step="0.01" required>
                        @error('garbage_monthly_fee')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label>Electricity billing</label>
                        <select name="electricity_billing_mode" required>
                            <option value="PREPAID" {{ old('electricity_billing_mode', 'PREPAID') === 'PREPAID' ? 'selected' : '' }}>Prepaid (default)</option>
                            <option value="POSTPAID" {{ old('electricity_billing_mode') === 'POSTPAID' ? 'selected' : '' }}>Postpaid</option>
                        </select>
                    </div>
                </div>
            </div>

            <div style="border-top:1px solid #e2e8f0;margin:6px 0 18px;padding-top:18px;">
                <h3 style="font-size:15px;font-weight:600;margin-bottom:4px;">Add Units Now <span style="font-weight:normal;color:#64748b;">(optional)</span></h3>
                <p style="font-size:12px;color:#64748b;margin-bottom:14px;">
                    Create the property and its initial units together. Example: first unit 101 and quantity 6 creates 101–106.
                </p>

                <div style="display:grid;grid-template-columns:2fr 1fr;gap:12px;">
                    <div class="form-group">
                        <label>First Unit Number</label>
                        <input type="text" name="first_unit_number" value="{{ old('first_unit_number') }}" placeholder="e.g. 101 or A01">
                        @error('first_unit_number')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label>Number of Units</label>
                        <input type="number" name="initial_units_count" value="{{ old('initial_units_count') }}" min="1" max="100" placeholder="e.g. 6">
                        @error('initial_units_count')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div class="form-group">
                        <label>Floor</label>
                        <input type="number" name="initial_floor" value="{{ old('initial_floor') }}" placeholder="e.g. 1">
                        @error('initial_floor')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label>Monthly Rent per Unit (KES)</label>
                        <input type="number" name="initial_rent_amount" value="{{ old('initial_rent_amount') }}" min="0" step="0.01" placeholder="e.g. 15000">
                        @error('initial_rent_amount')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>

            <div style="display:flex;gap:10px;">
                <button type="submit" class="btn btn-primary" {{ $landlords->isEmpty() ? 'disabled' : '' }}>Save Property &amp; Units</button>
                <a href="{{ route('admin.properties.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
