@extends('admin.layout')
@section('page-title', 'Tenants')

@section('content')
<style>
.tenant-person{display:flex;align-items:center;gap:10px}.tenant-avatar{width:38px;height:38px;flex:0 0 38px;border-radius:50%;overflow:hidden;display:grid;place-items:center;background:linear-gradient(135deg,#7656d8,#4f46e5);color:#fff;font-size:12px;font-weight:800}.tenant-avatar img{width:100%;height:100%;display:block;object-fit:cover;border-radius:50%}
.tenancy-nested-wrap{padding:0!important;background:#f8fafc}.tenancy-nested{width:100%;border-collapse:collapse}.tenancy-nested th,.tenancy-nested td{padding:10px 12px;border-top:1px solid #e2e8f0;font-size:13px;text-align:left;vertical-align:middle}.tenancy-nested thead th{font-size:11px;letter-spacing:.04em;text-transform:uppercase;color:#64748b;background:#f1f5f9}.tenancy-summary{font-size:12px;color:#64748b}.tenant-parent-row td{background:#fff}
</style>
<div class="admin-page-header">
    <div>
        <h2>Tenants</h2>
        <p>Invite tenants, link registered accounts to vacant units, and review tenancy status from one place.</p>
    </div>
    <div class="admin-actions">
        <form method="GET" class="admin-filter">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name or email...">
            <button type="submit" class="btn btn-secondary">Search</button>
        </form>
        <a href="{{ route('admin.invitations.index') }}" class="btn btn-primary">Invite Tenant</a>
        <a href="{{ route('admin.tenants.assign') }}" class="btn btn-secondary">Link Existing Account</a>
        @if(in_array(auth()->user()?->role?->name, ['SUPER_ADMIN','ADMIN'], true))
            <a href="{{ route('admin.tenants.create') }}" class="btn btn-secondary">Emergency Create</a>
        @endif
    </div>
</div>
<div class="card">
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
                <td style="color:#64748b;font-size:13px;">{{ $tenantUser->email }}</td>
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
                                <td>
                                    <span class="badge badge-gray">Tenant-managed</span>
                                    <div style="font-size:11px;color:#94a3b8;margin-top:3px;">Not edited in admin</div>
                                </td>
                                <td>{{ $tenancy->move_out_date?->format('d M Y') ?? 'Active tenancy' }}</td>
                                <td>
                                    <span class="badge {{ $tenancy->is_active ? 'badge-green' : 'badge-gray' }}">
                                        {{ $tenancy->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td><a href="{{ route('admin.tenants.show', $tenancy) }}" class="btn btn-secondary">Open</a></td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="empty-state">No active tenancy rows for this tenant.</td>
                            </tr>
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

<div class="card" style="margin-top:18px;">
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
                <td style="color:#64748b;font-size:13px;">{{ $tenantUser->email }}</td>
                <td>{{ $tenantUser->phone_number ?? '-' }}</td>
                <td>
                    <span class="badge {{ $tenantUser->is_active ? 'badge-green' : 'badge-gray' }}">
                        {{ $tenantUser->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </td>
                <td><a href="{{ route('admin.tenants.assign', ['user_id' => $tenantUser->id]) }}" class="btn btn-secondary">Link to Unit</a></td>
            </tr>
            @empty
            <tr><td colspan="5" class="empty-state">No unassigned tenant accounts.</td></tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>
@endsection
