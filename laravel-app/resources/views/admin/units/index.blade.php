@extends('admin.layout')
@section('page-title', 'Units')

@section('content')
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
    <div>
        <h2 style="font-size:18px;font-weight:600;">Your Units</h2>
        <p style="font-size:13px;color:#64748b;margin-top:4px;">Select a floor to expand and manage its units.</p>
    </div>
    <a href="{{ route('admin.properties.create') }}" class="btn btn-secondary">+ Add Property</a>
</div>

@forelse($properties as $property)
    <div class="card" style="margin-bottom:16px;">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:14px;">
            <div>
                <a href="{{ route('admin.properties.show', $property) }}" style="font-size:16px;font-weight:600;color:#1d4ed8;text-decoration:none;">
                    {{ $property->name }}
                </a>
                <div style="font-size:12px;color:#64748b;margin-top:3px;">
                    {{ $property->city }} · {{ $property->units->count() }} {{ Str::plural('unit', $property->units->count()) }}
                </div>
            </div>
            <a href="{{ route('admin.properties.units.create', $property) }}" class="btn btn-primary">+ Add Unit</a>
        </div>

        @forelse($property->units->groupBy(fn($unit) => $unit->floor === null ? 'Unassigned floor' : 'Floor '.$unit->floor) as $floorLabel => $floorUnits)
            <details style="border:1px solid #e2e8f0;border-radius:8px;margin-bottom:10px;overflow:hidden;">
                <summary style="cursor:pointer;padding:13px 15px;background:#f8fafc;font-weight:600;display:flex;justify-content:space-between;align-items:center;">
                    <span>{{ $floorLabel }}</span>
                    <span style="font-size:12px;color:#64748b;font-weight:normal;">
                        {{ $floorUnits->count() }} {{ Str::plural('unit', $floorUnits->count()) }}
                        · {{ $floorUnits->where('status', 'AVAILABLE')->count() }} available
                    </span>
                </summary>
                <div style="overflow-x:auto;">
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
