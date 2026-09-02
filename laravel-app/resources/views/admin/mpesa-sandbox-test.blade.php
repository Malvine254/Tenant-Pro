@extends('admin.layout')

@section('page-title', 'Sandbox Payment Test')

@section('content')
<style>
    .sandbox-wrap {
        display: grid;
        grid-template-columns: minmax(0, 1.1fr) minmax(260px, .9fr);
        gap: 18px;
    }
    .sandbox-card {
        background: linear-gradient(180deg, #111827, #0b1220);
        border: 1px solid rgba(148,163,184,.18);
        border-radius: 18px;
        box-shadow: 0 18px 32px rgba(2,6,23,.25);
        overflow: hidden;
    }
    .sandbox-header {
        padding: 18px 20px 14px;
        border-bottom: 1px solid rgba(148,163,184,.12);
    }
    .sandbox-header h3 {
        font-size: 18px;
        font-weight: 800;
        letter-spacing: -.03em;
        color: #f8fafc;
    }
    .sandbox-header p {
        margin-top: 6px;
        color: #94a3b8;
        font-size: 13px;
        line-height: 1.6;
    }
    .sandbox-body { padding: 18px 20px 20px; }
    .sandbox-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 14px 16px;
    }
    .sandbox-payment-row {
        grid-column: 1 / -1;
        display: grid;
        grid-template-columns: minmax(180px, .65fr) minmax(280px, 1.35fr);
        gap: 14px 16px;
    }
    .field { display: flex; flex-direction: column; gap: 6px; }
    .field label {
        font-size: 12px;
        color: #cbd5e1;
        font-weight: 700;
        letter-spacing: .04em;
        text-transform: uppercase;
    }
    .field input, .field select {
        width: 100%;
        min-height: 42px;
        padding: 10px 12px;
        border-radius: 11px;
        border: 1px solid rgba(255,255,255,.62);
        background: transparent;
        color: #f8fafc;
        font-size: 14px;
    }
    .field input::placeholder { color: rgba(248,250,252,.68); }
    .field input:focus, .field select:focus {
        outline:none;
        border-color: #fff;
        background: rgba(255,255,255,.03);
        box-shadow: 0 0 0 3px rgba(255,255,255,.12);
    }
    .helper-box {
        background: linear-gradient(180deg, rgba(96,165,250,.1), rgba(15,23,42,.92));
        border: 1px solid rgba(96,165,250,.18);
        border-radius: 16px;
        padding: 18px;
        color: #dbeafe;
    }
    .helper-box h4 {
        font-size: 16px;
        font-weight: 800;
        margin-bottom: 10px;
    }
    .helper-box ul {
        margin: 10px 0 0 16px;
        line-height: 1.8;
        color: #cbd5e1;
        font-size: 13px;
    }
    .status-badge {
        display: inline-flex;
        align-items:center;
        gap:8px;
        padding: 6px 10px;
        border-radius: 999px;
        background: rgba(52,211,153,.12);
        border:1px solid rgba(52,211,153,.24);
        color:#bbf7d0;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: .05em;
        text-transform: uppercase;
    }
    @media (max-width: 900px) {
        .sandbox-wrap { grid-template-columns: 1fr; }
    }
    @media (max-width: 620px) {
        .sandbox-payment-row { grid-template-columns: 1fr; }
    }
</style>

<div class="sandbox-wrap">
    <section class="sandbox-card">
        <div class="sandbox-header">
            <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
                <h3>Daraja sandbox payment test</h3>
                <span class="status-badge">{{ strtoupper($environment) }} mode</span>
            </div>
            <p>Use this form to test the M-Pesa flow in sandbox without creating an invoice. Enter the customer phone number and the PayBill or Till number you want to simulate.</p>
        </div>

        <div class="sandbox-body">
            @if($errors->any())
                <div class="alert-error" style="margin-bottom:16px;">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('admin.mpesa.sandbox-test.store') }}">
                @csrf
                <div class="sandbox-grid">
                    <div class="field">
                        <label for="payment_type">Payment type</label>
                        <select id="payment_type" name="payment_type">
                            <option value="PAYBILL" {{ old('payment_type', 'PAYBILL') === 'PAYBILL' ? 'selected' : '' }}>PayBill</option>
                            <option value="TILL" {{ old('payment_type') === 'TILL' ? 'selected' : '' }}>Till</option>
                        </select>
                    </div>

                    <div class="field">
                        <label for="short_code">PayBill / Till number</label>
                        <input id="short_code" name="short_code" value="{{ old('short_code', '174379') }}" placeholder="e.g. 174379" required>
                    </div>

                    <div class="field">
                        <label for="phone_number">Phone number</label>
                        <input id="phone_number" name="phone_number" value="{{ old('phone_number', '254712345678') }}" placeholder="e.g. 254712345678" required>
                    </div>

                    <div class="sandbox-payment-row">
                        <div class="field">
                            <label for="amount">Amount</label>
                            <input id="amount" name="amount" type="number" min="1" step="0.01" value="{{ old('amount', 500) }}" required>
                        </div>

                        <div class="field">
                            <label for="account_reference">Account reference</label>
                            <input id="account_reference" name="account_reference" value="{{ old('account_reference', 'Starmax Tenant Services Sandbox') }}" placeholder="Starmax Tenant Services Sandbox">
                        </div>
                    </div>
                </div>

                <div style="margin-top: 18px; display: flex; justify-content: flex-end;">
                    <button type="submit" class="btn btn-primary">Simulate Pay</button>
                </div>
            </form>
        </div>
    </section>

    <aside class="helper-box">
        <h4>Sandbox notes</h4>
        <p>Use this page when the Daraja sandbox app is active but passkey/shortcode are not available in production lookup, or when you simply need a safe payment test.</p>
        <ul>
            <li>Environment: {{ strtoupper($environment) }}</li>
            <li>Simulation mode: {{ $simulate ? 'Enabled' : 'Disabled' }}</li>
            <li>Phone number must be a valid Kenyan M-Pesa number.</li>
            <li>Use either a PayBill or Till number depending on your test setup.</li>
        </ul>
    </aside>
</div>
@endsection
