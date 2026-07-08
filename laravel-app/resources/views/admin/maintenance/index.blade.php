@extends('admin.layout')
@section('page-title', 'Maintenance')

@section('content')
<div class="admin-page-header">
    <div>
        <h2>Maintenance Requests</h2>
        <p>Track tenant-reported repairs by priority, status, apartment, and assigned workflow.</p>
    </div>
    <form method="GET" class="admin-filter">
        <select name="status">
            <option value="">All Statuses</option>
            @foreach(['OPEN','ACKNOWLEDGED','ASSIGNED','IN_PROGRESS','WAITING_TENANT','RESOLVED','CLOSED','CANCELLED'] as $status)
                <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ $status }}</option>
            @endforeach
        </select>
        <select name="priority">
            <option value="">All Priorities</option>
            @foreach(['LOW','MEDIUM','HIGH','URGENT'] as $priority)
                <option value="{{ $priority }}" {{ request('priority') === $priority ? 'selected' : '' }}>{{ $priority }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-secondary">Filter</button>
    </form>
</div>

<div class="card">
    <div class="table-scroll">
    <table>
        <thead>
            <tr>
                <th>Issue</th>
                <th>Property / Unit</th>
                <th>Reported By</th>
                <th>Priority</th>
                <th>Status</th>
                <th>Date</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($requests as $req)
            <tr>
                <td>{{ $req->title }}</td>
                <td style="font-size:13px;">
                    {{ $req->unit->property->name ?? '—' }}<br>
                    <span style="color:#94a3b8;">Unit {{ $req->unit->unit_number ?? '—' }}</span>
                </td>
                <td>{{ $req->reportedBy?->name ?? '—' }}</td>
                <td>
                    @php $pc = ['LOW'=>'badge-green','MEDIUM'=>'badge-blue','HIGH'=>'badge-yellow','URGENT'=>'badge-red']; @endphp
                    <span class="badge {{ $pc[$req->priority] ?? 'badge-gray' }}">{{ $req->priority }}</span>
                </td>
                <td><span class="badge badge-gray">{{ $req->status }}</span></td>
                <td style="font-size:13px;">{{ $req->created_at->format('d M Y') }}</td>
                <td><a href="{{ route('admin.maintenance.show', $req) }}" class="btn btn-secondary">View</a></td>
            </tr>
            @empty
            <tr><td colspan="7" class="empty-state">No maintenance requests yet.</td></tr>
            @endforelse
        </tbody>
    </table>
    </div>
    <div class="pagination">{{ $requests->links() }}</div>
</div>
@endsection
