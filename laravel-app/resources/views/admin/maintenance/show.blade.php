@extends('admin.layout')
@section('page-title', 'Maintenance request')

@section('content')
<div class="admin-page-header">
    <div>
        <p style="margin-bottom:5px;"><a href="{{ route('admin.maintenance.index') }}">Maintenance requests</a> / Request details</p>
        <h2>{{ $maintenanceRequest->title }}</h2>
        <p>{{ $maintenanceRequest->unit?->property?->name ?? 'Unknown property' }} · Unit {{ $maintenanceRequest->unit?->unit_number ?? '—' }}</p>
    </div>
    <div class="admin-actions">
        @php
            $statusClass = in_array($maintenanceRequest->status, ['RESOLVED', 'CLOSED'], true) ? 'badge-green' : ($maintenanceRequest->status === 'IN_PROGRESS' ? 'badge-blue' : 'badge-yellow');
            $priorityClass = in_array($maintenanceRequest->priority, ['URGENT', 'HIGH'], true) ? 'badge-red' : ($maintenanceRequest->priority === 'MEDIUM' ? 'badge-yellow' : 'badge-blue');
        @endphp
        <span class="badge {{ $priorityClass }}">{{ ucfirst(strtolower($maintenanceRequest->priority)) }} priority</span>
        <span class="badge {{ $statusClass }}">{{ str_replace('_', ' ', ucfirst(strtolower($maintenanceRequest->status))) }}</span>
    </div>
</div>

@if(session('success'))<div class="alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert-error">{{ $errors->first() }}</div>@endif

<div style="display:grid;grid-template-columns:minmax(0,1.4fr) minmax(280px,.8fr);gap:16px;">
    <div class="card">
        <div class="section-heading">Request details</div>
        <h3 style="font-size:18px;margin-bottom:12px;">{{ $maintenanceRequest->title }}</h3>
        <p style="white-space:pre-wrap;color:#cbd5e1;line-height:1.7;">{{ $maintenanceRequest->description }}</p>
        <div class="metric-row" style="margin-top:20px;margin-bottom:0;">
            <div class="metric-card"><span>Reported by</span><strong style="font-size:15px;">{{ $maintenanceRequest->reportedBy?->name ?? 'Tenant' }}</strong></div>
            <div class="metric-card"><span>Submitted</span><strong style="font-size:15px;">{{ $maintenanceRequest->created_at?->format('d M Y, H:i') ?? '—' }}</strong></div>
            <div class="metric-card"><span>Last updated</span><strong style="font-size:15px;">{{ $maintenanceRequest->updated_at?->format('d M Y, H:i') ?? '—' }}</strong></div>
        </div>
    </div>

    <div class="card">
        <div class="section-heading">Assignment</div>
        <p class="muted" style="margin-bottom:12px;">Choose the caretaker responsible for this request.</p>
        <form method="POST" action="{{ route('admin.maintenance.assign', $maintenanceRequest) }}">
            @csrf @method('PATCH')
            <div class="field">
                <label for="assigned-to">Caretaker</label>
                <select id="assigned-to" name="assigned_to_id">
                    <option value="">Unassigned</option>
                    @foreach($caretakers as $caretaker)
                        <option value="{{ $caretaker->id }}" @selected($maintenanceRequest->assigned_to_id === $caretaker->id)>{{ $caretaker->name }}</option>
                    @endforeach
                </select>
            </div>
            <button class="btn btn-primary" type="submit">Save assignment</button>
        </form>

        <div class="section-heading" style="margin-top:24px;">Update status</div>
        @if($nextStatuses)
            <form method="POST" action="{{ route('admin.maintenance.status', $maintenanceRequest) }}">
                @csrf @method('PATCH')
                <div class="field">
                    <label for="request-status">Next status</label>
                    <select id="request-status" name="status">
                        @foreach($nextStatuses as $status)
                            <option value="{{ $status }}">{{ str_replace('_', ' ', ucfirst(strtolower($status))) }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="btn btn-primary" type="submit">Update status</button>
            </form>
        @else
            <p class="muted">This request is closed and has no further status changes.</p>
        @endif
    </div>
</div>
@endsection
