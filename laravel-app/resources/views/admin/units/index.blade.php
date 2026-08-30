@extends('admin.layout')
@section('page-title', 'Units')

@section('content')
<style>
    .units-page {
        color: #e2e8f0;
    }
    .units-page .admin-page-header h2 {
        color: #f8fafc;
    }
    .units-page .admin-page-header p {
        color: #94a3b8;
    }
    .units-page .property-shell {
        background: linear-gradient(180deg, #111827, #0b1220);
        border: 1px solid rgba(148,163,184,.2);
        border-radius: 18px;
        box-shadow: 0 18px 36px rgba(2,6,23,.32);
        padding: 18px;
        margin-bottom: 18px;
    }
    .units-page .property-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding-bottom: 16px;
        margin-bottom: 18px;
        border-bottom: 1px solid rgba(148,163,184,.12);
    }
    .units-page .property-title {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    .units-page .property-title a {
        color: #dbeafe;
        font-size: 16px;
        font-weight: 700;
        text-decoration: none;
    }
    .units-page .property-meta {
        color: #94a3b8;
        font-size: 12px;
    }
    .units-page .summary-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        gap: 12px;
        margin-bottom: 18px;
    }
    .units-page .summary-card {
        background: rgba(15,23,42,.72);
        border: 1px solid rgba(148,163,184,.18);
        border-radius: 12px;
        padding: 12px 14px;
    }
    .units-page .summary-card span {
        display: block;
        color: #94a3b8;
        font-size: 11px;
        letter-spacing: .08em;
        text-transform: uppercase;
        margin-bottom: 6px;
    }
    .units-page .summary-card strong {
        display: block;
        font-size: 22px;
        letter-spacing: -.04em;
        color: #f8fafc;
    }
    .units-page details {
        background: rgba(15,23,42,.72);
        border: 1px solid rgba(148,163,184,.18);
        border-radius: 14px;
        overflow: hidden;
        margin-bottom: 12px;
    }
    .units-page summary {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        cursor: pointer;
        list-style: none;
        padding: 14px 16px;
        background: rgba(15,23,42,.9);
        color: #f8fafc;
        font-weight: 700;
    }
    .units-page summary::-webkit-details-marker { display: none; }
    .units-page .floor-meta {
        color: #94a3b8;
        font-size: 12px;
        font-weight: 500;
    }
    .units-page .table-scroll {
        overflow-x: auto;
    }
    .units-page table {
        width: 100%;
        border-collapse: collapse;
        background: transparent;
    }
    .units-page th {
        text-align: left;
        padding: 11px 13px;
        font-size: 11px;
        color: #cbd5e1;
        text-transform: uppercase;
        letter-spacing: .08em;
        background: rgba(15,23,42,.95);
        border-bottom: 1px solid rgba(148,163,184,.18);
    }
    .units-page td {
        padding: 12px 13px;
        border-bottom: 1px solid rgba(148,163,184,.12);
        color: #e2e8f0;
        vertical-align: middle;
    }
    .units-page tbody tr:hover {
        background: rgba(15,23,42,.72);
    }
    .units-page .unit-number {
        font-weight: 700;
        color: #f8fafc;
    }
    .units-page .btn {
        border: 1px solid rgba(148,163,184,.18);
        box-shadow: none;
    }
    .units-page .btn-primary {
        background: linear-gradient(180deg, #2563eb, #1d4ed8);
        color: #eff6ff;
        box-shadow: 0 8px 16px rgba(37,99,235,.22);
    }
    .units-page .btn-secondary {
        background: rgba(148,163,184,.1);
        color: #e2e8f0;
    }
    .units-page .status-pill {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: 5px 9px;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: .02em;
    }
    .units-page .status-available {
        background: rgba(34,197,94,.12);
        color: #86efac;
    }
    .units-page .status-occupied {
        background: rgba(59,130,246,.12);
        color: #93c5fd;
    }
    .units-page .status-maintenance {
        background: rgba(250,204,21,.12);
        color: #fcd34d;
    }
    .units-page .status-unknown {
        background: rgba(148,163,184,.12);
        color: #cbd5e1;
    }
    .units-page .tenant-name {
        color: #e2e8f0;
        font-weight: 600;
    }
    .units-page .empty-box {
        text-align: center;
        color: #94a3b8;
        padding: 24px 16px;
        background: rgba(15,23,42,.48);
        border: 1px dashed rgba(148,163,184,.18);
        border-radius: 12px;
    }
    .units-page .empty-card {
        background: linear-gradient(180deg, #111827, #0b1220);
        border: 1px solid rgba(148,163,184,.2);
        border-radius: 18px;
        padding: 36px 24px;
        text-align: center;
    }
    .units-page .empty-card h3 {
        font-size: 18px;
        margin-bottom: 8px;
        color: #f8fafc;
    }
    .units-page .empty-card p {
        color: #94a3b8;
        margin-bottom: 18px;
    }
    @media (max-width: 720px) {
        .units-page .property-header {
            flex-direction: column;
            align-items: flex-start;
        }
        .units-page .property-shell {
            padding: 14px;
        }
    }
</style>

<div class="admin-page-header units-page">
    <div>
        <h2>Your Units</h2>
        <p>Manage property floors, occupancy, and unit assignments in a cleaner nested view.</p>
    </div>
    <div class="admin-actions">
        <a href="{{ route('admin.properties.create') }}" class="btn btn-secondary">+ Add Property</a>
    </div>
</div>

@forelse($properties as $property)
    @php
        $totalUnits = $property->units->count();
        $availableUnits = $property->units->where('status', 'AVAILABLE')->count();
        $occupiedUnits = $property->units->where('status', 'OCCUPIED')->count();
        $maintenanceUnits = $property->units->where('status', 'UNDER_MAINTENANCE')->count();
    @endphp

    <div class="property-shell units-page">
        <div class="property-header">
            <div class="property-title">
                <a href="{{ route('admin.properties.show', $property) }}">{{ $property->name }}</a>
                <div class="property-meta">{{ $property->city }} · {{ $totalUnits }} {{ Str::plural('unit', $totalUnits) }}</div>
            </div>
            <div class="admin-actions">
                <a href="{{ route('admin.properties.units.create', $property) }}" class="btn btn-primary">+ Add Unit</a>
            </div>
        </div>

        <div class="summary-grid">
            <div class="summary-card">
                <span>Total</span>
                <strong>{{ $totalUnits }}</strong>
            </div>
            <div class="summary-card">
                <span>Available</span>
                <strong>{{ $availableUnits }}</strong>
            </div>
            <div class="summary-card">
                <span>Occupied</span>
                <strong>{{ $occupiedUnits }}</strong>
            </div>
            <div class="summary-card">
                <span>Maintenance</span>
                <strong>{{ $maintenanceUnits }}</strong>
            </div>
        </div>

        @forelse($property->units->groupBy(fn($unit) => $unit->floor === null ? 'Unassigned floor' : 'Floor '.$unit->floor) as $floorLabel => $floorUnits)
            <details>
                <summary>
                    <span>{{ $floorLabel }}</span>
                    <span class="floor-meta">
                        {{ $floorUnits->count() }} {{ Str::plural('unit', $floorUnits->count()) }}
                        · {{ $floorUnits->where('status', 'AVAILABLE')->count() }} available · Expand
                    </span>
                </summary>
                <div class="table-scroll">
                    <table>
                        <thead>
                            <tr>
                                <th>Unit</th>
                                <th>Rent</th>
                                <th>Status</th>
                                <th>Tenant</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($floorUnits as $unit)
                                @php
                                    $statusClass = match ($unit->status) {
                                        'AVAILABLE' => 'status-available',
                                        'OCCUPIED' => 'status-occupied',
                                        'UNDER_MAINTENANCE' => 'status-maintenance',
                                        default => 'status-unknown',
                                    };
                                @endphp
                                <tr>
                                    <td><span class="unit-number">{{ $unit->unit_number }}</span></td>
                                    <td>{{ $unit->rent_amount_formatted }}</td>
                                    <td>
                                        <span class="status-pill {{ $statusClass }}">
                                            {{ str_replace('_', ' ', $unit->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="tenant-name">{{ $unit->tenant?->user?->name ?? 'Unassigned' }}</span>
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.properties.units.edit', [$property, $unit]) }}" class="btn btn-secondary" style="margin-right:6px;">Edit</a>
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
            <div class="empty-box">No units yet. Use “Add Unit” to create the first one.</div>
        @endforelse
    </div>
@empty
    <div class="empty-card units-page">
        <h3>Create a property first</h3>
        <p>Units must belong to one of your properties before they can be managed here.</p>
        <a href="{{ route('admin.properties.create') }}" class="btn btn-primary">+ Add Property</a>
    </div>
@endforelse
@endsection
