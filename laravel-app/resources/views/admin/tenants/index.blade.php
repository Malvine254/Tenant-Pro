@extends('admin.layout')
@section('page-title', 'Tenants')

@section('content')
<style>
    .tenant-shell {
        color: #e2e8f0;
    }
    .tenant-page-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 18px;
    }
    .tenant-page-top h2 {
        color: #f8fafc;
    }
    .tenant-page-top p {
        color: #94a3b8;
    }
    .tenant-tabs {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin: 0 0 18px;
    }
    .tenant-tab {
        border: 1px solid rgba(148,163,184,.18);
        background: rgba(15,23,42,.7);
        color: #cbd5e1;
        border-radius: 999px;
        padding: 9px 14px;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: .02em;
        cursor: pointer;
        transition: all .15s ease;
    }
    .tenant-tab.active {
        background: linear-gradient(180deg,#2563eb,#1d4ed8);
        border-color: rgba(96,165,250,.4);
        color: #eff6ff;
        box-shadow: 0 10px 18px rgba(37,99,235,.2);
    }
    .tenant-panel {
        display: none;
    }
    .tenant-panel.active {
        display: block;
    }
    .tenant-card {
        background: linear-gradient(180deg,#111827,#0b1220);
        border: 1px solid rgba(148,163,184,.2);
        border-radius: 18px;
        box-shadow: 0 18px 36px rgba(2,6,23,.32);
        padding: 18px;
    }
    .tenant-person{display:flex;align-items:center;gap:10px}.tenant-avatar{width:38px;height:38px;flex:0 0 38px;border-radius:50%;overflow:hidden;display:grid;place-items:center;background:linear-gradient(135deg,#7656d8,#4f46e5);color:#fff;font-size:12px;font-weight:800}.tenant-avatar img{width:100%;height:100%;display:block;object-fit:cover;border-radius:50%}
    .tenancy-nested-wrap{padding:0!important;background:rgba(15,23,42,.4)}.tenancy-nested{width:100%;border-collapse:collapse}.tenancy-nested th,.tenancy-nested td{padding:10px 12px;border-top:1px solid rgba(148,163,184,.12);font-size:13px;text-align:left;vertical-align:middle}.tenancy-nested thead th{font-size:11px;letter-spacing:.04em;text-transform:uppercase;color:#cbd5e1;background:rgba(15,23,42,.9)}.tenancy-summary{font-size:12px;color:#94a3b8}.tenant-parent-row td{background:rgba(15,23,42,.48)}
    .tenant-card table {
        width: 100%;
        border-collapse: collapse;
        color: #e2e8f0;
    }
    .tenant-card th {
        text-align: left;
        padding: 11px 13px;
        font-size: 11px;
        color: #cbd5e1;
        text-transform: uppercase;
        letter-spacing: .08em;
        background: rgba(15,23,42,.9);
        border-bottom: 1px solid rgba(148,163,184,.18);
    }
    .tenant-card td {
        padding: 12px 13px;
        vertical-align: middle;
        border-bottom: 1px solid rgba(148,163,184,.12);
        color: #e2e8f0;
    }
    .tenant-card tbody tr:hover { background: rgba(15,23,42,.6); }
    .tenant-card .empty-state {
        color: #94a3b8;
        background: rgba(15,23,42,.4);
    }
    .tenant-quick-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        justify-content: flex-end;
    }
    .tenant-quick-actions .btn {
        border: 1px solid rgba(148,163,184,.18);
    }
    .tenant-quick-actions .btn-primary {
        background: linear-gradient(180deg,#2563eb,#1d4ed8);
        color: #eff6ff;
    }
    .tenant-quick-actions .btn-secondary {
        background: rgba(148,163,184,.1);
        color: #e2e8f0;
    }
    .tenant-mini-card {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    @media (max-width: 720px) {
        .tenant-page-top {
            flex-direction: column;
        }
        .tenant-quick-actions {
            justify-content: flex-start;
        }
    }
</style>

<div class="tenant-shell">
    <div class="tenant-page-top">
        <div>
            <h2>Tenants</h2>
            <p>Manage tenant onboarding, assignment, and status from one cleaner workspace.</p>
        </div>
        <div class="tenant-quick-actions">
            <form method="GET" class="admin-filter" style="margin:0;">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name or email...">
                <button type="submit" class="btn btn-secondary">Search</button>
            </form>
        </div>
    </div>

    <div class="tenant-tabs" role="tablist" aria-label="Tenant management tabs">
        <button class="tenant-tab active" type="button" data-tab="overview">Overview</button>
        <button class="tenant-tab" type="button" data-tab="invite">Invite tenant</button>
        <button class="tenant-tab" type="button" data-tab="link">Link existing account</button>
    </div>

    <div class="tenant-panel active" data-panel="overview">
        <div class="tenant-card">
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Tenant</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Active units</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tenantUsers as $tenantUser)
                        @php
                            $activeTenancies = $tenantUser->tenancies
                                ->filter(fn($tenancy) => $tenancy->is_active && $tenancy->unit)
                                ->values();
                            $activeUnits = $activeTenancies
                                ->map(fn($tenancy) => $tenancy->unit->unit_number)
                                ->unique()
                                ->values();
                        @endphp
                        <tr class="tenant-parent-row">
                            <td><div class="tenant-person">
                                @php $tenantInitials = collect(explode(' ', trim($tenantUser->name)))->filter()->take(2)->map(fn($part) => strtoupper(substr($part, 0, 1)))->implode(''); @endphp
                                <span class="tenant-avatar">@if($tenantUser->profile_image_url)<img src="{{ str_starts_with($tenantUser->profile_image_url, 'http') ? $tenantUser->profile_image_url : asset(ltrim($tenantUser->profile_image_url, '/')) }}" alt="{{ $tenantUser->name }}">@else{{ $tenantInitials ?: 'T' }}@endif</span>
                                <div>
                                    <div>{{ $tenantUser->name }}</div>
                                    <div class="tenancy-summary">{{ $activeTenancies->count() }} active {{ $activeTenancies->count() === 1 ? 'tenancy' : 'tenancies' }}</div>
                                </div>
                            </div></td>
                            <td style="color:#94a3b8;font-size:13px;">{{ $tenantUser->email }}</td>
                            <td>{{ $tenantUser->phone_number ?? '-' }}</td>
                            <td>
                                @if($activeUnits->isNotEmpty())
                                    {{ $activeUnits->join(', ') }}
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $tenantUser->is_active ? 'badge-green' : 'badge-gray' }}">
                                    {{ $tenantUser->is_active ? 'Active account' : 'Inactive account' }}
                                </span>
                            </td>
                            <td>
                                @if($activeTenancies->isNotEmpty())
                                    <a href="{{ route('admin.tenants.show', $activeTenancies->first()) }}" class="btn btn-secondary">View</a>
                                @else
                                    <span style="color:#94a3b8;font-size:12px;">No active unit</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td colspan="6" class="tenancy-nested-wrap">
                                <table class="tenancy-nested">
                                    <thead>
                                        <tr>
                                            <th>Unit</th>
                                            <th>Property</th>
                                            <th>Move-in</th>
                                            <th>M-Pesa Details</th>
                                            <th>Closing date</th>
                                            <th>Status</th>
                                            <th>Tenancy</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($activeTenancies as $tenancy)
                                        <tr>
                                            <td>{{ $tenancy->unit->unit_number }}</td>
                                            <td>{{ $tenancy->unit?->property?->name ?? '—' }}</td>
                                            <td>{{ $tenancy->move_in_date?->format('d M Y') ?? '—' }}</td>
                                            <td><span class="badge badge-gray">Tenant-managed</span><div style="font-size:11px;color:#94a3b8;margin-top:3px;">Not edited in admin</div></td>
                                            <td>{{ $tenancy->move_out_date?->format('d M Y') ?? 'Active tenancy' }}</td>
                                            <td><span class="badge {{ $tenancy->is_active ? 'badge-green' : 'badge-gray' }}">{{ $tenancy->is_active ? 'Active' : 'Inactive' }}</span></td>
                                            <td><a href="{{ route('admin.tenants.show', $tenancy) }}" class="btn btn-secondary">Open</a></td>
                                        </tr>
                                        @empty
                                        <tr><td colspan="7" class="empty-state">No active tenancy rows for this tenant.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="empty-state">No tenants yet. Invite a tenant to a vacant unit.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="pagination">{{ $tenantUsers->links() }}</div>
        </div>

        <div class="tenant-card" style="margin-top:18px;">
            <div class="admin-page-header" style="margin-bottom:12px;">
                <div>
                    <h2 style="font-size:17px;">Unassigned Tenant Accounts</h2>
                    <p>Accounts registered from the Android app but not yet assigned to a unit.</p>
                </div>
                <div class="admin-actions">
                    <a href="{{ route('admin.invitations.index') }}" class="btn btn-primary">Invite Tenant to Unit</a>
                </div>
            </div>
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($unassignedTenantUsers as $tenantUser)
                        <tr>
                            <td><div class="tenant-person">
                                @php $userInitials = collect(explode(' ', trim($tenantUser->name)))->filter()->take(2)->map(fn($part) => strtoupper(substr($part, 0, 1)))->implode(''); @endphp
                                <span class="tenant-avatar">@if($tenantUser->profile_image_url)<img src="{{ str_starts_with($tenantUser->profile_image_url, 'http') ? $tenantUser->profile_image_url : asset(ltrim($tenantUser->profile_image_url, '/')) }}" alt="{{ $tenantUser->name }}">@else{{ $userInitials ?: 'T' }}@endif</span>
                                <span>{{ $tenantUser->name }}</span>
                            </div></td>
                            <td style="color:#94a3b8;font-size:13px;">{{ $tenantUser->email }}</td>
                            <td>{{ $tenantUser->phone_number ?? '-' }}</td>
                            <td><span class="badge {{ $tenantUser->is_active ? 'badge-green' : 'badge-gray' }}">{{ $tenantUser->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td><a href="{{ route('admin.tenants.assign', ['user_id' => $tenantUser->id]) }}" class="btn btn-secondary">Link to Unit</a></td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="empty-state">No unassigned tenant accounts.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="tenant-panel" data-panel="invite">
        <div class="tenant-card tenant-mini-card">
            <div class="admin-page-header" style="margin-bottom:0;">
                <div>
                    <h2 style="font-size:17px;">Invite tenant</h2>
                    <p>Create an onboarding invite for a tenant to a vacant unit.</p>
                </div>
            </div>
            <a href="{{ route('admin.invitations.index') }}" class="btn btn-primary" style="align-self:flex-start;">Open invitation form</a>
            <div class="tenant-card" style="padding:16px; background: rgba(15,23,42,.55); border-color: rgba(148,163,184,.15);">
                <p style="color:#94a3b8; line-height:1.6; margin:0;">This keeps the tenants page focused while the actual invite form stays in the dedicated invitations workspace with a cleaner, stacked layout.</p>
            </div>
        </div>
    </div>

    <div class="tenant-panel" data-panel="link">
        <div class="tenant-card tenant-mini-card">
            <div class="admin-page-header" style="margin-bottom:0;">
                <div>
                    <h2 style="font-size:17px;">Link existing account</h2>
                    <p>Associate a registered tenant account with a vacant unit.</p>
                </div>
            </div>
            <a href="{{ route('admin.tenants.assign') }}" class="btn btn-primary" style="align-self:flex-start;">Open assignment form</a>
            <div class="tenant-card" style="padding:16px; background: rgba(15,23,42,.55); border-color: rgba(148,163,184,.15);">
                <p style="color:#94a3b8; line-height:1.6; margin:0;">This preserves the clean overview while making the assignment flow easy to access without cluttering the main tenant table.</p>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const tabs = document.querySelectorAll('.tenant-tab');
        const panels = document.querySelectorAll('.tenant-panel');

        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                const target = tab.dataset.tab;
                tabs.forEach(btn => btn.classList.toggle('active', btn === tab));
                panels.forEach(panel => panel.classList.toggle('active', panel.dataset.panel === target));
            });
        });
    });
</script>
@endsection
