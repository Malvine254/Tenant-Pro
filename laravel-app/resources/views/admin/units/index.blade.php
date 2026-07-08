@extends('admin.layout')
@section('page-title', 'Units')

@section('content')
<div class="admin-page-header">
    <div>
        <h2>Your Units</h2>
        <p>Select a floor to expand and manage units without creating one long table.</p>
    </div>
    <div class="admin-actions">
        <a href="{{ route('admin.properties.create') }}" class="btn btn-secondary">+ Add Property</a>
    </div>
</div>

@forelse($properties as $property)
    <div class="card" style="margin-bottom:16px;">
        <div class="admin-page-header" style="margin-bottom:14px;">
            <div>
                <a href="{{ route('admin.properties.show', $property) }}" style="font-size:16px;font-weight:600;color:#1d4ed8;text-decoration:none;">
                    {{ $property->name }}
                </a>
                <div style="font-size:12px;color:#64748b;margin-top:3px;">
                    {{ $property->city }} · {{ $property->units->count() }} {{ Str::plural('unit', $property->units->count()) }}
                </div>
            </div>
            <div class="admin-actions">
                <a href="{{ route('admin.properties.units.create', $property) }}" class="btn btn-primary">+ Add Unit</a>
            </div>
        </div>

        @forelse($property->units->groupBy(fn($unit) => $unit->floor === null ? 'Unassigned floor' : 'Floor '.$unit->floor) as $floorLabel => $floorUnits)
            <details>
                <summary>
                    <span>{{ $floorLabel }}</span>
                    <span style="font-size:12px;color:#64748b;font-weight:normal;">
                        {{ $floorUnits->count() }} {{ Str::plural('unit', $floorUnits->count()) }}
                        · {{ $floorUnits->where('status', 'AVAILABLE')->count() }} available
                    </span>
                </summary>
                <div class="table-scroll">
                    <table>
                        <thead>
                            <tr><th>Unit</th><th>Monthly Rent</th><th>Status</th><th>Tenant</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            @foreach($floorUnits as $unit)
                                <tr>
                                    <td><strong>{{ $unit->unit_number }}</strong></td>
                                    <td>{{ $unit->rent_amount_formatted }}</td>
                                    <td>
                                        <span class="badge {{ $unit->status === 'AVAILABLE' ? 'badge-green' : ($unit->status === 'OCCUPIED' ? 'badge-blue' : 'badge-yellow') }}">
                                            {{ str_replace('_', ' ', $unit->status) }}
                                        </span>
                                    </td>
                                    <td>{{ $unit->tenant?->user?->name ?? 'Unassigned' }}</td>
                                    <td>
                                        <a href="{{ route('admin.properties.units.edit', [$property, $unit]) }}" class="btn btn-secondary">Edit</a>
                                        @if($unit->status === 'AVAILABLE')
                                            <a href="{{ route('admin.invitations.index', ['property_id' => $property->id, 'unit_id' => $unit->id]) }}" class="btn btn-primary">Invite Tenant</a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </details>
        @empty
            <div style="color:#64748b;text-align:center;padding:20px;">No units yet. Use “Add Unit” to create the first one.</div>
        @endforelse
    </div>
@empty
    <div class="card" style="text-align:center;padding:36px;">
        <h3 style="font-size:16px;margin-bottom:8px;">Create a property first</h3>
        <p style="font-size:13px;color:#64748b;margin-bottom:16px;">Units must belong to one of your properties.</p>
        <a href="{{ route('admin.properties.create') }}" class="btn btn-primary">+ Add Property</a>
    </div>
@endforelse
@endsection
