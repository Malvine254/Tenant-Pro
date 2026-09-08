@extends('admin.layout')
@section('page-title', 'Maintenance')

@section('content')
<style>
    .maintenance-filter-grid {
        display: grid;
        grid-template-columns: minmax(240px, 1.35fr) repeat(2, minmax(180px, 1fr));
        gap: 0 16px;
    }
    .maintenance-filter-actions {
        justify-content: flex-start;
        align-self: end;
        grid-column: 3;
        margin-bottom: 15px;
    }
    @media (max-width: 980px) {
        .maintenance-filter-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .maintenance-filter-actions { grid-column: 1 / -1; }
    }
    @media (max-width: 560px) {
        .maintenance-filter-grid { grid-template-columns: 1fr; }
    }
</style>
<div class="admin-page-header">
    <div>
        <h2>Maintenance requests</h2>
        <p>Track tenant issues, assign responsibility, and keep every repair moving.</p>
    </div>
</div>

<div class="metric-row">
    <div class="metric-card"><span>Open</span><strong>{{ $counts['open'] }}</strong></div>
    <div class="metric-card"><span>In progress</span><strong>{{ $counts['in_progress'] }}</strong></div>
    <div class="metric-card"><span>Resolved</span><strong>{{ $counts['resolved'] }}</strong></div>
    <div class="metric-card"><span>Total requests</span><strong>{{ $counts['total'] }}</strong></div>
</div>

<form method="GET" class="card" style="margin-bottom:16px;">
    <div class="maintenance-filter-grid">
        <div class="field">
            <label for="maintenance-search">Search</label>
            <input id="maintenance-search" type="search" name="search" value="{{ request('search') }}" placeholder="Tenant, unit, title, or email">
        </div>
        <div class="field">
            <label for="maintenance-status">Status</label>
            <select id="maintenance-status" name="status">
                <option value="">All statuses</option>
                @foreach(['OPEN' => 'Open', 'IN_PROGRESS' => 'In progress', 'RESOLVED' => 'Resolved', 'CLOSED' => 'Closed'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="maintenance-priority">Priority</label>
            <select id="maintenance-priority" name="priority">
                <option value="">All priorities</option>
                @foreach(['LOW', 'MEDIUM', 'HIGH', 'URGENT'] as $priority)
                    <option value="{{ $priority }}" @selected(request('priority') === $priority)>{{ ucfirst(strtolower($priority)) }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="maintenance-property">Property</label>
            <select id="maintenance-property" name="property_id">
                <option value="">All properties</option>
                @foreach($properties as $property)
                    <option value="{{ $property->id }}" @selected(request('property_id') === $property->id)>{{ $property->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="maintenance-assignee">Assigned caretaker</label>
            <select id="maintenance-assignee" name="assigned_to_id">
                <option value="">Anyone</option>
                <option value="unassigned" @selected(request('assigned_to_id') === 'unassigned')>Unassigned</option>
                @foreach($caretakers as $caretaker)
                    <option value="{{ $caretaker->id }}" @selected(request('assigned_to_id') === $caretaker->id)>{{ $caretaker->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="admin-actions maintenance-filter-actions">
            <button class="btn btn-primary" type="submit">Apply filters</button>
            <a class="btn btn-secondary" href="{{ route('admin.maintenance.index') }}">Clear</a>
        </div>
    </div>
</form>

<div class="card">
    <div class="table-scroll">
        <table>
            <thead>
                <tr>
                    <th>Request</th>
                    <th>Tenant / unit</th>
                    <th>Property</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Assigned to</th>
                    <th>Reported</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $maintenance)
                    @php
                        $priorityClass = match($maintenance->priority) {
                            'URGENT', 'HIGH' => 'badge-red',
                            'MEDIUM' => 'badge-yellow',
                            default => 'badge-blue',
                        };
                        $statusClass = match($maintenance->status) {
                            'RESOLVED', 'CLOSED' => 'badge-green',
                            'IN_PROGRESS' => 'badge-blue',
                            default => 'badge-yellow',
                        };
                    @endphp
                    <tr>
                        <td>
                            <strong>{{ $maintenance->title }}</strong>
                            <div class="muted" style="font-size:12px;margin-top:3px;">#{{ substr($maintenance->id, 0, 8) }}</div>
                        </td>
                        <td>
                            <div>{{ $maintenance->tenant?->name ?? 'Unknown tenant' }}</div>
                            <div class="muted" style="font-size:12px;margin-top:3px;">{{ $maintenance->unit?->unit_number ?? 'Unknown unit' }}</div>
                        </td>
                        <td>{{ $maintenance->unit?->property?->name ?? '—' }}</td>
                        <td><span class="badge {{ $priorityClass }}">{{ ucfirst(strtolower($maintenance->priority)) }}</span></td>
                        <td><span class="badge {{ $statusClass }}">{{ str_replace('_', ' ', ucfirst(strtolower($maintenance->status))) }}</span></td>
                        <td>{{ $maintenance->assignedTo?->name ?? 'Unassigned' }}</td>
                        <td>{{ $maintenance->created_at?->format('d M Y') ?? '—' }}</td>
                        <td><a class="btn btn-secondary" href="{{ route('admin.maintenance.show', $maintenance) }}">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="empty-state">No maintenance requests match these filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination">{{ $requests->links() }}</div>
</div>
@endsection
