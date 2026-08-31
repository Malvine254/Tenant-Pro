@extends('admin.layout')
@section('page-title', 'Audit Log')

@section('content')
<div class="admin-page-header">
    <div>
        <h2>Administrative audit log</h2>
        <p>Trace sensitive changes by administrator, outcome, target, and request ID. Request bodies and credentials are never recorded.</p>
    </div>
</div>

<div class="card" style="margin-bottom:16px;">
    <form method="GET" class="admin-filter" role="search">
        <label class="sr-only" for="audit-search">Search audit log</label>
        <input id="audit-search" type="search" name="search" value="{{ request('search') }}" placeholder="Actor, target or request ID">
        <label class="sr-only" for="audit-action">Filter by action</label>
        <select id="audit-action" name="action">
            <option value="">All actions</option>
            @foreach($actions as $action)
                <option value="{{ $action }}" @selected(request('action') === $action)>{{ $action }}</option>
            @endforeach
        </select>
        <label class="sr-only" for="audit-outcome">Filter by outcome</label>
        <select id="audit-outcome" name="outcome">
            <option value="">All outcomes</option>
            <option value="successful" @selected(request('outcome') === 'successful')>Successful</option>
            <option value="failed" @selected(request('outcome') === 'failed')>Failed</option>
        </select>
        <button class="btn btn-primary" type="submit">Apply filters</button>
        @if(request()->hasAny(['search', 'action', 'outcome']))
            <a class="btn btn-secondary" href="{{ route('admin.audit-logs.index') }}">Clear</a>
        @endif
    </form>
</div>

<div class="card">
    @if($logs->isEmpty())
        <div class="empty-state">
            <strong>No audit events found</strong>
            <span>Administrative changes will appear here after the database migration is applied.</span>
        </div>
    @else
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th scope="col">Date and time</th>
                        <th scope="col">Administrator</th>
                        <th scope="col">Action</th>
                        <th scope="col">Target</th>
                        <th scope="col">Outcome</th>
                        <th scope="col">Request ID</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($logs as $log)
                        <tr>
                            <td><strong>{{ $log->created_at?->format('d M Y, H:i:s') }}</strong><br><span class="muted">{{ $log->ip_address ?: 'IP unavailable' }}</span></td>
                            <td>{{ $log->actor?->name ?: 'Deleted account' }}<br><span class="muted">{{ $log->actor_role ?: 'Unknown role' }}</span></td>
                            <td><code>{{ $log->action }}</code><br><span class="muted">{{ $log->method }} {{ $log->path }}</span></td>
                            <td>{{ $log->target_type ?: '—' }}@if($log->target_id)<br><span class="muted">{{ $log->target_id }}</span>@endif</td>
                            <td><span class="badge {{ $log->status_code < 400 ? 'badge-green' : 'badge-red' }}">HTTP {{ $log->status_code }}</span></td>
                            <td><code title="{{ $log->request_id }}">{{ \Illuminate\Support\Str::limit($log->request_id, 16) }}</code></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $logs->links() }}
    @endif
</div>
@endsection
