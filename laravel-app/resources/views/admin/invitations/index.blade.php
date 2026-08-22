@extends('admin.layout')
@section('page-title', $isLandlord ? 'Tenant Invitations' : 'Invitations')

@section('content')
@php
    $statusClass = [
        'PENDING' => 'badge-yellow',
        'ACCEPTED' => 'badge-green',
        'EXPIRED' => 'badge-red',
        'CANCELLED' => 'badge-gray',
        'REVOKED' => 'badge-gray',
    ];
@endphp

<style>
    .invitation-shell {
        color: #e2e8f0;
    }
    .invitation-tabs {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 18px;
    }
    .invitation-tab {
        border: 1px solid rgba(148,163,184,.18);
        background: rgba(15,23,42,.7);
        color: #cbd5e1;
        border-radius: 999px;
        padding: 9px 14px;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: .02em;
        cursor: pointer;
    }
    .invitation-tab.active {
        background: linear-gradient(180deg,#2563eb,#1d4ed8);
        border-color: rgba(96,165,250,.4);
        color: #eff6ff;
        box-shadow: 0 10px 18px rgba(37,99,235,.2);
    }
    .invitation-panel {
        display: none;
    }
    .invitation-panel.active {
        display: block;
    }
    .invitation-card {
        background: linear-gradient(180deg,#111827,#0b1220);
        border: 1px solid rgba(148,163,184,.2);
        border-radius: 18px;
        padding: 18px;
        box-shadow: 0 18px 36px rgba(2,6,23,.32);
    }
    .invitation-card h3 {
        color: #f8fafc;
        margin-bottom: 12px;
    }
    .invitation-layout {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px;
    }
    .tenant-invite-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px;
    }
    .tenant-invite-grid .full-width {
        grid-column: 1 / -1;
    }
    .tenant-invite-grid .span-2 {
        grid-column: span 2;
    }
    .invitation-card .form-group {
        margin-bottom: 0;
    }
    .invitation-card label {
        color: #cbd5e1;
    }
    .invitation-card input,
    .invitation-card select,
    .invitation-card textarea {
        background: transparent;
        border: 1px solid rgba(255,255,255,.62);
        color: #f8fafc;
    }
    .invitation-card input::placeholder,
    .invitation-card textarea::placeholder { color: rgba(248,250,252,.68); }
    .invitation-card input:focus,
    .invitation-card select:focus,
    .invitation-card textarea:focus {
        background: rgba(255,255,255,.03);
        border-color: #fff;
        box-shadow: 0 0 0 3px rgba(255,255,255,.12);
    }
    .invitation-card .btn-primary {
        background: linear-gradient(180deg,#2563eb,#1d4ed8);
        color: #eff6ff;
    }
    .invitation-card .btn-secondary {
        background: rgba(148,163,184,.1);
        color: #e2e8f0;
    }
    @media (max-width: 980px) {
        .tenant-invite-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .tenant-invite-grid .span-2 {
            grid-column: span 2;
        }
    }
    @media (max-width: 720px) {
        .invitation-layout,
        .tenant-invite-grid {
            grid-template-columns: 1fr;
        }
        .tenant-invite-grid .full-width,
        .tenant-invite-grid .span-2 {
            grid-column: auto;
        }
    }
</style>

<div class="admin-page-header invitation-shell">
    <div>
        <h2>{{ $isLandlord ? 'Invite tenants to vacant units' : 'Landlord and tenant invitations' }}</h2>
        <p>
            {{ $isLandlord
                ? 'Tenants receive an email invite, accept it, create/sign in to their account, and manage their own M-Pesa details in the Android app.'
                : 'Use email invitations for real onboarding. Platform admins can invite landlords; landlords invite tenants to vacant units.' }}
        </p>
    </div>
    <form method="GET" class="admin-filter">
        @unless($isLandlord)
            <select name="type">
                <option value="">All types</option>
                @foreach(['LANDLORD', 'TENANT'] as $type)
                    <option value="{{ $type }}" {{ request('type') === $type ? 'selected' : '' }}>{{ ucfirst(strtolower($type)) }}</option>
                @endforeach
            </select>
        @endunless
        <select name="status">
            <option value="">All statuses</option>
            @foreach(['PENDING', 'ACCEPTED', 'EXPIRED', 'CANCELLED', 'REVOKED'] as $status)
                <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ $status }}</option>
            @endforeach
        </select>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search invitee...">
        <button type="submit" class="btn btn-secondary">Filter</button>
    </form>
</div>

<div class="invitation-shell">
    @unless($isLandlord)
        <div class="invitation-tabs" role="tablist" aria-label="Invitation types">
            <button class="invitation-tab active" type="button" data-tab="landlord">Landlord invite</button>
            <button class="invitation-tab" type="button" data-tab="tenant">Tenant invite</button>
        </div>
    @endunless

    <div class="invitation-panel {{ !$isLandlord ? 'active' : '' }}" data-panel="landlord">
        @unless($isLandlord)
            <div class="invitation-card">
                <h3>Invite Landlord</h3>
                <form method="POST" action="{{ route('admin.invitations.landlords.store') }}">
                    @csrf
                    <div class="invitation-layout dual">
                        <div class="form-group">
                            <label>Landlord full name</label>
                            <input name="invitee_name" value="{{ old('invitee_name') }}" placeholder="Enter full name" required>
                            @error('invitee_name')<div class="form-error">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" value="{{ old('email') }}" placeholder="name@example.com" required>
                            @error('email')<div class="form-error">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label>Phone, optional</label>
                            <input name="phone_number" value="{{ old('phone_number') }}" placeholder="e.g. 2547XXXXXXXX">
                        </div>
                        <div class="form-group">
                            <label>Business name, optional</label>
                            <input name="business_name" value="{{ old('business_name') }}" placeholder="e.g. Starmax Properties">
                        </div>
                        <div class="form-group">
                            <label>Invite expires</label>
                            <input type="date" name="expires_at" value="{{ old('expires_at', $tenantInviteExpiryDefault ?? now()->addDays(7)->toDateString()) }}" placeholder="Select expiry date" required>
                            @error('expires_at')<div class="form-error">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group span-2">
                            <label>Optional message</label>
                            <textarea name="message" rows="3" placeholder="Add a short welcome message">{{ old('message') }}</textarea>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Send Landlord Invite</button>
                </form>
            </div>
        @endunless
    </div>

    <div class="invitation-panel {{ $isLandlord ? 'active' : '' }}" data-panel="tenant">
        <div class="invitation-card">
            <h3>Invite Tenant to Vacant Unit</h3>
            <form method="POST" action="{{ route('admin.invitations.tenants.store') }}">
                @csrf
                <div class="tenant-invite-grid">
                    <div class="form-group">
                        <label>Property</label>
                        <select id="tenantInviteProperty" name="property_id" required {{ $properties->isEmpty() ? 'disabled' : '' }}>
                            <option value="">- Select property -</option>
                            @foreach($properties as $property)
                                <option value="{{ $property->id }}" {{ old('property_id', request('property_id')) === $property->id ? 'selected' : '' }}>{{ $property->name }}</option>
                            @endforeach
                        </select>
                        @if($properties->isEmpty())
                            <div class="form-error">No properties with vacant units yet. Add property units before inviting tenants.</div>
                        @endif
                        @error('property_id')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label>Vacant unit</label>
                        <select id="tenantInviteUnit" name="unit_id" required {{ $properties->flatMap->units->isEmpty() ? 'disabled' : '' }}>
                            <option value="">- Select vacant unit -</option>
                            @foreach($properties as $property)
                                @foreach($property->units as $unit)
                                    <option value="{{ $unit->id }}" data-property-id="{{ $property->id }}" data-rent="{{ $unit->rent_amount }}" {{ old('unit_id', request('unit_id')) === $unit->id ? 'selected' : '' }}>
                                        {{ $property->name }} / Unit {{ $unit->unit_number }} — {{ $unit->rent_amount_formatted }}
                                    </option>
                                @endforeach
                            @endforeach
                        </select>
                        @error('unit_id')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label>Select existing tenant account</label>
                        <select id="tenantInviteUser" name="tenant_user_id">
                            <option value="">- Select tenant user (optional) -</option>
                            @foreach($tenantUsers as $tenantUser)
                                <option value="{{ $tenantUser->id }}" data-name="{{ $tenantUser->name }}" data-email="{{ $tenantUser->email }}" data-phone="{{ $tenantUser->phone_number }}" {{ old('tenant_user_id') === $tenantUser->id ? 'selected' : '' }}>
                                    {{ $tenantUser->name ?: 'Unnamed Tenant' }} | {{ $tenantUser->email }}{{ $tenantUser->phone_number ? ' | ' . $tenantUser->phone_number : '' }}
                                </option>
                            @endforeach
                        </select>
                        <p style="font-size:12px;color:#94a3b8;margin-top:6px;">
                            Selecting a tenant auto-fills name, email, and phone, and links the unit immediately.
                        </p>
                        @error('tenant_user_id')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label>Tenant name, optional</label>
                        <input id="tenantInviteName" name="invitee_name" value="{{ old('invitee_name') }}" placeholder="Enter tenant full name">
                    </div>
                    <div class="form-group">
                        <label>Tenant email</label>
                        <input id="tenantInviteEmail" type="email" name="email" value="{{ old('email') }}" placeholder="tenant@example.com" required>
                        @error('email')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label>Tenant phone, optional</label>
                        <input id="tenantInvitePhone" name="phone_number" value="{{ old('phone_number') }}" placeholder="e.g. 2547XXXXXXXX">
                    </div>
                    <div class="form-group">
                        <label>Move-in date, optional</label>
                        <input type="date" name="move_in_date" value="{{ old('move_in_date') }}" placeholder="Select move-in date">
                    </div>
                    <div class="form-group">
                        <label>Monthly rent</label>
                        <input id="tenantInviteRent" type="number" step="0.01" min="0" name="rent_amount" value="{{ old('rent_amount') }}" placeholder="e.g. 25000">
                    </div>
                    <div class="form-group">
                        <label>Deposit, optional</label>
                        <input type="number" step="0.01" min="0" name="deposit_amount" value="{{ old('deposit_amount') }}" placeholder="e.g. 25000">
                    </div>
                    <div class="form-group">
                        <label>Invite expires</label>
                        <input type="date" name="expires_at" value="{{ old('expires_at', $tenantInviteExpiryDefault ?? now()->addDays(7)->toDateString()) }}" placeholder="Select expiry date" required>
                    </div>
                    <div class="form-group full-width">
                        <label>Optional message</label>
                        <textarea name="message" rows="3" placeholder="Add a short message for the tenant">{{ old('message') }}</textarea>
                    </div>
                    <p class="full-width" style="font-size:12px;color:#94a3b8;margin:-4px 0 12px;">
                    M-Pesa details are not collected here. The tenant adds and controls their own payment phone in the Android app.
                    </p>
                </div>
                <button type="submit" class="btn btn-primary" {{ $properties->isEmpty() ? 'disabled' : '' }}>Send Tenant Invite</button>
            </form>
        </div>
    </div>
</div>

<div class="card" style="margin-top:18px;">
    <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Invitee</th>
                    <th>Property</th>
                    <th>Unit</th>
                    <th>Status</th>
                    <th>Sent</th>
                    <th>Expires</th>
                    <th>Accepted</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invitations as $invitation)
                    <tr>
                        <td>{{ ucfirst(strtolower($invitation->invite_type ?? 'TENANT')) }}</td>
                        <td>
                            <strong>{{ $invitation->invitee_name ?: ($invitation->email ?: $invitation->phone_number) }}</strong><br>
                            <span class="muted" style="font-size:12px;">{{ $invitation->email ?: $invitation->phone_number }}</span>
                        </td>
                        <td>{{ $invitation->property?->name ?? '—' }}</td>
                        <td>{{ $invitation->unit?->unit_number ?? '—' }}</td>
                        <td><span class="badge {{ $statusClass[$invitation->status] ?? 'badge-gray' }}">{{ $invitation->status }}</span></td>
                        <td>{{ ($invitation->last_sent_at ?: $invitation->created_at)?->format('d M Y') }}</td>
                        <td>{{ $invitation->expires_at?->format('d M Y') }}</td>
                        <td>{{ $invitation->accepted_at?->format('d M Y') ?? '—' }}</td>
                        <td style="white-space:nowrap;">
                            @if(in_array($invitation->status, ['PENDING', 'EXPIRED'], true))
                                <form method="POST" action="{{ route('admin.invitations.resend', $invitation) }}" style="display:inline;">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="btn btn-secondary">Resend</button>
                                </form>
                            @endif
                            @if($invitation->status !== 'ACCEPTED' && $invitation->status !== 'CANCELLED')
                                <form method="POST" action="{{ route('admin.invitations.cancel', $invitation) }}" style="display:inline;">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="btn btn-danger" onclick="return confirm('Cancel this invitation?')">Cancel</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="empty-state">
                            {{ $isLandlord
                                ? 'No tenant invitations yet. Invite a tenant to a vacant unit.'
                                : 'No invitations yet. Invite your first landlord or tenant to start onboarding.' }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination">{{ $invitations->links() }}</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const tabs = document.querySelectorAll('.invitation-tab');
    const panels = document.querySelectorAll('.invitation-panel');

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const target = tab.dataset.tab;
            tabs.forEach(btn => btn.classList.toggle('active', btn === tab));
            panels.forEach(panel => panel.classList.toggle('active', panel.dataset.panel === target));
        });
    });

    const property = document.getElementById('tenantInviteProperty');
    const unit = document.getElementById('tenantInviteUnit');
    const rent = document.getElementById('tenantInviteRent');
    const tenantUser = document.getElementById('tenantInviteUser');
    const tenantName = document.getElementById('tenantInviteName');
    const tenantEmail = document.getElementById('tenantInviteEmail');
    const tenantPhone = document.getElementById('tenantInvitePhone');
    if (!property || !unit) return;

    const filterUnits = () => {
        const propertyId = property.value;
        Array.from(unit.options).forEach((option, index) => {
            if (index > 0) option.hidden = option.dataset.propertyId !== propertyId;
        });
        if (unit.selectedOptions[0]?.hidden) unit.value = '';
        unit.disabled = !propertyId;
        syncRent();
    };

    const syncRent = () => {
        const selected = unit.selectedOptions[0];
        if (selected?.dataset.rent && (!rent.value || rent.dataset.autofilled === '1')) {
            rent.value = selected.dataset.rent;
            rent.dataset.autofilled = '1';
        }
    };

    rent?.addEventListener('input', () => rent.dataset.autofilled = '0');

    const autofillTenant = () => {
        if (!tenantUser) return;
        const selected = tenantUser.selectedOptions[0];
        if (!selected || !selected.value) return;

        if (tenantName && !tenantName.value) tenantName.value = selected.dataset.name || '';
        if (tenantEmail && !tenantEmail.value) tenantEmail.value = selected.dataset.email || '';
        if (tenantPhone && !tenantPhone.value) tenantPhone.value = selected.dataset.phone || '';
    };

    tenantUser?.addEventListener('change', autofillTenant);
    property.addEventListener('change', filterUnits);
    unit.addEventListener('change', syncRent);
    filterUnits();
    autofillTenant();
});
</script>
@endsection
