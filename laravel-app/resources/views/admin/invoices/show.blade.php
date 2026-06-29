@extends('admin.layout')
@section('page-title', 'Invoice Details')

@section('content')
<div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;">
    <a href="{{ route('admin.invoices.index') }}" style="color:#94a3b8;text-decoration:none;font-size:13px;">Invoices</a>
    <span style="color:#cbd5e1;">/</span>
    <span style="font-weight:600;">{{ date('M Y', mktime(0,0,0,$invoice->period_month,1,$invoice->period_year)) }}</span>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
    <div class="card">
        <h3 style="font-size:13px;color:#94a3b8;margin-bottom:10px;text-transform:uppercase;">Invoice Summary</h3>
        <p style="font-size:14px;margin-bottom:4px;"><strong>Tenant:</strong> {{ $invoice->tenant?->name ?? '—' }}</p>
        <p style="font-size:14px;margin-bottom:4px;"><strong>Unit:</strong> {{ $invoice->unit?->unit_number ?? '—' }} ({{ $invoice->unit->property->name ?? '—' }})</p>
        <p style="font-size:14px;margin-bottom:4px;"><strong>Issue Date:</strong> {{ $invoice->issue_date?->format('d M Y') }}</p>
        <p style="font-size:14px;margin-bottom:4px;"><strong>Due Date:</strong> {{ $invoice->due_date?->format('d M Y') }}</p>
        <p style="font-size:14px;margin-bottom:4px;"><strong>Status:</strong> <span class="badge badge-gray">{{ $invoice->status }}</span></p>
    </div>
    <div class="card">
        <h3 style="font-size:13px;color:#94a3b8;margin-bottom:10px;text-transform:uppercase;">Amounts</h3>
        <p style="font-size:14px;margin-bottom:4px;"><strong>Base Amount:</strong> KES {{ number_format($invoice->amount, 2) }}</p>
        <p style="font-size:14px;margin-bottom:4px;"><strong>Penalty:</strong> KES {{ number_format($invoice->penalty_amount, 2) }}</p>
        <p style="font-size:18px;margin:10px 0 4px;font-weight:700;color:#1d4ed8;"><strong>Total:</strong> KES {{ number_format($invoice->total_amount, 2) }}</p>
        <p style="font-size:14px;margin-bottom:4px;color:#16a34a;"><strong>Paid:</strong> KES {{ number_format($invoice->paid_amount, 2) }}</p>
        <p style="font-size:14px;margin-bottom:4px;color:#dc2626;"><strong>Balance:</strong> KES {{ number_format($invoice->total_amount - $invoice->paid_amount, 2) }}</p>
    </div>
</div>

<div class="card">
    <h3 style="font-size:13px;color:#94a3b8;margin-bottom:12px;text-transform:uppercase;">Payment History</h3>
    <table>
        <thead><tr><th>Date</th><th>Amount (KES)</th><th>Method</th><th>Reference</th></tr></thead>
        <tbody>
            @forelse($invoice->payments as $payment)
            <tr>
                <td style="font-size:13px;">{{ $payment->paid_at?->format('d M Y H:i') }}</td>
                <td>{{ number_format($payment->amount, 2) }}</td>
                <td>{{ $payment->method ?? '—' }}</td>
                <td style="font-family:monospace;font-size:12px;">{{ $payment->reference ?? '—' }}</td>
            </tr>
            @empty
            <tr><td colspan="4" style="color:#94a3b8;">No payments recorded yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
