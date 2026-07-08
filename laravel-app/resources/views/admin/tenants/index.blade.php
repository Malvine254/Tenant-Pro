@extends('admin.layout')
@section('page-title', 'Tenants')

@section('content')
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
                <th>Name</th>
                <th>Email</th>
                <th>Unit</th>
                <th>Property</th>
                <th>Move-in</th>
                <th>M-Pesa Details</th>
                <th>Closing date</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($tenants as $tenant)
            <tr>
                <td>{{ $tenant->user->name }}</td>
                <td style="color:#64748b;font-size:13px;">{{ $tenant->user->email }}</td>
                <td>{{ $tenant->unit->unit_number }}</td>
                <td>{{ $tenant->unit->property->name ?? '—' }}</td>
                <td style="font-size:13px;">{{ $tenant->move_in_date?->format('d M Y') }}</td>
                <td>
                    <span class="badge badge-gray">Tenant-managed</span>
                    <div style="font-size:11px;color:#94a3b8;margin-top:3px;">Not edited in admin</div>
                </td>
                <td style="font-size:13px;">
                    {{ $tenant->move_out_date?->format('d M Y') ?? ($tenant->is_active ? 'Active tenancy' : '—') }}
                </td>
                <td>
                    <span class="badge {{ $tenant->is_active ? 'badge-green' : 'badge-gray' }}">
                        {{ $tenant->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </td>
                <td><a href="{{ route('admin.tenants.show', $tenant) }}" class="btn btn-secondary">View</a></td>
            </tr>
            @empty
            <tr><td colspan="9" class="empty-state">No tenants yet. Invite a tenant to a vacant unit.</td></tr>
            @endforelse
        </tbody>
    </table>
    </div>
    <div class="pagination">{{ $tenants->links() }}</div>
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
                <td>{{ $tenantUser->name }}</td>
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
