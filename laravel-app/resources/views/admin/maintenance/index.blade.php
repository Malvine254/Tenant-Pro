@extends('admin.layout')
@section('page-title', 'Maintenance')

@section('content')
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
    <h2 style="font-size:16px;font-weight:600;">Maintenance Requests</h2>
    <form method="GET" style="display:flex;gap:8px;">
        <select name="status" style="padding:7px 12px;border:1px solid #cbd5e1;border-radius:6px;font-size:13px;">
            <option value="">All Statuses</option>
            @foreach(['OPEN','IN_PROGRESS','RESOLVED','CLOSED'] as $status)
                <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ $status }}</option>
            @endforeach
        </select>
        <select name="priority" style="padding:7px 12px;border:1px solid #cbd5e1;border-radius:6px;font-size:13px;">
            <option value="">All Priorities</option>
            @foreach(['LOW','MEDIUM','HIGH','URGENT'] as $priority)
                <option value="{{ $priority }}" {{ request('priority') === $priority ? 'selected' : '' }}>{{ $priority }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-secondary">Filter</button>
    </form>
</div>

<div class="card">
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
            <tr><td colspan="7" style="color:#94a3b8;text-align:center;padding:24px;">No maintenance requests found.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="pagination">{{ $requests->links() }}</div>
</div>
@endsection
