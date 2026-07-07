@extends('admin.layout')
@section('page-title', 'Landlords')

@section('content')
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;gap:12px;">
    <div>
        <h2 style="font-size:16px;font-weight:600;">Landlords</h2>
        <p style="font-size:13px;color:#64748b;margin-top:4px;">Monitor landlord onboarding, portfolio size, rent collection, and outstanding balances.</p>
    </div>
    <div style="display:flex;gap:8px;align-items:center;">
        <form method="GET" style="display:flex;gap:8px;">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search landlord..." style="padding:7px 12px;border:1px solid #cbd5e1;border-radius:6px;font-size:13px;width:220px;">
            <select name="status" style="padding:7px 12px;border:1px solid #cbd5e1;border-radius:6px;font-size:13px;">
                <option value="">All statuses</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
            </select>
            <button type="submit" class="btn btn-secondary">Search</button>
        </form>
        <a href="{{ route('admin.invitations.index', ['type' => 'LANDLORD']) }}" class="btn btn-primary">Invite Landlord</a>
        <a href="{{ route('admin.landlords.create') }}" class="btn btn-secondary">Emergency Create</a>
    </div>
</div>

<div class="card">
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Properties</th>
                <th>Units</th>
                <th>Tenants</th>
                <th>Collected This Month</th>
                <th>Outstanding</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($landlords as $landlord)
            <tr>
                <td>{{ $landlord->name }}</td>
                <td style="color:#64748b;font-size:13px;">{{ $landlord->email }}</td>
                <td>{{ $landlord->phone_number ?? '-' }}</td>
                <td>{{ $landlord->properties_count }}</td>
                <td>{{ $landlord->units_count }}</td>
                <td>{{ $landlord->tenants_count }}</td>
                <td style="color:#16a34a;">KSh {{ number_format($landlord->collected_this_month, 2) }}</td>
                <td style="color:#dc2626;">KSh {{ number_format($landlord->outstanding_balance, 2) }}</td>
                <td>
                    <span class="badge {{ $landlord->is_active ? 'badge-green' : 'badge-gray' }}">
                        {{ $landlord->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </td>
                <td>
                    <a href="{{ route('admin.landlords.edit', $landlord) }}" class="btn btn-secondary" style="margin-right:6px;">Edit</a>
                    <form method="POST" action="{{ route('admin.landlords.status', $landlord) }}" style="display:inline;">
                        @csrf @method('PATCH')
                        <input type="hidden" name="is_active" value="{{ $landlord->is_active ? 0 : 1 }}">
                        <button type="submit" class="btn {{ $landlord->is_active ? 'btn-danger' : 'btn-primary' }}" onclick="return confirm('{{ $landlord->is_active ? 'Suspend this landlord account?' : 'Reactivate this landlord account?' }}')">
                            {{ $landlord->is_active ? 'Suspend' : 'Reactivate' }}
                        </button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="10" style="color:#94a3b8;text-align:center;padding:24px;">No landlords yet. Invite your first landlord to start onboarding properties.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="pagination">{{ $landlords->links() }}</div>
</div>
@endsection
