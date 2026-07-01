@extends('admin.layout')
@section('page-title', 'Tenants')

@section('content')
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
    <h2 style="font-size:16px;font-weight:600;">All Tenants</h2>
    <div style="display:flex;gap:8px;align-items:center;">
        <form method="GET" style="display:flex;gap:8px;">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name or email..." style="padding:7px 12px;border:1px solid #cbd5e1;border-radius:6px;font-size:13px;width:220px;">
            <button type="submit" class="btn btn-secondary">Search</button>
        </form>
        <a href="{{ route('admin.tenants.assign') }}" class="btn btn-secondary">Assign Existing</a>
        <a href="{{ route('admin.tenants.create') }}" class="btn btn-primary">+ Add Tenant</a>
    </div>
</div>
<div class="card">
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Unit</th>
                <th>Property</th>
                <th>Move-in</th>
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
                    <span class="badge {{ $tenant->is_active ? 'badge-green' : 'badge-gray' }}">
                        {{ $tenant->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </td>
                <td><a href="{{ route('admin.tenants.show', $tenant) }}" class="btn btn-secondary">View</a></td>
            </tr>
            @empty
            <tr><td colspan="7" style="color:#94a3b8;text-align:center;padding:24px;">No tenants found.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="pagination">{{ $tenants->links() }}</div>
</div>

<div class="card" style="margin-top:18px;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
        <div>
            <h3 style="font-size:13px;color:#64748b;text-transform:uppercase;">Unassigned Tenant Accounts</h3>
            <div style="font-size:12px;color:#94a3b8;margin-top:4px;">Accounts registered from the Android app but not yet assigned to a unit.</div>
        </div>
        <a href="{{ route('admin.tenants.assign') }}" class="btn btn-primary">Assign to Unit</a>
    </div>
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
                <td><a href="{{ route('admin.tenants.assign', ['user_id' => $tenantUser->id]) }}" class="btn btn-secondary">Assign</a></td>
            </tr>
            @empty
            <tr><td colspan="5" style="color:#94a3b8;text-align:center;padding:20px;">No unassigned tenant accounts.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
