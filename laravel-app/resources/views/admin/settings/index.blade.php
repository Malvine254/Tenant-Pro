@extends('admin.layout')

@section('page-title', 'Payment Settings')

@section('content')
<style>
    .settings-shell {
        display:grid;
        grid-template-columns: minmax(0, 1.15fr) minmax(280px, .85fr);
        gap:18px;
    }
    .settings-card {
        background: linear-gradient(180deg,#111827,#0b1220);
        border: 1px solid rgba(148,163,184,.18);
        border-radius: 16px;
        box-shadow: 0 18px 32px rgba(2,6,23,.25);
        overflow: hidden;
    }
    .settings-card-header {
        padding: 18px 18px 12px;
        border-bottom: 1px solid rgba(148,163,184,.12);
    }
    .settings-card-header h3 {
        font-size: 18px;
        font-weight: 800;
        letter-spacing: -.03em;
        color:#f8fafc;
    }
    .settings-card-header p {
        margin-top:6px;
        color:#94a3b8;
        font-size: 13px;
    }
    .settings-form { padding: 18px; }
    .settings-grid {
        display:grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 14px 16px;
    }
    .field { display:flex; flex-direction:column; gap:6px; }
    .field label {
        font-size:12px;
        color:#cbd5e1;
        font-weight:700;
        letter-spacing:.04em;
        text-transform:uppercase;
    }
    .field input, .field select, .field textarea {
        width:100%;
        min-height: 42px;
        padding: 10px 12px;
        border-radius: 11px;
        border: 1px solid rgba(148,163,184,.22);
        background: rgba(15,23,42,.8);
        color:#f8fafc;
        font-size: 14px;
    }
    .field textarea { min-height: 88px; resize: vertical; }
    .field input:focus, .field select:focus, .field textarea:focus {
        outline:none;
        border-color: rgba(96,165,250,.5);
        box-shadow: 0 0 0 3px rgba(96,165,250,.12);
    }
    .check-row {
        display:flex;
        align-items:center;
        gap:10px;
        padding: 12px 14px;
        background: rgba(15,23,42,.7);
        border:1px solid rgba(148,163,184,.18);
        border-radius: 12px;
        color:#e2e8f0;
    }
    .check-row input {
        width: 16px; height: 16px;
        accent-color: #60a5fa;
    }
    .helper-panel {
        background: linear-gradient(180deg, rgba(96,165,250,.09), rgba(15,23,42,.96));
        border:1px solid rgba(96,165,250,.18);
        border-radius:16px;
        padding:18px;
        color:#dbeafe;
    }
    .helper-panel h4 {
        font-size: 16px;
        font-weight:800;
        margin-bottom:8px;
    }
    .helper-panel ul {
        margin: 10px 0 0 16px;
        color:#cbd5e1;
        line-height:1.8;
        font-size:13px;
    }
    .status-pill {
        display:inline-flex;
        align-items:center;
        gap:8px;
        padding:6px 10px;
        border-radius:999px;
        background: rgba(52,211,153,.14);
        border:1px solid rgba(52,211,153,.25);
        color:#bbf7d0;
        font-weight:700;
        font-size:11px;
        text-transform:uppercase;
        letter-spacing:.05em;
    }
    @media (max-width: 920px) {
        .settings-shell { grid-template-columns: 1fr; }
    }
</style>

<div class="settings-shell">
    <section class="settings-card">
        <div class="settings-card-header">
            <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
                <h3>Daraja payment configuration</h3>
                <span class="status-pill">Saved for landlord</span>
            </div>
            <p>Choose the M-Pesa channel your tenants should use for rent and invoice payments. The selected configuration is applied to this landlord’s tenants automatically.</p>
        </div>

        <form method="POST" action="{{ route('admin.settings.payment') }}" class="settings-form">
            @csrf
            @method('PUT')

            <div class="settings-grid">
                <div class="field">
                    <label for="payment_type">Payment channel</label>
                    <select id="payment_type" name="payment_type" required>
                        <option value="PAYBILL" {{ (($paymentSettings['payment_type'] ?? 'PAYBILL') === 'PAYBILL') ? 'selected' : '' }}>PayBill</option>
                        <option value="TILL" {{ (($paymentSettings['payment_type'] ?? 'PAYBILL') === 'TILL') ? 'selected' : '' }}>Till</option>
                    </select>
                </div>

                <div class="field" data-paybill-field>
                    <label for="paybill_number">PayBill number</label>
                    <input id="paybill_number" name="paybill_number" value="{{ old('paybill_number', $paymentSettings['paybill_number'] ?? '') }}" placeholder="e.g. 123456">
                </div>

                <div class="field" data-till-field style="display:none;">
                    <label for="till_number">Till number</label>
                    <input id="till_number" name="till_number" value="{{ old('till_number', $paymentSettings['till_number'] ?? '') }}" placeholder="e.g. 456789">
                </div>

                <div class="field">
                    <label for="account_reference">Account reference</label>
                    <input id="account_reference" name="account_reference" value="{{ old('account_reference', $paymentSettings['account_reference'] ?? 'Tenant Pro') }}" placeholder="Primary rent account">
                </div>

                <div class="field">
                    <label for="business_name">Business name</label>
                    <input id="business_name" name="business_name" value="{{ old('business_name', $paymentSettings['business_name'] ?? '') }}" placeholder="Starmax Ltd">
                </div>

                <div class="field">
                    <label for="passkey">Daraja passkey</label>
                    <input id="passkey" name="passkey" value="{{ old('passkey', $paymentSettings['passkey'] ?? '') }}" placeholder="Paste your Daraja passkey">
                </div>

                <div class="field" style="grid-column: 1 / -1;">
                    <label for="short_code_note">Notes</label>
                    <textarea id="short_code_note" name="short_code_note" placeholder="Optional instructions for tenants or staff">{{ old('short_code_note', $paymentSettings['short_code_note'] ?? '') }}</textarea>
                </div>
            </div>

            <div style="margin-top:18px; display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
                <label class="check-row" for="use_default_config">
                    <input id="use_default_config" name="use_default_config" type="checkbox" value="1" {{ old('use_default_config', $paymentSettings['use_default_config'] ?? false) ? 'checked' : '' }}>
                    <span>Use this configuration for all tenant payments from this landlord</span>
                </label>

                <button type="submit" class="btn btn-primary">Save payment settings</button>
            </div>
        </form>
    </section>

    <aside class="helper-panel">
        <h4>How it works</h4>
        <p>Each landlord can now keep a separate Daraja payment account. When a tenant pays an invoice, the app automatically uses the landlord’s saved PayBill or Till details for the payment request.</p>
        <ul>
            <li>Choose PayBill for rent collection numbers or long-code accounts.</li>
            <li>Choose Till when your property uses a Till number for payment collection.</li>
            <li>Keep the account reference short and consistent for invoice records.</li>
            <li>Use a passkey that matches the Daraja app linked to the selected shortcode.</li>
        </ul>
    </aside>
</div>

<script>
    (function () {
        const paymentType = document.getElementById('payment_type');
        const paybillField = document.querySelector('[data-paybill-field]');
        const tillField = document.querySelector('[data-till-field]');

        function syncFields() {
            const isTill = paymentType.value === 'TILL';
            paybillField.style.display = isTill ? 'none' : 'flex';
            tillField.style.display = isTill ? 'flex' : 'none';
        }

        paymentType.addEventListener('change', syncFields);
        syncFields();
    })();
</script>
@endsection
