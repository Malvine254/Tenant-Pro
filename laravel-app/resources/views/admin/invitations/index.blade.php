@extends('admin.layout')
@section('page-title', 'Invitations')

@section('content')
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
    <div>
        <h2 style="font-size:18px;font-weight:600;">My Invitations</h2>
        <p style="font-size:13px;color:#64748b;margin-top:4px;">
            Landlords only see invitations they personally sent.
        </p>
    </div>
    <form method="GET">
        <select name="status" onchange="this.form.submit()" style="padding:7px 12px;border:1px solid #cbd5e1;border-radius:6px;">
            <option value="">All statuses</option>
            @foreach(['PENDING', 'ACCEPTED', 'EXPIRED', 'REVOKED'] as $status)
                <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ $status }}</option>
            @endforeach
        </select>
    </form>
</div>

<div class="card">
    <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr><th>Invitee</th><th>Property</th><th>Unit</th><th>Code</th><th>Status</th><th>Sent</th><th>Expires</th></tr>
            </thead>
            <tbody>
                @forelse($invitations as $invitation)
                    <tr>
                        <td>{{ $invitation->phone_number }}</td>
                        <td>{{ $invitation->property?->name ?? '—' }}</td>
                        <td>{{ $invitation->unit?->unit_number ?? '—' }}</td>
                        <td><strong>{{ $invitation->code }}</strong></td>
                        <td>
                            <span class="badge {{ $invitation->status === 'ACCEPTED' ? 'badge-green' : ($invitation->status === 'PENDING' ? 'badge-yellow' : 'badge-gray') }}">
                                {{ $invitation->status }}
                            </span>
                        </td>
                        <td>{{ $invitation->created_at?->format('d M Y') }}</td>
                        <td>{{ $invitation->expires_at?->format('d M Y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" style="text-align:center;color:#64748b;padding:24px;">You have not sent any invitations yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination">{{ $invitations->links() }}</div>
</div>
@endsection
