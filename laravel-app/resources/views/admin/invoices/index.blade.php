@extends('admin.layout')
@section('page-title', 'Invoices')

@section('content')
<div class="admin-page-header">
    <div>
        <h2>Invoice Management</h2>
        <p>Review rent billing, payment status, due dates, and outstanding balances.</p>
    </div>
    <form method="GET" class="admin-filter">
        <select name="status">
            <option value="">All Statuses</option>
            @foreach(['PENDING','PARTIAL','PAID','OVERDUE','CANCELLED'] as $status)
                <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ $status }}</option>
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
            <tr><td colspan="8" class="empty-state">No invoices found.</td></tr>
            @endforelse
        </tbody>
    </table>
    </div>
    <div class="pagination">{{ $invoices->links() }}</div>
</div>
@endsection
