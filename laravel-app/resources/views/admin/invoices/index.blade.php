@extends('admin.layout')
@section('page-title', 'Invoices')

@section('content')
@php
    $totalInvoices = $invoices->total() ?? 0;
    $totalDue = $invoices->sum(fn($invoice) => (float) $invoice->total_amount);
    $totalPaid = $invoices->sum(fn($invoice) => (float) $invoice->paid_amount);
    $overdueCount = $invoices->filter(fn($invoice) => $invoice->status === 'OVERDUE')->count();
    $pendingCount = $invoices->filter(fn($invoice) => $invoice->status === 'PENDING')->count();
@endphp

<style>
    .invoice-shell {
        color: #e2e8f0;
    }
    .invoice-summary-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 14px;
        margin: 0 0 18px;
    }
    .invoice-summary-card {
        background: linear-gradient(180deg,#111827,#0b1220);
        border: 1px solid rgba(148,163,184,.18);
        border-radius: 18px;
        padding: 16px 18px;
        box-shadow: 0 18px 36px rgba(2,6,23,.28);
    }
    .invoice-summary-card .label {
        display: block;
        font-size: 11px;
        color: #94a3b8;
        letter-spacing: .08em;
        text-transform: uppercase;
        margin-bottom: 8px;
    }
    .invoice-summary-card .value {
        font-size: 26px;
        font-weight: 800;
        color: #f8fafc;
        line-height: 1.2;
    }
    .invoice-summary-card .meta {
        margin-top: 8px;
        font-size: 12px;
        color: #cbd5e1;
    }
    .invoice-table-card {
        background: linear-gradient(180deg,#111827,#0b1220);
        border: 1px solid rgba(148,163,184,.18);
        border-radius: 18px;
        box-shadow: 0 18px 36px rgba(2,6,23,.28);
        overflow: hidden;
    }
    .invoice-table-card table {
        width: 100%;
        border-collapse: collapse;
        color: #e2e8f0;
    }
    .invoice-table-card th {
        text-align: left;
        padding: 12px 14px;
        font-size: 11px;
        letter-spacing: .08em;
        color: #cbd5e1;
        text-transform: uppercase;
        background: rgba(15,23,42,.92);
        border-bottom: 1px solid rgba(148,163,184,.18);
    }
    .invoice-table-card td {
        padding: 14px;
        border-bottom: 1px solid rgba(148,163,184,.12);
        vertical-align: middle;
    }
    .invoice-table-card tbody tr:hover {
        background: rgba(15,23,42,.55);
    }
    .invoice-period {
        font-weight: 700;
        color: #f8fafc;
    }
    .invoice-tenant strong {
        display: block;
        color: #f8fafc;
    }
    .invoice-tenant span {
        color: #94a3b8;
        font-size: 12px;
    }
    .invoice-unit {
        font-size: 13px;
        color: #cbd5e1;
    }
    .invoice-unit small {
        display: block;
        color: #94a3b8;
        margin-top: 4px;
    }
    .invoice-mono {
        font-variant-numeric: tabular-nums;
        font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
    }
    .invoice-empty {
        color: #94a3b8;
        text-align: center;
        padding: 30px 14px;
    }
</style>

<div class="admin-page-header invoice-shell">
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

<div class="invoice-summary-grid invoice-shell">
    <div class="invoice-summary-card">
        <span class="label">Total invoices</span>
        <div class="value">{{ number_format($totalInvoices) }}</div>
        <div class="meta">Across all tenant billing periods</div>
    </div>
    <div class="invoice-summary-card">
        <span class="label">Total due</span>
        <div class="value invoice-mono">KSh {{ number_format($totalDue, 2) }}</div>
        <div class="meta">Current invoice value</div>
    </div>
    <div class="invoice-summary-card">
        <span class="label">Paid</span>
        <div class="value invoice-mono" style="color:#86efac;">KSh {{ number_format($totalPaid, 2) }}</div>
        <div class="meta">Collected so far</div>
    </div>
    <div class="invoice-summary-card">
        <span class="label">Attention</span>
        <div class="value" style="color:#fca5a5;">{{ $overdueCount + $pendingCount }}</div>
        <div class="meta">{{ $overdueCount }} overdue · {{ $pendingCount }} pending</div>
    </div>
</div>

<div class="invoice-table-card invoice-shell">
    <div class="table-scroll">
    <table>
        <thead>
            <tr>
                <th>Invoice</th>
                <th>Type</th>
                <th>Tenant</th>
                <th>Property / Unit</th>
                <th>Amount</th>
                <th>Paid</th>
                <th>Status</th>
                <th>Due Date</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($invoices as $invoice)
            <tr>
                <td class="invoice-period">{{ date('M Y', mktime(0,0,0,$invoice->period_month,1,$invoice->period_year)) }}</td>
                <td>{{ ucfirst(strtolower(str_replace('_', ' ', $invoice->billing_type))) }}</td>
                <td class="invoice-tenant">
                    <strong>{{ $invoice->tenant?->name ?? '—' }}</strong>
                    <span>{{ $invoice->tenant?->email ?? 'No email' }}</span>
                </td>
                <td class="invoice-unit">
                    {{ $invoice->unit->property->name ?? '—' }}
                    <small>Unit {{ $invoice->unit->unit_number ?? '—' }}</small>
                </td>
                <td class="invoice-mono">KSh {{ number_format($invoice->total_amount, 2) }}</td>
                <td class="invoice-mono" style="color:#86efac;">KSh {{ number_format($invoice->paid_amount, 2) }}</td>
                <td>
                    @php $sc = ['PAID'=>'badge-green','PENDING'=>'badge-yellow','OVERDUE'=>'badge-red','PARTIAL'=>'badge-blue','CANCELLED'=>'badge-gray']; @endphp
                    <span class="badge {{ $sc[$invoice->status] ?? 'badge-gray' }}">{{ $invoice->status }}</span>
                </td>
                <td class="invoice-mono" style="color:#cbd5e1;">{{ $invoice->due_date?->format('d M Y') }}</td>
                <td><a href="{{ route('admin.invoices.show', $invoice) }}" class="btn btn-secondary">View</a></td>
            </tr>
            @empty
            <tr><td colspan="9" class="invoice-empty">No invoices found.</td></tr>
            @endforelse
        </tbody>
    </table>
    </div>
    <div class="pagination">{{ $invoices->links() }}</div>
</div>
@endsection
