@extends('admin.layout')

@section('content')
<div class="admin-page-header">
    <div>
        <h2>Generate Monthly Invoice</h2>
        <p>Create tenant rental invoices with automatic Sub-Meter water &amp; electricity usage calculations.</p>
    </div>
    <div class="admin-actions">
        <a href="{{ route('admin.invoices.index') }}" class="btn btn-secondary">Back to Invoices</a>
    </div>
</div>

<div class="card" style="max-width: 780px;">
    <form method="POST" action="{{ route('admin.invoices.store') }}" id="formNewInvoice">
        @csrf

        <div class="section-heading">Tenant &amp; Period Details</div>
        <div class="form-grid">
            <div class="form-group">
                <label>Property <span style="color:#f87171;">*</span></label>
                <select id="selectInvoiceProperty" required onchange="filterUnits()">
                    <option value="">Select property</option>
                    @foreach($properties as $prop)
                        <option value="{{ $prop->id }}">{{ $prop->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Unit / Room <span style="color:#f87171;">*</span></label>
                <select name="unit_id" id="selectInvoiceUnit" required onchange="onUnitSelected()">
                    <option value="">Select unit</option>
                    @foreach($properties as $prop)
                        @foreach($prop->units as $unit)
                            <option value="{{ $unit->id }}" 
                                    data-property="{{ $prop->id }}" 
                                    data-rent="{{ $unit->rent_amount ?? 0 }}"
                                    data-tenant-name="{{ $unit->tenant?->user?->name ?? 'Vacant' }}">
                                {{ $prop->name }} - Unit {{ $unit->unit_number }} ({{ $unit->tenant?->user?->name ?? 'Vacant' }})
                            </option>
                        @endforeach
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Billing Type <span style="color:#f87171;">*</span></label>
                <select name="billing_type" required>
                    <option value="RENT">Monthly Rent &amp; Utilities</option>
                    <option value="WATER">Water Only</option>
                    <option value="ELECTRICITY">Electricity Only</option>
                    <option value="GARBAGE">Garbage Only</option>
                    <option value="SERVICE_CHARGE">Service Charge</option>
                    <option value="OTHER">Other Fee</option>
                </select>
            </div>
            <div class="form-group">
                <label>Billing Month &amp; Year <span style="color:#f87171;">*</span></label>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                    <select name="period_month" required>
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ $m == now()->month ? 'selected' : '' }}>{{ date('F', mktime(0, 0, 0, $m, 1)) }}</option>
                        @endfor
                    </select>
                    <select name="period_year" required>
                        @for($y = now()->year; $y <= now()->year + 2; $y++)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endfor
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Issue Date <span style="color:#f87171;">*</span></label>
                <input type="date" name="issue_date" value="{{ date('Y-m-d') }}" required />
            </div>
            <div class="form-group">
                <label>Due Date <span style="color:#f87171;">*</span></label>
                <input type="date" name="due_date" value="{{ date('Y-m-05', strtotime('+1 month')) }}" required />
            </div>
        </div>

        <!-- Rent & Sub-Meter Calculator -->
        <div class="section-heading" style="margin-top: 18px;">Charge Breakdown &amp; Sub-Meter Readings</div>
        
        <div class="form-group">
            <label>Base Rent Amount (KSh) <span style="color:#f87171;">*</span></label>
            <input type="number" step="0.01" name="amount" id="inputBaseRent" placeholder="e.g. 15000.00" required oninput="calculateTotal()" />
        </div>

        <!-- Water Sub-Meter Box -->
        <div style="background: rgba(15,23,42,0.6); border: 1px solid rgba(148,163,184,0.18); border-radius: 12px; padding: 14px; margin-bottom: 14px;">
            <div style="font-weight: 800; font-size: 13px; color: #93c5fd; margin-bottom: 8px;">💧 Water Sub-Meter Calculation</div>
            <div class="form-grid" style="grid-template-columns: 1fr 1fr 1fr 1fr;">
                <div class="form-group">
                    <label style="font-size: 11px;">Prev Reading</label>
                    <input type="number" step="0.01" name="water_previous_reading" id="waterPrev" placeholder="e.g. 120" oninput="calculateTotal()" />
                </div>
                <div class="form-group">
                    <label style="font-size: 11px;">Curr Reading</label>
                    <input type="number" step="0.01" name="water_current_reading" id="waterCurr" placeholder="e.g. 135" oninput="calculateTotal()" />
                </div>
                <div class="form-group">
                    <label style="font-size: 11px;">Rate / Unit (KSh)</label>
                    <input type="number" step="0.01" name="water_rate_per_unit" id="waterRate" value="150" placeholder="150" oninput="calculateTotal()" />
                </div>
                <div class="form-group">
                    <label style="font-size: 11px;">Water Total</label>
                    <input type="text" id="waterSubtotal" readonly value="KSh 0.00" style="background: rgba(0,0,0,0.3); font-weight: bold; color: #93c5fd;" />
                </div>
            </div>
        </div>

        <!-- Electricity Sub-Meter Box -->
        <div style="background: rgba(15,23,42,0.6); border: 1px solid rgba(148,163,184,0.18); border-radius: 12px; padding: 14px; margin-bottom: 14px;">
            <div style="font-weight: 800; font-size: 13px; color: #fde68a; margin-bottom: 8px;">⚡ Electricity Sub-Meter Calculation (Optional)</div>
            <div class="form-grid" style="grid-template-columns: 1fr 1fr 1fr 1fr;">
                <div class="form-group">
                    <label style="font-size: 11px;">Prev Reading</label>
                    <input type="number" step="0.01" name="electricity_previous_reading" id="powerPrev" placeholder="e.g. 350" oninput="calculateTotal()" />
                </div>
                <div class="form-group">
                    <label style="font-size: 11px;">Curr Reading</label>
                    <input type="number" step="0.01" name="electricity_current_reading" id="powerCurr" placeholder="e.g. 385" oninput="calculateTotal()" />
                </div>
                <div class="form-group">
                    <label style="font-size: 11px;">Rate / Unit (KSh)</label>
                    <input type="number" step="0.01" name="electricity_rate_per_unit" id="powerRate" value="25" placeholder="25" oninput="calculateTotal()" />
                </div>
                <div class="form-group">
                    <label style="font-size: 11px;">Power Total</label>
                    <input type="text" id="powerSubtotal" readonly value="KSh 0.00" style="background: rgba(0,0,0,0.3); font-weight: bold; color: #fde68a;" />
                </div>
            </div>
        </div>

        <div class="form-group">
            <label>Late Penalty (Optional)</label>
            <input type="number" step="0.01" name="penalty_amount" id="inputPenalty" placeholder="0.00" value="0.00" oninput="calculateTotal()" />
        </div>

        <!-- Total Summary Box -->
        <div style="background: linear-gradient(135deg, rgba(37,99,235,0.15), rgba(15,23,42,0.8)); border: 1px solid rgba(59,130,246,0.3); border-radius: 12px; padding: 16px; margin-top: 18px; margin-bottom: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="font-size: 14px; font-weight: bold; color: #e2e8f0;">TOTAL INVOICE AMOUNT DUE:</span>
                <span id="labelTotalAmount" style="font-size: 20px; font-weight: 900; color: #60a5fa;">KSh 0.00</span>
            </div>
        </div>

        <div style="display: flex; gap: 10px; justify-content: flex-end;">
            <a href="{{ route('admin.invoices.index') }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Generate &amp; Dispatch Invoice</button>
        </div>
    </form>
</div>

<script>
function filterUnits() {
    const propId = document.getElementById('selectInvoiceProperty').value;
    const unitSelect = document.getElementById('selectInvoiceUnit');
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

function onUnitSelected() {
    const select = document.getElementById('selectInvoiceUnit');
    const selected = select.options[select.selectedIndex];
    if (selected && selected.getAttribute('data-rent')) {
        const rent = parseFloat(selected.getAttribute('data-rent')) || 0;
        document.getElementById('inputBaseRent').value = rent.toFixed(2);
        calculateTotal();
    }
}

function calculateTotal() {
    const baseRent = parseFloat(document.getElementById('inputBaseRent').value) || 0;
    
    // Water
    const wPrev = parseFloat(document.getElementById('waterPrev').value);
    const wCurr = parseFloat(document.getElementById('waterCurr').value);
    const wRate = parseFloat(document.getElementById('waterRate').value) || 0;
    let waterCost = 0;
    if (!isNaN(wPrev) && !isNaN(wCurr) && wCurr >= wPrev) {
        waterCost = (wCurr - wPrev) * wRate;
    }
    document.getElementById('waterSubtotal').value = 'KSh ' + waterCost.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});

    // Electricity
    const pPrev = parseFloat(document.getElementById('powerPrev').value);
    const pCurr = parseFloat(document.getElementById('powerCurr').value);
    const pRate = parseFloat(document.getElementById('powerRate').value) || 0;
    let powerCost = 0;
    if (!isNaN(pPrev) && !isNaN(pCurr) && pCurr >= pPrev) {
        powerCost = (pCurr - pPrev) * pRate;
    }
    document.getElementById('powerSubtotal').value = 'KSh ' + powerCost.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});

    const penalty = parseFloat(document.getElementById('inputPenalty').value) || 0;
    const total = baseRent + waterCost + powerCost + penalty;

    document.getElementById('labelTotalAmount').textContent = 'KSh ' + total.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
}
</script>
@endsection
