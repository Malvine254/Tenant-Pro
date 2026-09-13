@extends('admin.layout')

@section('content')
<div class="admin-page-header">
    <div>
        <h2>Conduct Unit Inspection</h2>
        <p>Record room conditions, key handovers, and meter readings at move-in or move-out.</p>
    </div>
    <div class="admin-actions">
        <a href="{{ route('admin.inspections.index') }}" class="btn btn-secondary">Back to Inspections</a>
    </div>
</div>

<div class="card" style="max-width: 800px;">
    <form method="POST" action="{{ route('admin.inspections.store') }}">
        @csrf
        
        <div class="section-heading">Basic Information</div>
        <div class="form-grid">
            <div class="form-group">
                <label>Property <span style="color:#f87171;">*</span></label>
                <select name="property_id" id="selectProperty" required onchange="filterUnitsByProperty()">
                    <option value="">Select property</option>
                    @foreach($properties as $prop)
                        <option value="{{ $prop->id }}">{{ $prop->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Unit / Room <span style="color:#f87171;">*</span></label>
                <select name="unit_id" id="selectUnit" required>
                    <option value="">Select unit</option>
                    @foreach($properties as $prop)
                        @foreach($prop->units as $unit)
                            <option value="{{ $unit->id }}" data-property="{{ $prop->id }}" data-tenant="{{ $unit->tenant?->user_id }}" data-tenant-name="{{ $unit->tenant?->user?->name }}">
                                {{ $prop->name }} - Unit {{ $unit->unit_number }} ({{ $unit->tenant?->user?->name ?? 'Vacant' }})
                            </option>
                        @endforeach
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Inspection Type <span style="color:#f87171;">*</span></label>
                <select name="type" required>
                    <option value="MOVE_IN">Move-In Inspection (Check-in)</option>
                    <option value="MOVE_OUT">Move-Out Inspection (Check-out &amp; Deposit review)</option>
                    <option value="PERIODIC">Periodic Routine Inspection</option>
                </select>
            </div>
            <div class="form-group">
                <label>Inspection Date <span style="color:#f87171;">*</span></label>
                <input type="date" name="inspection_date" value="{{ date('Y-m-d') }}" required />
            </div>
            <div class="form-group field-wide">
                <label>Inspector Name / Property Manager <span style="color:#f87171;">*</span></label>
                <input type="text" name="inspector_name" value="{{ auth()->user()->name }}" required />
            </div>
        </div>

        <div class="section-heading" style="margin-top: 18px;">Initial Utility Meter Readings</div>
        <div class="form-grid">
            <div class="form-group">
                <label>💧 Water Sub-Meter Reading (Units)</label>
                <input type="number" step="0.01" name="meter_reading_water" placeholder="e.g. 142.50" />
            </div>
            <div class="form-group">
                <label>⚡ Power Meter / Token Balance (Units)</label>
                <input type="number" step="0.01" name="meter_reading_electricity" placeholder="e.g. 45.00" />
            </div>
        </div>

        <div class="section-heading" style="margin-top: 18px;">Room &amp; Fixtures Condition Checklist</div>
        <div style="background: rgba(15,23,42,0.6); border: 1px solid rgba(148,163,184,0.18); border-radius: 12px; padding: 14px; margin-bottom: 18px;">
            <div style="display: grid; grid-template-columns: 2fr 2fr 1.5fr 2fr; gap: 10px; font-weight: 800; font-size: 11px; text-transform: uppercase; color: var(--muted); margin-bottom: 8px;">
                <div>Area / Room</div>
                <div>Item Inspected</div>
                <div>Condition</div>
                <div>Remarks / Notes</div>
            </div>

            @php
                $defaultItems = [
                    ['cat' => 'Living Room', 'item' => 'Walls, Ceilings & Paint'],
                    ['cat' => 'Living Room', 'item' => 'Windows & Curtain Rods'],
                    ['cat' => 'Kitchen', 'item' => 'Sink, Taps & Drains'],
                    ['cat' => 'Kitchen', 'item' => 'Cabinets & Worktops'],
                    ['cat' => 'Bathroom', 'item' => 'Shower / Instant Heater'],
                    ['cat' => 'Bathroom', 'item' => 'Toilet System & Flush'],
                    ['cat' => 'Bedrooms', 'item' => 'Doors, Locks & 2 Keys Handover'],
                    ['cat' => 'Electrical', 'item' => 'Sockets, Switches & Bulbs'],
                ];
            @endphp

            @foreach($defaultItems as $idx => $def)
            <div style="display: grid; grid-template-columns: 2fr 2fr 1.5fr 2fr; gap: 10px; align-items: center; margin-bottom: 10px;">
                <input type="text" name="items[{{ $idx }}][category]" value="{{ $def['cat'] }}" required style="font-size: 12px;" />
                <input type="text" name="items[{{ $idx }}][item_name]" value="{{ $def['item'] }}" required style="font-size: 12px;" />
                <select name="items[{{ $idx }}][condition]" style="font-size: 12px;">
                    <option value="GOOD">Good / Intact</option>
                    <option value="FAIR">Fair / Normal Wear</option>
                    <option value="DAMAGED">Damaged / Issue</option>
                </select>
                <input type="text" name="items[{{ $idx }}][notes]" placeholder="Condition notes..." style="font-size: 12px;" />
            </div>
            @endforeach
        </div>

        <div class="form-group">
            <label>General Notes / Summary</label>
            <textarea name="general_notes" rows="3" placeholder="Additional observations, special agreements, key handover notes..."></textarea>
        </div>

        <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
            <a href="{{ route('admin.inspections.index') }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Save &amp; Generate Certificate</button>
        </div>
    </form>
</div>

<script>
function filterUnitsByProperty() {
    const propId = document.getElementById('selectProperty').value;
    const unitSelect = document.getElementById('selectUnit');
    const options = unitSelect.querySelectorAll('option');
    options.forEach(opt => {
        if (!opt.value) return;
        if (!propId || opt.getAttribute('data-property') === propId) {
            opt.style.display = '';
        } else {
            opt.style.display = 'none';
        }
    });
}
</script>
@endsection
