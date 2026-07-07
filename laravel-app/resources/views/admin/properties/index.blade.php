@extends('admin.layout')
@section('page-title', 'Properties')

@section('content')
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
    <div>
        <h2 style="font-size:16px;font-weight:600;">Properties</h2>
        <p style="font-size:13px;color:#64748b;margin-top:4px;">Track occupancy and expected rent by property.</p>
    </div>
    <a href="{{ route('admin.properties.create') }}" class="btn btn-primary">+ Add Property</a>
</div>

<div class="card">
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
                <td><a href="{{ route('admin.properties.show', $property) }}" style="color:#1d4ed8;text-decoration:none;">{{ $property->name }}</a></td>
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
            <tr><td colspan="8" style="color:#94a3b8;text-align:center;padding:24px;">No properties yet. Add your first property to begin.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="pagination">{{ $properties->links() }}</div>
</div>
@endsection
