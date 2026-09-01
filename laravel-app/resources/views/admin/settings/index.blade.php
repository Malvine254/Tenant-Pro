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
    .daraja-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
        align-items: end;
    }
    .daraja-simulation {
        grid-column: span 2;
        min-height: 42px;
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
    .settings-status-row { display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;margin-bottom:16px; }
    .settings-status { padding:12px;border:1px solid rgba(148,163,184,.18);border-radius:12px;background:rgba(15,23,42,.65); }
    .settings-status span { display:block;color:#94a3b8;font-size:11px;text-transform:uppercase;letter-spacing:.05em;margin-bottom:5px; }
    .settings-status strong { color:#f8fafc;font-size:13px;overflow-wrap:anywhere; }
    .danger-zone { border-color:rgba(248,113,113,.28);background:linear-gradient(180deg,rgba(127,29,29,.15),rgba(15,23,42,.72)); }
    @media (max-width: 980px) {
        .settings-shell {
            grid-template-columns: 1fr;
        }
        .daraja-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .daraja-simulation { grid-column: auto; }
    }
    @media (max-width: 760px) {
        .settings-grid {
            grid-template-columns: 1fr;
        }
        .daraja-grid { grid-template-columns:1fr; }
        .tenant-preferences-grid {
            grid-template-columns: 1fr;
        }
        .settings-status-row { grid-template-columns:1fr; }
    }
</style>

<div class="settings-shell">
    <section class="settings-card">
        <div class="settings-card-header">
            <h3>Account & platform settings</h3>
            <p>{{ $isSuperAdmin ? 'Manage your account, production integrations, and platform availability from one secured workspace.' : 'Manage your profile, security, tenant preferences, and payment operations from one place.' }}</p>
        </div>

        <nav class="settings-tabs ui-tabs" role="tablist" aria-label="Settings tabs" data-ui-tabs data-tab-param="tab" data-initial-tab="{{ old('_settings_tab', request('tab', 'account')) }}">
            <button id="settings-tab-account" type="button" role="tab" aria-controls="settings-pane-account" class="settings-tab ui-tab" data-ui-tab="account" data-tab-panel="settings-pane-account">Account</button>
            <button id="settings-tab-security" type="button" role="tab" aria-controls="settings-pane-security" class="settings-tab ui-tab" data-ui-tab="security" data-tab-panel="settings-pane-security">Security</button>
            @if($isLandlordOwner)
                <button id="settings-tab-tenants" type="button" role="tab" aria-controls="settings-pane-tenants" class="settings-tab ui-tab" data-ui-tab="tenants" data-tab-panel="settings-pane-tenants">Tenant Preferences</button>
                <button id="settings-tab-payment" type="button" role="tab" aria-controls="settings-pane-payment" class="settings-tab ui-tab" data-ui-tab="payment" data-tab-panel="settings-pane-payment">Payment</button>
            @endif
            @if($isSuperAdmin)
                <button id="settings-tab-daraja" type="button" role="tab" aria-controls="settings-pane-daraja" class="settings-tab ui-tab" data-ui-tab="daraja" data-tab-panel="settings-pane-daraja">Daraja API</button>
                <button id="settings-tab-maintenance" type="button" role="tab" aria-controls="settings-pane-maintenance" class="settings-tab ui-tab" data-ui-tab="maintenance" data-tab-panel="settings-pane-maintenance">Maintenance</button>
            @endif
        </nav>
        <div class="divider"></div>

        <div id="settings-pane-account" class="settings-pane ui-tab-panel" role="tabpanel" aria-labelledby="settings-tab-account">
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

        <div id="settings-pane-security" class="settings-pane ui-tab-panel" role="tabpanel" aria-labelledby="settings-tab-security">
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

        @if($isLandlordOwner)
            <div id="settings-pane-tenants" class="settings-pane ui-tab-panel" role="tabpanel" aria-labelledby="settings-tab-tenants">
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

        @if($isLandlordOwner)
        <div id="settings-pane-payment" class="settings-pane ui-tab-panel" role="tabpanel" aria-labelledby="settings-tab-payment">
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
                        <input id="account_reference" name="account_reference" value="{{ old('account_reference', $paymentSettings['account_reference'] ?? '') }}" placeholder="Required for Paybill, e.g. UNIT-A12">
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
        @endif

        @if($isSuperAdmin)
            <div id="settings-pane-daraja" class="settings-pane ui-tab-panel" role="tabpanel" aria-labelledby="settings-tab-daraja">
                <form method="POST" action="{{ route('admin.settings.daraja') }}" class="settings-form">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="_settings_tab" value="daraja">

                    <div class="settings-status-row">
                        <div class="settings-status"><span>Environment</span><strong>{{ ucfirst($darajaSettings['environment']) }}</strong></div>
                        <div class="settings-status"><span>Credential status</span><strong style="color:{{ $darajaSettings['ready'] ? '#86efac' : '#fca5a5' }};">{{ $darajaSettings['ready'] ? 'Ready' : 'Incomplete' }}</strong></div>
                        <div class="settings-status"><span>Consumer key</span><strong>{{ $darajaSettings['consumer_key_masked'] ?: 'Not configured' }}</strong></div>
                    </div>

                    <div class="settings-grid daraja-grid">
                        <div class="field">
                            <label for="daraja_environment">Environment</label>
                            <select id="daraja_environment" name="environment" required>
                                <option value="sandbox" @selected(old('environment', $darajaSettings['environment']) === 'sandbox')>Sandbox</option>
                                <option value="production" @selected(old('environment', $darajaSettings['environment']) === 'production')>Production</option>
                            </select>
                        </div>
                        <div class="field">
                            <label for="daraja_shortcode">Default shortcode</label>
                            <input id="daraja_shortcode" inputmode="numeric" name="shortcode" value="{{ old('shortcode', $darajaSettings['shortcode']) }}" placeholder="Paybill or Till shortcode" required>
                        </div>
                        <div class="field">
                            <label for="daraja_callback_url">Callback URL</label>
                            <input id="daraja_callback_url" type="url" name="callback_url" value="{{ old('callback_url', $darajaSettings['callback_url']) }}" placeholder="https://app.starmaxltd.com/api/payments/mpesa/callback" required>
                        </div>
                        <div class="field">
                            <label for="daraja_consumer_key">Consumer key</label>
                            <input id="daraja_consumer_key" type="password" name="consumer_key" autocomplete="new-password" placeholder="{{ $darajaSettings['consumer_key_masked'] ?: 'Enter consumer key' }}">
                        </div>
                        <div class="field">
                            <label for="daraja_consumer_secret">Consumer secret</label>
                            <input id="daraja_consumer_secret" type="password" name="consumer_secret" autocomplete="new-password" placeholder="{{ $darajaSettings['consumer_secret_configured'] ? 'Configured — leave blank to keep' : 'Enter consumer secret' }}">
                        </div>
                        <div class="field">
                            <label for="daraja_passkey">Lipa na M-PESA passkey</label>
                            <input id="daraja_passkey" type="password" name="passkey" autocomplete="new-password" placeholder="{{ $darajaSettings['passkey_configured'] ? 'Configured — leave blank to keep' : 'Enter passkey' }}">
                        </div>
                        <div class="field">
                            <label for="daraja_current_password">Confirm your password</label>
                            <input id="daraja_current_password" type="password" name="current_password" autocomplete="current-password" required>
                        </div>
                        <label class="check-row daraja-simulation" for="daraja_simulate">
                            <input id="daraja_simulate" name="simulate" type="checkbox" value="1" @checked(old('simulate', $darajaSettings['simulate']))>
                            <span>Simulate payments locally in Sandbox. Production always disables simulation.</span>
                        </label>
                    </div>
                    <p class="tab-note">Credentials are encrypted in the database and never displayed again. Leave a secret field blank to retain its current value.</p>

                    <div class="actions-row">
                        <button type="submit" class="btn btn-primary">Save Daraja configuration</button>
                    </div>
                </form>

                <div class="divider"></div>
                <form method="POST" action="{{ route('admin.settings.daraja.test') }}" class="settings-form">
                    @csrf
                    <div class="field" style="max-width:420px;">
                        <label for="daraja_test_password">Confirm password to test connection</label>
                        <input id="daraja_test_password" type="password" name="current_password" autocomplete="current-password" required>
                    </div>
                    <div class="actions-row"><button type="submit" class="btn btn-secondary" data-loading-text="Testing connection…">Test Daraja authentication</button></div>
                </form>
            </div>

            <div id="settings-pane-maintenance" class="settings-pane ui-tab-panel" role="tabpanel" aria-labelledby="settings-tab-maintenance">
                <form method="POST" action="{{ route('admin.settings.maintenance') }}" class="settings-form">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="_settings_tab" value="maintenance">
                    <div class="settings-status-row">
                        <div class="settings-status"><span>Customer access</span><strong style="color:{{ $maintenanceSettings['enabled'] ? '#fca5a5' : '#86efac' }};">{{ $maintenanceSettings['enabled'] ? 'Maintenance active' : 'Online' }}</strong></div>
                        <div class="settings-status"><span>Admin portal</span><strong>Remains available</strong></div>
                        <div class="settings-status"><span>M-PESA callbacks</span><strong>Remain available</strong></div>
                    </div>
                    <div class="settings-card danger-zone" style="box-shadow:none;">
                        <div class="settings-form">
                            <label class="check-row" for="maintenance_enabled">
                                <input id="maintenance_enabled" name="enabled" type="checkbox" value="1" @checked(old('enabled', $maintenanceSettings['enabled']))>
                                <span>Put tenant-facing web and Android API operations into maintenance mode</span>
                            </label>
                            <div class="field" style="margin-top:14px;">
                                <label for="maintenance_message">Customer message</label>
                                <textarea id="maintenance_message" name="message" maxlength="500" required>{{ old('message', $maintenanceSettings['message']) }}</textarea>
                            </div>
                            <div class="field" style="margin-top:14px;">
                                <label for="maintenance_password">Confirm your password</label>
                                <input id="maintenance_password" type="password" name="current_password" autocomplete="current-password" required>
                            </div>
                            <p class="tab-note">Health checks, the admin portal, and M-PESA callbacks are deliberately excluded to prevent lockout and lost payment confirmations.</p>
                            <div class="actions-row"><button type="submit" class="btn {{ $maintenanceSettings['enabled'] ? 'btn-secondary' : 'btn-danger' }}">{{ $maintenanceSettings['enabled'] ? 'Disable maintenance mode' : 'Apply maintenance setting' }}</button></div>
                        </div>
                    </div>
                </form>
            </div>
        @endif
    </section>

    <aside class="helper-panel">
        <h4>{{ $isSuperAdmin ? 'Platform control centre' : ($isLandlordOwner ? 'Landlord workspace' : 'Team member account') }}</h4>
        <p>{{ $isSuperAdmin ? 'Sensitive system changes require your current password and are written to the administrative audit log.' : ($isLandlordOwner ? 'Configure payment, tenant onboarding defaults, and account details without leaving the admin module.' : 'Update your personal profile and password here. Owner-level payment and team settings remain protected.') }}</p>

        @if($isLandlordOwner)
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

        @if($isSuperAdmin)
            <div class="settings-card" style="padding:14px;box-shadow:none;margin-bottom:16px;">
                <h4 style="margin:0 0 6px;">Operational safeguards</h4>
                <p style="margin-bottom:12px;">Review privileged changes before deployment and use controlled tools for production operations.</p>
                <div class="actions-row" style="margin:0;">
                    <a class="btn btn-secondary" href="{{ route('admin.audit-logs.index') }}">Open audit log</a>
                    <a class="btn btn-secondary" href="{{ route('admin.deployment-tools.index') }}">Deployment tools</a>
                </div>
            </div>
        @endif

        <ul>
            <li>Account tab updates your personal profile details used across the dashboard.</li>
            <li>Security tab allows changing password with current-password verification.</li>
            @if($isLandlordOwner)
                <li>Tenant Preferences sets landlord-level defaults for onboarding behavior.</li>
                <li>Payment stores the landlord-specific Daraja channel used by tenant billing flows.</li>
            @endif
            @if($isSuperAdmin)
                <li>Daraja credentials are encrypted and masked after saving.</li>
                <li>Maintenance mode preserves admin recovery, health endpoints, and payment callbacks.</li>
            @endif
        </ul>
    </aside>
</div>

<script>
    (function () {
        const paymentType = document.getElementById('payment_type');
        const paybillField = document.querySelector('[data-paybill-field]');
        const tillField = document.querySelector('[data-till-field]');

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

        syncPaymentFields();
    })();
</script>
@endsection
