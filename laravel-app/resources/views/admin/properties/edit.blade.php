@extends('admin.layout')
@section('page-title', 'Edit Property')

@section('content')
<div style="max-width:1100px;">
    <h2 style="font-size:16px;font-weight:600;margin-bottom:16px;">Edit: {{ $property->name }}</h2>
    <div class="card">
        <form method="POST" action="{{ route('admin.properties.update', $property) }}" enctype="multipart/form-data">
            @csrf @method('PUT')
            <div class="form-group">
                <label>Landlord</label>
                <select name="landlord_id" required>
                    @foreach($landlords as $landlord)
                        <option value="{{ $landlord->id }}" {{ old('landlord_id', $property->landlord_id) == $landlord->id ? 'selected' : '' }}>{{ $landlord->name }} ({{ $landlord->email }})</option>
                    @endforeach
                </select>
                @if(auth()->user()?->role?->name !== 'LANDLORD')
                    <div style="font-size:12px;margin-top:5px;color:#64748b;"><a href="{{ route('admin.landlords.create') }}">Add a new landlord</a></div>
                @endif
                @error('landlord_id')<div class="form-error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label>Property Name</label>
                <input type="text" name="name" value="{{ old('name', $property->name) }}" required>
                @error('name')<div class="form-error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="3">{{ old('description', $property->description) }}</textarea>
            </div>
            <div class="form-group">
                <label>Property Cover Image</label>
                @if($property->cover_image_url)
                    <div style="margin-bottom:10px;">
                        <img src="{{ $property->cover_image_url }}" style="max-width:200px;height:auto;border-radius:4px;">
                    </div>
                @endif
                <input type="file" name="cover_image" accept="image/*" style="padding:8px;">
                <small style="color:#64748b;">JPG, PNG up to 5MB</small>
                @if($property->cover_image_url)
                    <label style="display:flex;align-items:center;gap:8px;margin-top:9px;font-weight:normal;">
                        <input type="checkbox" name="remove_cover_image" value="1" style="width:auto;" {{ old('remove_cover_image') ? 'checked' : '' }}>
                        Remove the current cover image
                    </label>
                @endif
                @error('cover_image')<div class="form-error">{{ $message }}</div>@enderror
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div class="form-group">
                    <label>Address Line</label>
                    <input type="text" name="address_line" value="{{ old('address_line', $property->address_line) }}" required>
                </div>
                <div class="form-group">
                    <label>City</label>
                    <input type="text" name="city" value="{{ old('city', $property->city) }}" required>
                </div>
                <div class="form-group">
                    <label>State / County</label>
                    <input type="text" name="state" value="{{ old('state', $property->state) }}">
                </div>
                <div class="form-group">
                    <label>Country</label>
                    <input type="text" name="country" value="{{ old('country', $property->country) }}">
                </div>
            </div>
            <div style="border-top:1px solid #e2e8f0;margin:6px 0 18px;padding-top:18px;">
                <h3 style="font-size:15px;font-weight:600;margin-bottom:4px;">Recurring tenant bills</h3>
                <p style="font-size:12px;color:#64748b;margin-bottom:14px;">Water and garbage invoices are issued monthly for every active tenancy. Electricity defaults to prepaid and is not automatically invoiced.</p>
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
                    <div class="form-group">
                        <label>Water fee (KES/month)</label>
                        <input type="number" name="water_monthly_fee" value="{{ old('water_monthly_fee', $property->billing_settings['water_monthly_fee'] ?? 0) }}" min="0" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>Garbage fee (KES/month)</label>
                        <input type="number" name="garbage_monthly_fee" value="{{ old('garbage_monthly_fee', $property->billing_settings['garbage_monthly_fee'] ?? 0) }}" min="0" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>Electricity billing</label>
                        <select name="electricity_billing_mode" required>
                            @php($electricityMode = old('electricity_billing_mode', $property->billing_settings['electricity_billing_mode'] ?? 'PREPAID'))
                            <option value="PREPAID" {{ $electricityMode === 'PREPAID' ? 'selected' : '' }}>Prepaid (default)</option>
                            <option value="POSTPAID" {{ $electricityMode === 'POSTPAID' ? 'selected' : '' }}>Postpaid</option>
                        </select>
                    </div>
                </div>
            </div>
            <div style="border-top:1px solid #e2e8f0;margin:6px 0 18px;padding-top:18px;">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;margin-bottom:14px;">
                    <div>
                        <h3 style="font-size:15px;font-weight:600;margin-bottom:4px;">Units and monthly pricing</h3>
                        <p style="font-size:12px;color:#64748b;margin:0;">Edit every unit here. New rent applies to future billing; invoices already issued keep their recorded amount.</p>
                    </div>
                    <a href="{{ route('admin.properties.units.create', $property) }}" class="btn btn-secondary" style="white-space:nowrap;">+ Add Unit</a>
                </div>

                @error('units')<div class="form-error" style="margin-bottom:12px;">{{ $message }}</div>@enderror

                @forelse($property->units as $index => $unit)
                    <input type="hidden" name="units[{{ $index }}][id]" value="{{ $unit->id }}">
                    <div style="border:1px solid #e2e8f0;border-radius:8px;padding:14px;margin-bottom:12px;background:#f8fafc;">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:11px;">
                            <strong style="font-size:14px;">Unit {{ $unit->unit_number }}</strong>
                            @if($unit->tenant)
                                <span class="badge badge-blue">Tenant assigned</span>
                            @endif
                        </div>
                        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:12px;">
                            <div class="form-group">
                                <label>Unit Number</label>
                                <input type="text" name="units[{{ $index }}][unit_number]" value="{{ old("units.$index.unit_number", $unit->unit_number) }}" required>
                                @error("units.$index.unit_number")<div class="form-error">{{ $message }}</div>@enderror
                            </div>
                            <div class="form-group">
                                <label>Floor</label>
                                <input type="number" name="units[{{ $index }}][floor]" value="{{ old("units.$index.floor", $unit->floor) }}">
                                @error("units.$index.floor")<div class="form-error">{{ $message }}</div>@enderror
                            </div>
                            <div class="form-group">
                                <label>Monthly Rent (KES)</label>
                                <input type="number" name="units[{{ $index }}][rent_amount]" value="{{ old("units.$index.rent_amount", $unit->rent_amount) }}" min="0" step="0.01" required>
                                @error("units.$index.rent_amount")<div class="form-error">{{ $message }}</div>@enderror
                            </div>
                            <div class="form-group">
                                <label>Status</label>
                                @php($unitStatus = old("units.$index.status", $unit->status))
                                <select name="units[{{ $index }}][status]" required>
                                    @foreach(['AVAILABLE' => 'Available', 'OCCUPIED' => 'Occupied', 'UNDER_MAINTENANCE' => 'Under Maintenance'] as $value => $label)
                                        <option value="{{ $value }}" {{ $unitStatus === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error("units.$index.status")<div class="form-error">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;">
                            <div class="form-group" style="margin-bottom:0;">
                                <label>Water override (KES/month, optional)</label>
                                <input type="number" name="units[{{ $index }}][water_monthly_fee]" value="{{ old("units.$index.water_monthly_fee", $unit->billing_overrides['water_monthly_fee'] ?? '') }}" min="0" step="0.01" placeholder="Use property fee">
                                @error("units.$index.water_monthly_fee")<div class="form-error">{{ $message }}</div>@enderror
                            </div>
                            <div class="form-group" style="margin-bottom:0;">
                                <label>Garbage override (KES/month, optional)</label>
                                <input type="number" name="units[{{ $index }}][garbage_monthly_fee]" value="{{ old("units.$index.garbage_monthly_fee", $unit->billing_overrides['garbage_monthly_fee'] ?? '') }}" min="0" step="0.01" placeholder="Use property fee">
                                @error("units.$index.garbage_monthly_fee")<div class="form-error">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                @empty
                    <div style="border:1px dashed #cbd5e1;border-radius:8px;padding:18px;text-align:center;color:#64748b;">No units have been added to this property yet.</div>
                @endforelse
            </div>
            <div style="display:flex;gap:10px;">
                <button type="submit" class="btn btn-primary">Save Property &amp; Units</button>
                <a href="{{ route('admin.properties.show', $property) }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
