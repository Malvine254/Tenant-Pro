@extends('admin.layout')

@section('content')
<div class="admin-page-header">
    <div>
        <h2>Move-In &amp; Move-Out Condition Reports</h2>
        <p>Digital inspection records, room-by-room condition checks, and handover meter readings to prevent end-of-tenancy deposit disputes.</p>
    </div>
    <div class="admin-actions">
        <a href="{{ route('admin.inspections.create') }}" class="btn btn-primary">
            + New Inspection
        </a>
    </div>
</div>

<div class="card">
    @if($inspections->isEmpty())
        <div class="empty-state">
            <strong>No inspection reports found</strong>
            <span>Conduct your first digital move-in or move-out checklist to protect security deposits and verify unit conditions.</span>
            <div style="margin-top: 14px;">
                <a href="{{ route('admin.inspections.create') }}" class="btn btn-primary">+ Conduct Inspection</a>
            </div>
        </div>
    @else
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Property &amp; Unit</th>
                        <th>Tenant</th>
                        <th>Inspector</th>
                        <th>Status</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($inspections as $ins)
                    <tr>
                        <td style="font-weight: 700; white-space: nowrap;">{{ $ins->inspection_date?->format('d M Y') }}</td>
                        <td>
                            <span class="badge {{ $ins->type === 'MOVE_IN' ? 'badge-green' : ($ins->type === 'MOVE_OUT' ? 'badge-yellow' : 'badge-blue') }}">
                                {{ $ins->type_label }}
                            </span>
                        </td>
                        <td>
                            <strong>{{ $ins->property?->name ?? '-' }}</strong>
                            <div style="font-size: 11px; color: var(--muted);">Unit {{ $ins->unit?->unit_number ?? '-' }}</div>
                        </td>
                        <td>{{ $ins->tenant?->name ?? 'Unassigned' }}</td>
                        <td class="muted" style="font-size: 12px;">{{ $ins->inspector_name }}</td>
                        <td><span class="badge badge-green">{{ $ins->status_label }}</span></td>
                        <td style="text-align: right; white-space: nowrap;">
                            <a href="{{ route('admin.inspections.show', $ins) }}" class="btn btn-secondary" style="min-height: 30px; padding: 4px 9px; font-size: 12px;">View</a>
                            <a href="{{ route('admin.inspections.pdf', $ins) }}" target="_blank" class="btn btn-primary" style="min-height: 30px; padding: 4px 9px; font-size: 12px;">PDF Certificate</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="pagination">{{ $inspections->links() }}</div>
    @endif
</div>
@endsection
