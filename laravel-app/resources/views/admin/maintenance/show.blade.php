@extends('admin.layout')
@section('page-title', 'Maintenance Request')

@section('content')
<div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;">
    <a href="{{ route('admin.maintenance.index') }}" style="color:#94a3b8;text-decoration:none;font-size:13px;">Maintenance</a>
    <span style="color:#cbd5e1;">/</span>
    <span style="font-weight:600;">{{ $maintenanceRequest->title }}</span>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
    <div class="card">
        <h3 style="font-size:13px;color:#94a3b8;margin-bottom:10px;text-transform:uppercase;">Request Details</h3>
        <p style="font-size:14px;margin-bottom:4px;"><strong>Title:</strong> {{ $maintenanceRequest->title }}</p>
        <p style="font-size:14px;margin-bottom:4px;"><strong>Description:</strong><br>{{ $maintenanceRequest->description }}</p>
        <p style="font-size:14px;margin-bottom:4px;"><strong>Property:</strong> {{ $maintenanceRequest->unit->property->name ?? '—' }}</p>
        <p style="font-size:14px;margin-bottom:4px;"><strong>Unit:</strong> {{ $maintenanceRequest->unit->unit_number ?? '—' }}</p>
        <p style="font-size:14px;margin-bottom:4px;"><strong>Reported By:</strong> {{ $maintenanceRequest->reportedBy?->name ?? '—' }}</p>
        <p style="font-size:14px;margin-bottom:4px;"><strong>Created:</strong> {{ $maintenanceRequest->created_at->format('d M Y H:i') }}</p>
    </div>

    <div class="card">
        <h3 style="font-size:13px;color:#94a3b8;margin-bottom:10px;text-transform:uppercase;">Update Status</h3>
        <form method="POST" action="{{ route('admin.maintenance.update', $maintenanceRequest) }}">
            @csrf @method('PATCH')
            <div class="form-group">
                <label>Priority</label>
                <input type="text" value="{{ $maintenanceRequest->priority }}" disabled>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status" required>
                    @foreach(['OPEN','IN_PROGRESS','RESOLVED','CLOSED'] as $status)
                        <option value="{{ $status }}" {{ $maintenanceRequest->status === $status ? 'selected' : '' }}>{{ $status }}</option>
                    @endforeach
                </select>
                @error('status')<div class="form-error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label>Assign To (Staff User ID)</label>
                <input type="text" name="assigned_to_id" value="{{ old('assigned_to_id', $maintenanceRequest->assigned_to_id) }}" placeholder="UUID (optional)">
                @error('assigned_to_id')<div class="form-error">{{ $message }}</div>@enderror
            </div>
            <button type="submit" class="btn btn-primary">Update Request</button>
        </form>
        @if($maintenanceRequest->resolved_at)
            <p style="margin-top:12px;font-size:13px;color:#16a34a;">Resolved at: {{ $maintenanceRequest->resolved_at->format('d M Y H:i') }}</p>
        @endif
    </div>
</div>
@endsection
