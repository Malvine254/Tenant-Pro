@extends('admin.layout')
@section('page-title', $property->name)

@section('content')
<div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;">
    <a href="{{ route('admin.properties.index') }}" style="color:#94a3b8;text-decoration:none;font-size:13px;">Properties</a>
    <span style="color:#cbd5e1;">/</span>
    <span style="font-weight:600;">{{ $property->name }}</span>
    <a href="{{ route('admin.properties.edit', $property) }}" class="btn btn-secondary" style="margin-left:auto;">Edit</a>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;">
    <div class="card">
        <h3 style="font-size:13px;color:#94a3b8;margin-bottom:10px;text-transform:uppercase;">Property Info</h3>
        <p style="font-size:14px;margin-bottom:6px;"><strong>Address:</strong> {{ $property->address_line }}, {{ $property->city }}{{ $property->state ? ', '.$property->state : '' }}</p>
        <p style="font-size:14px;margin-bottom:6px;"><strong>Country:</strong> {{ $property->country }}</p>
        <p style="font-size:14px;margin-bottom:6px;"><strong>Landlord:</strong> {{ $property->landlord?->name ?? '-' }}</p>
        @if($property->description)
        <p style="font-size:14px;margin-top:10px;color:#64748b;">{{ $property->description }}</p>
        @endif
    </div>
    <div class="card">
        <h3 style="font-size:13px;color:#94a3b8;margin-bottom:10px;text-transform:uppercase;">Unit Summary</h3>
        <p style="font-size:24px;font-weight:700;margin-bottom:4px;">{{ $property->units->count() }} <span style="font-size:14px;font-weight:normal;color:#94a3b8;">units</span></p>
        <p style="font-size:14px;color:#16a34a;">{{ $property->units->where('status','OCCUPIED')->count() }} occupied</p>
        <p style="font-size:14px;color:#1d4ed8;">{{ $property->units->where('status','AVAILABLE')->count() }} available</p>
    </div>
</div>

<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
        <h3 style="font-size:13px;color:#94a3b8;text-transform:uppercase;">Units</h3>
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
                        <tr><th>Unit</th><th>Rent (KES)</th><th>Status</th><th>Tenant</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        @foreach($floorUnits as $unit)
                        <tr>
                            <td><strong>{{ $unit->unit_number }}</strong></td>
                            <td>{{ number_format($unit->rent_amount, 2) }}</td>
                            <td>
                                @php $statusColors = ['AVAILABLE'=>'badge-green','OCCUPIED'=>'badge-blue','UNDER_MAINTENANCE'=>'badge-yellow']; @endphp
                                <span class="badge {{ $statusColors[$unit->status] ?? 'badge-gray' }}">{{ str_replace('_', ' ', $unit->status) }}</span>
                            </td>
                            <td>{{ $unit->tenant?->user?->name ?? 'Unassigned' }}</td>
                            <td>
                                <a href="{{ route('admin.properties.units.edit', [$property, $unit]) }}" class="btn btn-secondary" style="margin-right:6px;">Edit</a>
                                <form method="POST" action="{{ route('admin.properties.units.destroy', [$property, $unit]) }}" style="display:inline;">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-danger" onclick="return confirm('Delete this unit?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </details>
    @empty
        <div style="color:#94a3b8;text-align:center;padding:20px;">No units yet.</div>
    @endforelse
</div>
@endsection
