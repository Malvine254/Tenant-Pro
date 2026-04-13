@extends('admin.layout')
@section('page-title', 'Invoices')

@section('content')
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
    <h2 style="font-size:16px;font-weight:600;">Invoice Management</h2>
    <form method="GET" style="display:flex;gap:8px;">
        <select name="status" style="padding:7px 12px;border:1px solid #cbd5e1;border-radius:6px;font-size:13px;">
            <option value="">All Statuses</option>
            @foreach(['PENDING','PARTIAL','PAID','OVERDUE','CANCELLED'] as $status)
                <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ $status }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-secondary">Filter</button>
    </form>
</div>
<div class="card">
    <table>
        <thead>
            <tr>
                <th>Invoice</th>
                <th>Tenant</th>
                <th>Property / Unit</th>
                <th>Amount (KES)</th>
                <th>Paid (KES)</th>
                <th>Status</th>
                <th>Due Date</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($invoices as $invoice)
            <tr>
                <td style="font-size:13px;">{{ date('M Y', mktime(0,0,0,$invoice->period_month,1,$invoice->period_year)) }}</td>
                <td>{{ $invoice->tenant?->name ?? '—' }}</td>
                <td style="font-size:13px;">
                    {{ $invoice->unit->property->name ?? '—' }}<br>
                    <span style="color:#94a3b8;">Unit {{ $invoice->unit->unit_number ?? '—' }}</span>
                </td>
                <td>{{ number_format($invoice->total_amount, 2) }}</td>
                <td style="color:#16a34a;">{{ number_format($invoice->paid_amount, 2) }}</td>
                <td>
                    @php $sc = ['PAID'=>'badge-green','PENDING'=>'badge-yellow','OVERDUE'=>'badge-red','PARTIAL'=>'badge-blue','CANCELLED'=>'badge-gray']; @endphp
                    <span class="badge {{ $sc[$invoice->status] ?? 'badge-gray' }}">{{ $invoice->status }}</span>
                </td>
                <td style="font-size:13px;">{{ $invoice->due_date?->format('d M Y') }}</td>
                <td><a href="{{ route('admin.invoices.show', $invoice) }}" class="btn btn-secondary">View</a></td>
            </tr>
            @empty
            <tr><td colspan="8" style="color:#94a3b8;text-align:center;padding:24px;">No invoices found.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="pagination">{{ $invoices->links() }}</div>
</div>
@endsection
