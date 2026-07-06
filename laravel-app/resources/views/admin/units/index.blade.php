@extends('admin.layout')
@section('page-title', 'Units')

@section('content')
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
    <div>
        <h2 style="font-size:18px;font-weight:600;">Your Units</h2>
        <p style="font-size:13px;color:#64748b;margin-top:4px;">Add and manage units under each property you own.</p>
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

        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr><th>Unit</th><th>Floor</th><th>Monthly Rent</th><th>Status</th><th>Tenant</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    @forelse($property->units as $unit)
                        <tr>
                            <td><strong>{{ $unit->unit_number }}</strong></td>
                            <td>{{ $unit->floor ?? '—' }}</td>
                            <td>{{ $unit->rent_amount_formatted }}</td>
                            <td>
                                <span class="badge {{ $unit->status === 'AVAILABLE' ? 'badge-green' : ($unit->status === 'OCCUPIED' ? 'badge-blue' : 'badge-yellow') }}">
                                    {{ str_replace('_', ' ', $unit->status) }}
                                </span>
                            </td>
                            <td>{{ $unit->tenant?->user?->name ?? 'Unassigned' }}</td>
                            <td>
                                <a href="{{ route('admin.properties.units.edit', [$property, $unit]) }}" class="btn btn-secondary">Edit</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="color:#64748b;text-align:center;padding:20px;">
                                No units yet. Use “Add Unit” to create the first one.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@empty
    <div class="card" style="text-align:center;padding:36px;">
        <h3 style="font-size:16px;margin-bottom:8px;">Create a property first</h3>
        <p style="font-size:13px;color:#64748b;margin-bottom:16px;">Units must belong to one of your properties.</p>
        <a href="{{ route('admin.properties.create') }}" class="btn btn-primary">+ Add Property</a>
    </div>
@endforelse
@endsection
