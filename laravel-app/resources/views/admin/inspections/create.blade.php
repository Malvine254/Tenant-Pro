@extends('admin.layout')

@section('content')
<style>
    .wizard-actions { display:flex; align-items:center; justify-content:space-between; gap:10px; margin-top:22px; padding-top:18px; border-top:1px solid rgba(148,163,184,.14); }
    .checklist-table { display:flex; flex-direction:column; gap:10px; margin-bottom:18px; }
    .checklist-head { display:grid; grid-template-columns:1.2fr 1.6fr 1.1fr 1.6fr; gap:10px; padding:0 4px; font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.04em; color:var(--muted); }
    .checklist-row { display:grid; grid-template-columns:1.2fr 1.6fr 1.1fr 1.6fr; gap:10px; align-items:center; background:rgba(15,23,42,.55); border:1px solid rgba(148,163,184,.16); border-radius:12px; padding:10px; }
    .checklist-row input, .checklist-row select { width:100%; min-height:42px; padding:9px 11px; border:1px solid rgba(148,163,184,.42); border-radius:10px; font-size:13px; background:rgba(2,6,23,.22); color:var(--text); outline:none; transition:border-color .14s, box-shadow .14s, background .14s; }
    .checklist-row input:focus, .checklist-row select:focus { border-color:#fff; background:rgba(255,255,255,.03); box-shadow:0 0 0 3px rgba(255,255,255,.12); }
    .checklist-row input::placeholder { color:rgba(248,250,252,.6); }
    @media (max-width: 720px) {
        .checklist-head { display:none; }
        .checklist-row { grid-template-columns:1fr 1fr; }
    }
</style>

<div class="admin-page-header">
    <div>
        <h2>Conduct Unit Inspection</h2>
        <p>Record room conditions, key handovers, and meter readings at move-in or move-out.</p>
    </div>
    <div class="admin-actions">
        <a href="{{ route('admin.inspections.index') }}" class="btn btn-secondary">Back to Inspections</a>
    </div>
</div>

<div class="card" style="max-width: 880px;">
    <div class="ui-tabs" id="inspectionTabs" role="tablist" aria-label="Inspection steps" data-ui-tabs>
        <button type="button" class="ui-tab" role="tab" data-ui-tab="basic" data-tab-panel="insp-panel-basic">1. Basic Info</button>
        <button type="button" class="ui-tab" role="tab" data-ui-tab="meters" data-tab-panel="insp-panel-meters">2. Meter Readings</button>
        <button type="button" class="ui-tab" role="tab" data-ui-tab="checklist" data-tab-panel="insp-panel-checklist">3. Room Checklist</button>
        <button type="button" class="ui-tab" role="tab" data-ui-tab="notes" data-tab-panel="insp-panel-notes">4. Notes &amp; Submit</button>
    </div>

    <form method="POST" action="{{ route('admin.inspections.store') }}" id="inspectionForm">
        @csrf

        <div id="insp-panel-basic" class="ui-tab-panel" role="tabpanel">
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
            <div class="wizard-actions">
                <span></span>
                <button type="button" class="btn btn-primary" data-goto="meters" data-validate="1">Next: Meter Readings &rarr;</button>
            </div>
        </div>

        <div id="insp-panel-meters" class="ui-tab-panel" role="tabpanel">
            <div class="section-heading">Initial Utility Meter Readings</div>
            <div class="form-grid">
                <div class="form-group">
                    <label>&#128167; Water Sub-Meter Reading (Units)</label>
                    <input type="number" step="0.01" name="meter_reading_water" placeholder="e.g. 142.50" />
                </div>
                <div class="form-group">
                    <label>&#9889; Power Meter / Token Balance (Units)</label>
                    <input type="number" step="0.01" name="meter_reading_electricity" placeholder="e.g. 45.00" />
                </div>
            </div>
            <div class="wizard-actions">
                <button type="button" class="btn btn-secondary" data-goto="basic">&larr; Back</button>
                <button type="button" class="btn btn-primary" data-goto="checklist">Next: Room Checklist &rarr;</button>
            </div>
        </div>

        <div id="insp-panel-checklist" class="ui-tab-panel" role="tabpanel">
            <div class="section-heading">Room &amp; Fixtures Condition Checklist</div>
            <div class="checklist-table">
                <div class="checklist-head">
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
                <div class="checklist-row">
                    <input type="text" name="items[{{ $idx }}][category]" value="{{ $def['cat'] }}" required />
                    <input type="text" name="items[{{ $idx }}][item_name]" value="{{ $def['item'] }}" required />
                    <select name="items[{{ $idx }}][condition]">
                        <option value="GOOD">Good / Intact</option>
                        <option value="FAIR">Fair / Normal Wear</option>
                        <option value="DAMAGED">Damaged / Issue</option>
                    </select>
                    <input type="text" name="items[{{ $idx }}][notes]" placeholder="Condition notes..." />
                </div>
                @endforeach
            </div>
            <div class="wizard-actions">
                <button type="button" class="btn btn-secondary" data-goto="meters">&larr; Back</button>
                <button type="button" class="btn btn-primary" data-goto="notes">Next: Notes &amp; Review &rarr;</button>
            </div>
        </div>

        <div id="insp-panel-notes" class="ui-tab-panel" role="tabpanel">
            <div class="section-heading">General Notes &amp; Summary</div>
            <div class="form-group field-wide">
                <label>General Notes / Summary</label>
                <textarea name="general_notes" rows="4" placeholder="Additional observations, special agreements, key handover notes..."></textarea>
            </div>
            <div class="wizard-actions">
                <button type="button" class="btn btn-secondary" data-goto="checklist">&larr; Back</button>
                <div style="display:flex; gap:10px;">
                    <a href="{{ route('admin.inspections.index') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save &amp; Generate Certificate</button>
                </div>
            </div>
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

(function () {
    const tabsWrap = document.getElementById('inspectionTabs');
    if (!tabsWrap) return;
    const tabs = Array.from(tabsWrap.querySelectorAll('[data-ui-tab]'));

    function currentIndex() {
        const active = tabs.find(tab => tab.classList.contains('active'));
        return active ? tabs.indexOf(active) : 0;
    }

    // Block jumping ahead to a later step via the tab pills unless the current step is valid.
    tabs.forEach((tab, idx) => {
        tab.addEventListener('click', event => {
            if (idx <= currentIndex()) return;
            const activePanel = document.getElementById(tabs[currentIndex()].dataset.tabPanel);
            const invalid = activePanel ? activePanel.querySelector(':invalid') : null;
            if (invalid) {
                event.preventDefault();
                event.stopImmediatePropagation();
                invalid.reportValidity();
            }
        });
    });

    document.querySelectorAll('#inspectionForm [data-goto]').forEach(btn => {
        btn.addEventListener('click', () => {
            if (btn.dataset.validate) {
                const panel = btn.closest('.ui-tab-panel');
                const invalid = panel ? panel.querySelector(':invalid') : null;
                if (invalid) {
                    invalid.reportValidity();
                    return;
                }
            }
            const target = tabs.find(tab => tab.dataset.uiTab === btn.dataset.goto);
            if (target) target.click();
        });
    });
})();
</script>
@endsection
