@extends('admin.layout')
@section('page-title', 'Tenants')

@section('content')
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
    <h2 style="font-size:16px;font-weight:600;">All Tenants</h2>
    <form method="GET" style="display:flex;gap:8px;">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name or email..." style="padding:7px 12px;border:1px solid #cbd5e1;border-radius:6px;font-size:13px;width:220px;">
        <button type="submit" class="btn btn-secondary">Search</button>
    </form>
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
@endsection
