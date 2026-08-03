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

<div class="admin-page-header">
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

<div style="display:grid;grid-template-columns:{{ $isLandlord ? '1fr' : '1fr 1fr' }};gap:16px;margin-bottom:18px;">
    @unless($isLandlord)
        <div class="card">
            <h3 class="section-heading">Invite Landlord</h3>
            <form method="POST" action="{{ route('admin.invitations.landlords.store') }}">
                @csrf
                <div class="form-group">
                    <label>Landlord full name</label>
                    <input name="invitee_name" value="{{ old('invitee_name') }}" required>
                    @error('invitee_name')<div class="form-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" required>
                    @error('email')<div class="form-error">{{ $message }}</div>@enderror
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div class="form-group">
                        <label>Phone, optional</label>
                        <input name="phone_number" value="{{ old('phone_number') }}">
                    </div>
                    <div class="form-group">
                        <label>Business name, optional</label>
                        <input name="business_name" value="{{ old('business_name') }}">
                    </div>
                </div>
                <div class="form-group">
                    <label>Invite expires</label>
                    <input type="date" name="expires_at" value="{{ old('expires_at', now()->addDays(7)->toDateString()) }}" required>
                    @error('expires_at')<div class="form-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label>Optional message</label>
                    <textarea name="message" rows="3">{{ old('message') }}</textarea>
                </div>
                <button type="submit" class="btn btn-primary">Send Landlord Invite</button>
            </form>
        </div>
    @endunless

    <div class="card">
        <h3 class="section-heading">Invite Tenant to Vacant Unit</h3>
        <form method="POST" action="{{ route('admin.invitations.tenants.store') }}">
            @csrf
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
                        <option
                            value="{{ $tenantUser->id }}"
                            data-name="{{ $tenantUser->name }}"
                            data-email="{{ $tenantUser->email }}"
                            data-phone="{{ $tenantUser->phone_number }}"
                            {{ old('tenant_user_id') === $tenantUser->id ? 'selected' : '' }}
                        >
                            {{ $tenantUser->name ?: 'Unnamed Tenant' }} | {{ $tenantUser->email }}{{ $tenantUser->phone_number ? ' | ' . $tenantUser->phone_number : '' }}
                        </option>
                    @endforeach
                </select>
                <p style="font-size:12px;color:#64748b;margin-top:6px;">
                    Selecting a tenant auto-fills name, email, and phone, and links the unit immediately.
                    The invitation code is still sent and can be used as a fallback confirmation.
                </p>
                @error('tenant_user_id')<div class="form-error">{{ $message }}</div>@enderror
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div class="form-group">
                    <label>Tenant name, optional</label>
                    <input id="tenantInviteName" name="invitee_name" value="{{ old('invitee_name') }}">
                </div>
                <div class="form-group">
                    <label>Tenant email</label>
                    <input id="tenantInviteEmail" type="email" name="email" value="{{ old('email') }}" required>
                    @error('email')<div class="form-error">{{ $message }}</div>@enderror
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div class="form-group">
                    <label>Tenant phone, optional</label>
                    <input id="tenantInvitePhone" name="phone_number" value="{{ old('phone_number') }}">
                </div>
                <div class="form-group">
                    <label>Move-in date, optional</label>
                    <input type="date" name="move_in_date" value="{{ old('move_in_date') }}">
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
                <div class="form-group">
                    <label>Monthly rent</label>
                    <input id="tenantInviteRent" type="number" step="0.01" min="0" name="rent_amount" value="{{ old('rent_amount') }}">
                </div>
                <div class="form-group">
                    <label>Deposit, optional</label>
                    <input type="number" step="0.01" min="0" name="deposit_amount" value="{{ old('deposit_amount') }}">
                </div>
                <div class="form-group">
                    <label>Invite expires</label>
                    <input type="date" name="expires_at" value="{{ old('expires_at', now()->addDays(7)->toDateString()) }}" required>
                </div>
            </div>
            <div class="form-group">
                <label>Optional message</label>
                <textarea name="message" rows="3">{{ old('message') }}</textarea>
            </div>
            <p style="font-size:12px;color:#64748b;margin:-4px 0 12px;">
                M-Pesa details are not collected here. The tenant adds and controls their own payment phone in the Android app.
            </p>
            <button type="submit" class="btn btn-primary" {{ $properties->isEmpty() ? 'disabled' : '' }}>Send Tenant Invite</button>
        </form>
    </div>
</div>

<div class="card">
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
