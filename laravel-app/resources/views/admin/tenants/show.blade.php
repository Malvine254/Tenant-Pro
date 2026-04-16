@extends('admin.layout')
@section('page-title', $tenant->user->name)

@section('content')
<div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;">
    <a href="{{ route('admin.tenants.index') }}" style="color:#94a3b8;text-decoration:none;font-size:13px;">Tenants</a>
    <span style="color:#cbd5e1;">/</span>
    <span style="font-weight:600;">{{ $tenant->user->name }}</span>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;">
    <div class="card">
        <h3 style="font-size:13px;color:#94a3b8;margin-bottom:10px;text-transform:uppercase;">Tenant Info</h3>
        <p style="font-size:14px;margin-bottom:5px;"><strong>Email:</strong> {{ $tenant->user->email }}</p>
        <p style="font-size:14px;margin-bottom:5px;"><strong>Phone:</strong> {{ $tenant->user->phone_number ?? '—' }}</p>
        <p style="font-size:14px;margin-bottom:5px;"><strong>Status:</strong>
            <span class="badge {{ $tenant->is_active ? 'badge-green' : 'badge-gray' }}">{{ $tenant->is_active ? 'Active' : 'Inactive' }}</span>
        </p>
        <p style="font-size:14px;margin-bottom:5px;"><strong>Move-in:</strong> {{ $tenant->move_in_date?->format('d M Y') }}</p>
        @if($tenant->move_out_date)
        <p style="font-size:14px;margin-bottom:5px;"><strong>Move-out:</strong> {{ $tenant->move_out_date->format('d M Y') }}</p>
        @endif
    </div>
    <div class="card">
        <h3 style="font-size:13px;color:#94a3b8;margin-bottom:10px;text-transform:uppercase;">Unit Info</h3>
        <p style="font-size:14px;margin-bottom:5px;"><strong>Unit:</strong> {{ $tenant->unit->unit_number }}</p>
        <p style="font-size:14px;margin-bottom:5px;"><strong>Property:</strong> {{ $tenant->unit->property->name ?? '—' }}</p>
        <p style="font-size:14px;margin-bottom:5px;"><strong>Rent:</strong> KES {{ number_format($tenant->unit->rent_amount, 2) }}</p>
    </div>
</div>

<div class="card" style="margin-bottom:16px;">
    <h3 style="font-size:13px;color:#94a3b8;margin-bottom:12px;text-transform:uppercase;">Recent Invoices</h3>
    <table>
        <thead><tr><th>Period</th><th>Amount</th><th>Status</th><th>Due</th></tr></thead>
        <tbody>
            @forelse($tenant->unit->invoices->take(5) as $invoice)
            <tr>
                <td>{{ date('M Y', mktime(0,0,0,$invoice->period_month,1,$invoice->period_year)) }}</td>
                <td>KES {{ number_format($invoice->total_amount, 2) }}</td>
                <td>
                    @php $ic = ['PAID'=>'badge-green','PENDING'=>'badge-yellow','OVERDUE'=>'badge-red','PARTIAL'=>'badge-blue','CANCELLED'=>'badge-gray']; @endphp
                    <span class="badge {{ $ic[$invoice->status] ?? 'badge-gray' }}">{{ $invoice->status }}</span>
                </td>
                <td style="font-size:13px;">{{ $invoice->due_date?->format('d M Y') }}</td>
            </tr>
            @empty
            <tr><td colspan="4" style="color:#94a3b8;">No invoices yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="card">
    <h3 style="font-size:13px;color:#94a3b8;margin-bottom:12px;text-transform:uppercase;">Maintenance Requests</h3>
    <table>
        <thead><tr><th>Title</th><th>Priority</th><th>Status</th><th>Date</th></tr></thead>
        <tbody>
            @forelse($tenant->unit->maintenanceRequests->take(5) as $req)
            <tr>
                <td>{{ $req->title }}</td>
                <td>
                    @php $pc = ['LOW'=>'badge-green','MEDIUM'=>'badge-blue','HIGH'=>'badge-yellow','URGENT'=>'badge-red']; @endphp
                    <span class="badge {{ $pc[$req->priority] ?? 'badge-gray' }}">{{ $req->priority }}</span>
                </td>
                <td><span class="badge badge-gray">{{ $req->status }}</span></td>
                <td style="font-size:13px;">{{ $req->created_at->format('d M Y') }}</td>
            </tr>
            @empty
            <tr><td colspan="4" style="color:#94a3b8;">No maintenance requests.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
