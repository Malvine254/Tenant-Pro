@extends('admin.layout')

@section('page-title', 'Settings')

@section('content')
<style>
    .settings-shell {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(280px, 0.34fr);
        gap: 18px;
    }
    .settings-card {
        background: linear-gradient(180deg, #111827, #0b1220);
        border: 1px solid rgba(148, 163, 184, 0.18);
        border-radius: 16px;
        box-shadow: 0 18px 32px rgba(2, 6, 23, 0.25);
        overflow: hidden;
    }
    .settings-card-header {
        padding: 18px;
        border-bottom: 1px solid rgba(148, 163, 184, 0.12);
    }
    .settings-card-header h3 {
        margin: 0;
        font-size: 18px;
        font-weight: 800;
        letter-spacing: -0.03em;
        color: #f8fafc;
    }
    .settings-card-header p {
        margin-top: 7px;
        color: #94a3b8;
        font-size: 13px;
    }
    .settings-form {
        padding: 18px;
    }
    .settings-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px 16px;
    }
    .field {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    .field label {
        font-size: 12px;
        color: #cbd5e1;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }
    .field input,
    .field select,
    .field textarea {
        width: 100%;
        min-height: 42px;
        padding: 10px 12px;
        border-radius: 11px;
        border: 1px solid rgba(255, 255, 255, 0.62);
        background: transparent;
        color: #f8fafc;
        font-size: 14px;
    }
    .field textarea {
        min-height: 88px;
        resize: vertical;
    }
    .field input::placeholder,
    .field textarea::placeholder {
        color: rgba(248, 250, 252, 0.68);
    }
    .field input:focus,
    .field select:focus,
    .field textarea:focus {
        outline: none;
        border-color: #fff;
        background: rgba(255, 255, 255, 0.03);
        box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.12);
    }
    .settings-tabs {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        padding: 14px 18px 0;
    }
    .settings-tab {
        border: 1px solid rgba(148, 163, 184, 0.25);
        background: rgba(15, 23, 42, 0.72);
        color: #cbd5e1;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        padding: 8px 12px;
        cursor: pointer;
    }
    .settings-tab.is-active {
        color: #f8fafc;
        border-color: rgba(96, 165, 250, 0.52);
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.35), rgba(14, 116, 144, 0.35));
        box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.12);
    }
    .settings-pane {
        display: none;
    }
    .settings-pane.is-active {
        display: block;
    }
    .divider {
        height: 1px;
        margin: 8px 18px 0;
        background: rgba(148, 163, 184, 0.12);
    }
    .check-row {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 14px;
        background: rgba(15, 23, 42, 0.7);
        border: 1px solid rgba(148, 163, 184, 0.18);
        border-radius: 12px;
        color: #e2e8f0;
    }
    .check-row input {
        width: 16px;
        height: 16px;
        flex: 0 0 16px;
        accent-color: #60a5fa;
    }
    .tenant-preferences-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
        align-items: stretch;
    }
    .tenant-preference-field,
    .tenant-preferences-grid .check-row {
        min-height: 88px;
        box-sizing: border-box;
        padding: 12px 14px;
        background: rgba(15, 23, 42, 0.7);
        border: 1px solid rgba(148, 163, 184, 0.18);
        border-radius: 12px;
    }
    .tenant-preference-field {
        justify-content: space-between;
    }
    .tenant-preference-field input {
        min-height: 38px;
        padding: 8px 10px;
    }
    .actions-row {
        margin-top: 18px;
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        flex-wrap: wrap;
    }
    .helper-panel {
        background: linear-gradient(180deg, rgba(96, 165, 250, 0.09), rgba(15, 23, 42, 0.96));
        border: 1px solid rgba(96, 165, 250, 0.18);
        border-radius: 16px;
        padding: 18px;
        color: #dbeafe;
        height: fit-content;
    }
    .helper-panel h4 {
        font-size: 16px;
        font-weight: 800;
        margin-bottom: 10px;
    }
    .helper-panel ul {
        margin: 8px 0 0 16px;
        color: #cbd5e1;
        line-height: 1.7;
        font-size: 13px;
    }
    .summary-grid {
        margin-top: 10px;
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
    }
    .summary-item {
        background: rgba(15, 23, 42, 0.72);
        border: 1px solid rgba(148, 163, 184, 0.18);
        border-radius: 12px;
        padding: 10px 12px;
    }
    .summary-item b {
        display: block;
        color: #f8fafc;
        font-size: 17px;
        line-height: 1.2;
    }
    .summary-item span {
        color: #94a3b8;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .tab-note {
        margin-top: 10px;
        color: #93c5fd;
        font-size: 13px;
    }
    @media (max-width: 980px) {
        .settings-shell {
            grid-template-columns: 1fr;
        }
    }
    @media (max-width: 760px) {
        .settings-grid {
            grid-template-columns: 1fr;
        }
        .tenant-preferences-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="settings-shell">
    <section class="settings-card">
        <div class="settings-card-header">
            <h3>Account & platform settings</h3>
            <p>Use tabs to manage your profile, security, tenant preferences, and payment operations from one place.</p>
        </div>

        <nav class="settings-tabs" aria-label="Settings tabs">
            <button type="button" class="settings-tab" data-tab-target="account">Account</button>
            <button type="button" class="settings-tab" data-tab-target="security">Security</button>
            @if($isLandlord)
                <button type="button" class="settings-tab" data-tab-target="tenants">Tenant Preferences</button>
            @endif
            <button type="button" class="settings-tab" data-tab-target="payment">Payment</button>
            @if($isAdmin)
                <button type="button" class="settings-tab" data-tab-target="platform">Platform</button>
            @endif
        </nav>
        <div class="divider"></div>

        <div class="settings-pane" data-tab-pane="account">
            <form method="POST" action="{{ route('admin.settings.account') }}" class="settings-form">
                @csrf
                @method('PUT')
                <input type="hidden" name="_settings_tab" value="account">

                <div class="settings-grid">
                    <div class="field">
                        <label for="first_name">First name</label>
                        <input id="first_name" name="first_name" value="{{ old('first_name', $user->first_name ?? '') }}" placeholder="Enter first name" required>
                    </div>
                    <div class="field">
                        <label for="last_name">Last name</label>
                        <input id="last_name" name="last_name" value="{{ old('last_name', $user->last_name ?? '') }}" placeholder="Enter last name">
                    </div>
                    <div class="field">
                        <label for="email">Email address</label>
                        <input id="email" type="email" name="email" value="{{ old('email', $user->email ?? '') }}" placeholder="name@example.com" required>
                    </div>
                    <div class="field">
                        <label for="phone_number">Phone number</label>
                        <input id="phone_number" name="phone_number" value="{{ old('phone_number', $user->phone_number ?? '') }}" placeholder="e.g. 2547XXXXXXXX" required>
                    </div>
                </div>

                <div class="actions-row">
                    <button type="submit" class="btn btn-primary">Save account changes</button>
                </div>
            </form>
        </div>

        <div class="settings-pane" data-tab-pane="security">
            <form method="POST" action="{{ route('admin.settings.password') }}" class="settings-form">
                @csrf
                @method('PUT')
                <input type="hidden" name="_settings_tab" value="security">

                <div class="settings-grid">
                    <div class="field">
                        <label for="current_password">Current password</label>
                        <input id="current_password" type="password" name="current_password" placeholder="Enter current password" required>
                    </div>
                    <div class="field">
                        <label for="password">New password</label>
                        <input id="password" type="password" name="password" minlength="8" placeholder="Enter new password" required>
                    </div>
                    <div class="field">
                        <label for="password_confirmation">Confirm new password</label>
                        <input id="password_confirmation" type="password" name="password_confirmation" minlength="8" placeholder="Re-enter new password" required>
                    </div>
                </div>

                <div class="actions-row">
                    <button type="submit" class="btn btn-primary">Update password</button>
                </div>
            </form>
        </div>

        @if($isLandlord)
            <div class="settings-pane" data-tab-pane="tenants">
                <form method="POST" action="{{ route('admin.settings.tenant-preferences') }}" class="settings-form">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="_settings_tab" value="tenants">

                    <div class="settings-grid tenant-preferences-grid">
                        <div class="field tenant-preference-field">
                            <label for="default_invite_expiry_days">Default invite expiry (days)</label>
                            <input id="default_invite_expiry_days" type="number" min="1" max="60" name="default_invite_expiry_days" value="{{ old('default_invite_expiry_days', $tenantSettings['default_invite_expiry_days'] ?? 7) }}" placeholder="e.g. 7" required>
                        </div>

                        <label class="check-row" for="auto_assign_unit_on_accept">
                            <input id="auto_assign_unit_on_accept" name="auto_assign_unit_on_accept" type="checkbox" value="1" {{ old('auto_assign_unit_on_accept', $tenantSettings['auto_assign_unit_on_accept'] ?? true) ? 'checked' : '' }}>
                            <span>Auto-assign selected unit when tenant accepts invitation</span>
                        </label>

                        <label class="check-row" for="require_tenant_email">
                            <input id="require_tenant_email" name="require_tenant_email" type="checkbox" value="1" {{ old('require_tenant_email', $tenantSettings['require_tenant_email'] ?? true) ? 'checked' : '' }}>
                            <span>Require tenant email for invitation flow</span>
                        </label>

                        <label class="check-row" for="allow_multi_unit_assignment">
                            <input id="allow_multi_unit_assignment" name="allow_multi_unit_assignment" type="checkbox" value="1" {{ old('allow_multi_unit_assignment', $tenantSettings['allow_multi_unit_assignment'] ?? true) ? 'checked' : '' }}>
                            <span>Allow assigning one tenant account to multiple units</span>
                        </label>
                    </div>

                    <p class="tab-note">These preferences shape default behavior in invitations, onboarding, and tenant assignment flows.</p>

                    <div class="actions-row">
                        <button type="submit" class="btn btn-primary">Save tenant preferences</button>
                    </div>
                </form>
            </div>
        @endif

        <div class="settings-pane" data-tab-pane="payment">
            <form method="POST" action="{{ route('admin.settings.payment') }}" class="settings-form">
                @csrf
                @method('PUT')
                <input type="hidden" name="_settings_tab" value="payment">

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
        </div>

        @if($isAdmin)
            <div class="settings-pane" data-tab-pane="platform">
                <form method="POST" action="{{ route('admin.settings.passkey') }}" class="settings-form">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="_settings_tab" value="platform">

                    <div class="settings-grid">
                        <div class="field" style="grid-column: 1 / -1;">
                            <label for="passkey">Global Daraja passkey</label>
                            <input id="passkey" name="passkey" value="{{ old('passkey', $globalPasskey ?? '') }}" placeholder="Paste the shared Daraja passkey" required>
                        </div>
                    </div>

                    <p class="tab-note">Platform settings apply globally. Restrict changes to trusted operations administrators.</p>

                    <div class="actions-row">
                        <button type="submit" class="btn btn-primary">Save global passkey</button>
                    </div>
                </form>
            </div>
        @endif
    </section>

    <aside class="helper-panel">
        <h4>Landlord workspace</h4>
        <p>Configure payment, tenant onboarding defaults, and account details without leaving the admin module.</p>

        @if($isLandlord)
            <div class="summary-grid">
                <div class="summary-item">
                    <b>{{ $tenantSummary['properties'] ?? 0 }}</b>
                    <span>Properties</span>
                </div>
                <div class="summary-item">
                    <b>{{ $tenantSummary['units'] ?? 0 }}</b>
                    <span>Units</span>
                </div>
                <div class="summary-item">
                    <b>{{ $tenantSummary['activeTenants'] ?? 0 }}</b>
                    <span>Active tenants</span>
                </div>
                <div class="summary-item">
                    <b>{{ $tenantSummary['pendingInvites'] ?? 0 }}</b>
                    <span>Pending invites</span>
                </div>
            </div>
        @endif

        <ul>
            <li>Account tab updates your personal profile details used across the dashboard.</li>
            <li>Security tab allows changing password with current-password verification.</li>
            <li>Tenant Preferences sets landlord-level defaults for onboarding behavior.</li>
            <li>Payment stores the landlord-specific Daraja channel used by tenant billing flows.</li>
            @if($isAdmin)
                <li>Platform tab is reserved for global Daraja passkey management.</li>
            @endif
        </ul>
    </aside>
</div>

<script>
    (function () {
        const tabs = Array.from(document.querySelectorAll('[data-tab-target]'));
        const panes = Array.from(document.querySelectorAll('[data-tab-pane]'));
        const paymentType = document.getElementById('payment_type');
        const paybillField = document.querySelector('[data-paybill-field]');
        const tillField = document.querySelector('[data-till-field]');

        function resolveInitialTab() {
            const validTabs = tabs.map((tab) => tab.dataset.tabTarget);
            const queryTab = new URLSearchParams(window.location.search).get('tab');
            const oldTab = "{{ old('_settings_tab', '') }}";
            if (oldTab && validTabs.includes(oldTab)) return oldTab;
            if (queryTab && validTabs.includes(queryTab)) return queryTab;
            return validTabs[0] || 'account';
        }

        function activateTab(tabName) {
            tabs.forEach((tab) => {
                tab.classList.toggle('is-active', tab.dataset.tabTarget === tabName);
            });
            panes.forEach((pane) => {
                pane.classList.toggle('is-active', pane.dataset.tabPane === tabName);
            });

            const url = new URL(window.location.href);
            url.searchParams.set('tab', tabName);
            window.history.replaceState({}, '', url);
        }

        tabs.forEach((tab) => {
            tab.addEventListener('click', function () {
                activateTab(this.dataset.tabTarget);
            });
        });

        function syncPaymentFields() {
            if (!paymentType || !paybillField || !tillField) {
                return;
            }
            const isTill = paymentType.value === 'TILL';
            paybillField.style.display = isTill ? 'none' : 'flex';
            tillField.style.display = isTill ? 'flex' : 'none';
        }

        if (paymentType) {
            paymentType.addEventListener('change', syncPaymentFields);
        }

        activateTab(resolveInitialTab());
        syncPaymentFields();
    })();
</script>
@endsection
