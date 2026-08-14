@extends('admin.layout')
@section('page-title', 'Properties')

@section('content')
<style>
    .properties-page {
        color:#e2e8f0;
    }
    .properties-page .admin-page-header h2 {
        color:#f8fafc;
    }
    .properties-page .admin-page-header p {
        color:#94a3b8;
    }
    .properties-page .card {
        background:linear-gradient(180deg,#111827,#0b1220);
        border:1px solid rgba(148,163,184,.2);
        box-shadow:0 10px 24px rgba(2,6,23,.3);
    }
    .properties-page table {
        color:#e2e8f0;
        background:transparent;
    }
    .properties-page th {
        background:rgba(15,23,42,.92);
        color:#cbd5e1;
        border-bottom:1px solid rgba(148,163,184,.18);
    }
    .properties-page td {
        border-bottom:1px solid rgba(148,163,184,.12);
        color:#e2e8f0;
    }
    .properties-page tbody tr:hover {
        background:rgba(15,23,42,.8);
    }
    .properties-page a {
        color:#bfdbfe;
        text-decoration:none;
    }
    .properties-page .btn {
        border:1px solid rgba(148,163,184,.18);
        box-shadow:none;
    }
    .properties-page .btn-primary {
        background:linear-gradient(180deg,#2563eb,#1d4ed8);
        color:#eff6ff;
        box-shadow:0 8px 16px rgba(37,99,235,.22);
    }
    .properties-page .btn-secondary {
        background:rgba(148,163,184,.1);
        color:#e2e8f0;
        border-color:rgba(148,163,184,.18);
    }
    .properties-page .btn-danger {
        background:linear-gradient(180deg,#ef4444,#dc2626);
        color:#fff;
        box-shadow:0 8px 16px rgba(239,68,68,.2);
    }
    .properties-page .empty-state {
        color:#94a3b8;
        background:rgba(15,23,42,.4);
    }
    .properties-page .pagination a,
    .properties-page .pagination span {
        background:rgba(15,23,42,.8);
        border:1px solid rgba(148,163,184,.18);
        color:#e2e8f0;
    }
    .properties-page .pagination .active span {
        background:linear-gradient(180deg,#2563eb,#1d4ed8);
        border-color:#2563eb;
    }
</style>

<div class="admin-page-header properties-page">
    <div>
        <h2>Properties</h2>
        <p>Track occupancy, vacant units, landlords, and expected rent by property.</p>
    </div>
    <div class="admin-actions">
        <a href="{{ route('admin.properties.create') }}" class="btn btn-primary">+ Add Property</a>
    </div>
</div>

<div class="card properties-page">
    <div class="table-scroll">
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>City</th>
                <th>Landlord</th>
                <th>Units</th>
                <th>Occupied</th>
                <th>Vacant</th>
                <th>Expected Monthly Rent</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($properties as $property)
            <tr>
                <td><a href="{{ route('admin.properties.show', $property) }}">{{ $property->name }}</a></td>
                <td>{{ $property->city }}</td>
                <td>{{ $property->landlord?->name ?? '—' }}</td>
                <td>{{ $property->units_count ?? $property->units->count() }}</td>
                <td>{{ $property->units->where('status', 'OCCUPIED')->count() }}</td>
                <td>{{ $property->units->where('status', 'AVAILABLE')->count() }}</td>
                <td>KSh {{ number_format($property->units->sum('rent_amount'), 2) }}</td>
                <td>
                    <a href="{{ route('admin.properties.units.create', $property) }}" class="btn btn-primary" style="margin-right:6px;">+ Add Unit</a>
                    <a href="{{ route('admin.properties.edit', $property) }}" class="btn btn-secondary" style="margin-right:6px;">Edit</a>
                    <form method="POST" action="{{ route('admin.properties.destroy', $property) }}" style="display:inline;">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger" onclick="return confirm('Delete this property?')">Delete</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="8" class="empty-state">No properties yet. Add your first property to begin.</td></tr>
            @endforelse
        </tbody>
    </table>
    </div>
    <div class="pagination">{{ $properties->links() }}</div>
</div>
@endsection
