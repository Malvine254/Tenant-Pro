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
        <p style="font-size:14px;margin-bottom:6px;"><strong>Landlord:</strong> {{ $property->landlord?->name ?? '—' }}</p>
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
    <h3 style="font-size:13px;color:#94a3b8;margin-bottom:12px;text-transform:uppercase;">Units</h3>
    <table>
        <thead>
            <tr><th>Unit</th><th>Floor</th><th>Rent (KES)</th><th>Status</th><th>Tenant</th></tr>
        </thead>
        <tbody>
            @forelse($property->units as $unit)
            <tr>
                <td>{{ $unit->unit_number }}</td>
                <td>{{ $unit->floor ?? '—' }}</td>
                <td>{{ number_format($unit->rent_amount, 2) }}</td>
                <td>
                    @php $statusColors = ['AVAILABLE'=>'badge-green','OCCUPIED'=>'badge-blue','UNDER_MAINTENANCE'=>'badge-yellow']; @endphp
                    <span class="badge {{ $statusColors[$unit->status] ?? 'badge-gray' }}">{{ $unit->status }}</span>
                </td>
                <td>{{ $unit->tenant?->user?->name ?? '—' }}</td>
            </tr>
            @empty
            <tr><td colspan="5" style="color:#94a3b8;text-align:center;padding:20px;">No units yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
