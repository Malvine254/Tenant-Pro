@extends('admin.layout')
@section('page-title', 'Landlords')

@section('content')
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;gap:12px;">
    <h2 style="font-size:16px;font-weight:600;">All Landlords</h2>
    <div style="display:flex;gap:8px;align-items:center;">
        <form method="GET" style="display:flex;gap:8px;">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search landlord..." style="padding:7px 12px;border:1px solid #cbd5e1;border-radius:6px;font-size:13px;width:220px;">
            <button type="submit" class="btn btn-secondary">Search</button>
        </form>
        <a href="{{ route('admin.landlords.create') }}" class="btn btn-primary">+ Add Landlord</a>
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
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($landlords as $landlord)
            <tr>
                <td>{{ $landlord->name }}</td>
                <td style="color:#64748b;font-size:13px;">{{ $landlord->email }}</td>
                <td>{{ $landlord->phone_number ?? '-' }}</td>
                <td>{{ $landlord->properties_count }}</td>
                <td>
                    <span class="badge {{ $landlord->is_active ? 'badge-green' : 'badge-gray' }}">
                        {{ $landlord->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </td>
            </tr>
            @empty
            <tr><td colspan="5" style="color:#94a3b8;text-align:center;padding:24px;">No landlords found.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="pagination">{{ $landlords->links() }}</div>
</div>
@endsection
