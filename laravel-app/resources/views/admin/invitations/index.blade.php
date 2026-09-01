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
    $workspace = request('workspace');
    if (! in_array($workspace, ['create', 'history'], true)) {
        $workspace = request()->filled('edit') || request()->filled('search') || request()->filled('status') || request()->filled('type')
            ? 'history'
            : 'create';
    }
    $inviteTypeTab = $isLandlord || request()->filled('property_id') || request()->filled('unit_id')
        ? 'tenant'
        : (request('invite_type_tab') === 'tenant' ? 'tenant' : 'landlord');
@endphp

<style>
    .invitation-shell {
        color: #e2e8f0;
    }
    .invitation-workspace-tabs {
        display: flex;
        gap: 8px;
        margin-bottom: 18px;
        padding: 5px;
        width: fit-content;
        border: 1px solid rgba(148,163,184,.18);
        border-radius: 14px;
        background: rgba(15,23,42,.72);
    }
    .invitation-workspace-tab {
        border: 0;
        border-radius: 10px;
        padding: 10px 16px;
        background: transparent;
        color: #94a3b8;
        font-weight: 750;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .invitation-workspace-tab.active { background:#2563eb; color:#fff; }
    .invitation-workspace-panel { display:none; }
    .invitation-workspace-panel.active { display:block; }
    .invitation-advanced { grid-column:1 / -1; border:1px solid rgba(148,163,184,.18); border-radius:14px; background:rgba(15,23,42,.45); }
    .invitation-advanced summary { cursor:pointer; padding:14px 16px; color:#e2e8f0; font-weight:700; }
    .invitation-advanced-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:16px; padding:0 16px 16px; }
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
        text-decoration: none;
        display: inline-flex;
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
        .tenant-invite-grid,
        .invitation-advanced-grid {
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
</div>

<div class="invitation-workspace-tabs" role="tablist" aria-label="Invitation workspace">
    <a id="workspace-tab-create" class="invitation-workspace-tab {{ $workspace === 'create' ? 'active' : '' }}" role="tab" aria-selected="{{ $workspace === 'create' ? 'true' : 'false' }}" aria-controls="workspace-panel-create" href="{{ route('admin.invitations.index', array_filter(['workspace' => 'create', 'property_id' => request('property_id'), 'unit_id' => request('unit_id')])) }}">Create invitation</a>
    <a id="workspace-tab-history" class="invitation-workspace-tab {{ $workspace === 'history' ? 'active' : '' }}" role="tab" aria-selected="{{ $workspace === 'history' ? 'true' : 'false' }}" aria-controls="workspace-panel-history" href="{{ route('admin.invitations.index', ['workspace' => 'history']) }}">Invitation history <span class="badge badge-gray">{{ $invitations->total() }}</span></a>
</div>

<section id="workspace-panel-create" class="invitation-workspace-panel {{ $workspace === 'create' ? 'active' : '' }}" role="tabpanel" aria-labelledby="workspace-tab-create">

<div class="invitation-shell">
    @unless($isLandlord)
        <div class="invitation-tabs" role="tablist" aria-label="Invitation types">
            <a class="invitation-tab {{ $inviteTypeTab === 'landlord' ? 'active' : '' }}" role="tab" aria-selected="{{ $inviteTypeTab === 'landlord' ? 'true' : 'false' }}" href="{{ route('admin.invitations.index', ['workspace' => 'create', 'invite_type_tab' => 'landlord']) }}">Landlord invite</a>
            <a class="invitation-tab {{ $inviteTypeTab === 'tenant' ? 'active' : '' }}" role="tab" aria-selected="{{ $inviteTypeTab === 'tenant' ? 'true' : 'false' }}" href="{{ route('admin.invitations.index', ['workspace' => 'create', 'invite_type_tab' => 'tenant']) }}">Tenant invite</a>
        </div>
    @endunless

    <div class="invitation-panel {{ $inviteTypeTab === 'landlord' ? 'active' : '' }}" data-panel="landlord">
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

    <div class="invitation-panel {{ $inviteTypeTab === 'tenant' ? 'active' : '' }}" data-panel="tenant">
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
                        <label>Tenant email (required if no existing tenant selected)</label>
                        <input id="tenantInviteEmail" type="email" name="email" value="{{ old('email') }}" placeholder="tenant@example.com">
                        @error('email')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label>Tenant phone, optional</label>
                        <input id="tenantInvitePhone" name="phone_number" value="{{ old('phone_number') }}" placeholder="e.g. 2547XXXXXXXX">
                    </div>
                    <div class="form-group">
                        <label>Invite expires</label>
                        <input type="date" name="expires_at" value="{{ old('expires_at', $tenantInviteExpiryDefault ?? now()->addDays(7)->toDateString()) }}" placeholder="Select expiry date" required>
                    </div>
                    <details class="invitation-advanced" {{ old('move_in_date') || old('message') ? 'open' : '' }}>
                        <summary>Optional tenancy details and message</summary>
                        <div class="invitation-advanced-grid">
                            <div class="form-group">
                                <label>Move-in date</label>
                                <input type="date" name="move_in_date" value="{{ old('move_in_date') }}">
                            </div>
                            <div class="form-group">
                                <label>Monthly rent</label>
                                <input id="tenantInviteRent" type="number" step="0.01" min="0" value="" readonly aria-describedby="tenantRentHelp">
                                <small id="tenantRentHelp" style="display:block;margin-top:6px;color:#94a3b8;">Uses the rent saved on the selected unit.</small>
                            </div>
                            <div class="form-group">
                                <label>Deposit</label>
                                <input type="text" value="Equal to one month of rent" readonly aria-describedby="tenantDepositHelp">
                                <small id="tenantDepositHelp" style="display:block;margin-top:6px;color:#94a3b8;">Charged once on the move-in invoice and not repeated in later months.</small>
                            </div>
                            <div class="form-group">
                                <label>Personal message</label>
                                <textarea name="message" rows="3" placeholder="Add a short message for the tenant">{{ old('message') }}</textarea>
                            </div>
                        </div>
                    </details>
                    <p class="full-width" style="font-size:12px;color:#94a3b8;margin:-4px 0 12px;">
                    M-Pesa details are not collected here. The tenant adds and controls their own payment phone in the Android app.
                    </p>
                </div>
                <button type="submit" class="btn btn-primary" {{ $properties->isEmpty() ? 'disabled' : '' }}>Send Tenant Invite</button>
            </form>
        </div>
    </div>
</div>
</section>

<section id="workspace-panel-history" class="invitation-workspace-panel {{ $workspace === 'history' ? 'active' : '' }}" role="tabpanel" aria-labelledby="workspace-tab-history">
<div class="invitation-card invitation-shell" style="margin-bottom:18px;">
    <div class="admin-page-header" style="margin-bottom:0;">
        <div><h3 style="margin:0 0 5px;">Invitation history</h3><p>Search, review, resend, edit, or cancel invitations.</p></div>
        <form method="GET" class="admin-filter">
            <input type="hidden" name="workspace" value="history">
            @unless($isLandlord)
                <select name="type" aria-label="Invitation type">
                    <option value="">All types</option>
                    @foreach(['LANDLORD', 'TENANT'] as $type)
                        <option value="{{ $type }}" {{ request('type') === $type ? 'selected' : '' }}>{{ ucfirst(strtolower($type)) }}</option>
                    @endforeach
                </select>
            @endunless
            <select name="status" aria-label="Invitation status">
                <option value="">All statuses</option>
                @foreach(['PENDING', 'ACCEPTED', 'EXPIRED', 'CANCELLED', 'REVOKED'] as $status)
                    <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ $status }}</option>
                @endforeach
            </select>
            <input type="search" name="search" value="{{ request('search') }}" placeholder="Search invitee..." aria-label="Search invitations">
            <button type="submit" class="btn btn-secondary">Apply filters</button>
        </form>
    </div>
</div>

@if($editingInvitation)
    <div class="invitation-card invitation-shell" style="margin-top:18px;">
        <h3>Edit {{ strtolower($editingInvitation->invite_type) }} invitation</h3>
        <p style="color:#94a3b8;font-size:13px;margin:-4px 0 16px;">
            Saving sends a refreshed invitation. If you correct a tenant's email, their temporary sign-in is moved to the new address and a new temporary password is sent.
        </p>
        <form method="POST" action="{{ route('admin.invitations.update', $editingInvitation) }}">
            @csrf @method('PATCH')
            <div class="tenant-invite-grid">
                <div class="form-group">
                    <label>Full name</label>
                    <input name="invitee_name" value="{{ old('invitee_name', $editingInvitation->invitee_name) }}" placeholder="Enter full name">
                    @error('invitee_name')<div class="form-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="{{ old('email', $editingInvitation->email) }}" placeholder="name@example.com" required>
                    @error('email')<div class="form-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label>Phone, optional</label>
                    <input name="phone_number" value="{{ old('phone_number', $editingInvitation->phone_number) }}" placeholder="e.g. 2547XXXXXXXX">
                    @error('phone_number')<div class="form-error">{{ $message }}</div>@enderror
                </div>
                @if($editingInvitation->invite_type === 'LANDLORD')
                    <div class="form-group">
                        <label>Business name, optional</label>
                        <input name="business_name" value="{{ old('business_name', $editingInvitation->business_name) }}" placeholder="e.g. Starmax Properties">
                    </div>
                @endif
                <div class="form-group">
                    <label>Invite expires</label>
                    <input type="date" name="expires_at" value="{{ old('expires_at', $editingInvitation->expires_at?->toDateString()) }}" required>
                    @error('expires_at')<div class="form-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-group full-width">
                    <label>Optional message</label>
                    <textarea name="message" rows="3" placeholder="Add a short message">{{ old('message', $editingInvitation->message) }}</textarea>
                    @error('message')<div class="form-error">{{ $message }}</div>@enderror
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Update &amp; resend invitation</button>
            <a href="{{ route('admin.invitations.index') }}" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
@endif

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
                                <a href="{{ route('admin.invitations.index', array_filter(['edit' => $invitation->id, 'workspace' => 'history', 'type' => request('type'), 'status' => request('status'), 'search' => request('search')])) }}" class="btn btn-secondary">Edit</a>
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
</section>

<script>
document.addEventListener('DOMContentLoaded', () => {
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
        if (selected?.dataset.rent) {
            rent.value = selected.dataset.rent;
        } else if (rent) {
            rent.value = '';
        }
    };

    const autofillTenant = () => {
        if (!tenantUser) return;
        const selected = tenantUser.selectedOptions[0];
        if (!selected || !selected.value) return;

        if (tenantName && !tenantName.value) tenantName.value = selected.dataset.name || '';
        if (tenantEmail && !tenantEmail.value) tenantEmail.value = selected.dataset.email || '';
        if (tenantPhone && !tenantPhone.value) tenantPhone.value = selected.dataset.phone || '';
    };

    const syncTenantEmailRequirement = () => {
        if (!tenantUser || !tenantEmail) return;
        const hasSelectedTenant = !!tenantUser.value;
        tenantEmail.required = !hasSelectedTenant;
    };

    tenantUser?.addEventListener('change', () => {
        autofillTenant();
        syncTenantEmailRequirement();
    });
    property.addEventListener('change', filterUnits);
    unit.addEventListener('change', syncRent);
    filterUnits();
    autofillTenant();
    syncTenantEmailRequirement();
});
</script>
@endsection
