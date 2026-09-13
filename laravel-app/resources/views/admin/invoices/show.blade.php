@extends('admin.layout')
@section('page-title', 'Invoice Details')

@section('content')
<div class="admin-page-header">
    <div>
        <h2>Invoice #INV-{{ strtoupper(substr($invoice->id, 0, 8)) }}</h2>
        <p>{{ $invoice->unit?->property?->name }} • Unit {{ $invoice->unit?->unit_number }} • {{ date('F Y', mktime(0,0,0,$invoice->period_month,1,$invoice->period_year)) }}</p>
    </div>
    <div class="admin-actions">
        <a href="{{ route('admin.invoices.index') }}" class="btn btn-secondary">Back to Invoices</a>
        @if($invoice->status !== 'PAID')
            <form method="POST" action="{{ route('admin.invoices.remind', $invoice) }}" style="display:inline;">
                @csrf
                <button type="submit" class="btn btn-secondary" onclick="return confirm('Send an instant rent payment reminder to this tenant?')">
                    🔔 Send Reminder
                </button>
            </form>
        @endif
        <a href="{{ route('admin.invoices.pdf', $invoice) }}" target="_blank" class="btn btn-primary">
            Download PDF Statement
        </a>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
    <div class="card">
        <h3 style="font-size:13px;color:#94a3b8;margin-bottom:10px;text-transform:uppercase;">Invoice Summary</h3>
        <p style="font-size:14px;margin-bottom:4px;"><strong>Tenant:</strong> {{ $invoice->tenant?->name ?? '-' }} ({{ $invoice->tenant?->phone_number ?? '-' }})</p>
        <p style="font-size:14px;margin-bottom:4px;"><strong>Type:</strong> {{ ucfirst(strtolower(str_replace('_', ' ', $invoice->billing_type))) }}</p>
        <p style="font-size:14px;margin-bottom:4px;"><strong>Unit:</strong> {{ $invoice->unit?->unit_number ?? '-' }} ({{ $invoice->unit?->property?->name ?? '-' }})</p>
        <p style="font-size:14px;margin-bottom:4px;"><strong>Issue Date:</strong> {{ $invoice->issue_date?->format('d M Y') }}</p>
        <p style="font-size:14px;margin-bottom:4px;"><strong>Due Date:</strong> <span style="color:{{ $invoice->status === 'OVERDUE' ? '#f87171' : '#f8fafc' }}; font-weight:700;">{{ $invoice->due_date?->format('d M Y') }}</span></p>
        <p style="font-size:14px;margin-bottom:4px;"><strong>Status:</strong> 
            @php $sc = ['PAID'=>'badge-green','PENDING'=>'badge-yellow','OVERDUE'=>'badge-red','PARTIAL'=>'badge-blue','CANCELLED'=>'badge-gray']; @endphp
            <span class="badge {{ $sc[$invoice->status] ?? 'badge-gray' }}">{{ $invoice->status }}</span>
        </p>
        @if($invoice->last_reminder_sent_at)
            <small class="muted" style="font-size:11px; display:block; margin-top:6px;">Last reminder dispatched: {{ $invoice->last_reminder_sent_at->format('d M Y, h:i A') }}</small>
        @endif
    </div>
    <div class="card">
        <h3 style="font-size:13px;color:#94a3b8;margin-bottom:10px;text-transform:uppercase;">Financials</h3>
        <p style="font-size:14px;margin-bottom:4px;"><strong>Base Rent:</strong> KSh {{ number_format($invoice->amount, 2) }}</p>
        @if($invoice->water_current_reading !== null && $invoice->water_previous_reading !== null)
            <p style="font-size:14px;margin-bottom:4px;color:#93c5fd;">
                <strong>Water Sub-Meter:</strong> {{ $invoice->water_units_consumed }} Units @ {{ $invoice->water_rate_per_unit }} = KSh {{ number_format($invoice->water_cost, 2) }}
            </p>
        @endif
        @if($invoice->electricity_current_reading !== null && $invoice->electricity_previous_reading !== null)
            <p style="font-size:14px;margin-bottom:4px;color:#fde68a;">
                <strong>Power Sub-Meter:</strong> {{ $invoice->electricity_units_consumed }} Units @ {{ $invoice->electricity_rate_per_unit }} = KSh {{ number_format($invoice->electricity_cost, 2) }}
            </p>
        @endif
        <p style="font-size:14px;margin-bottom:4px;"><strong>Penalty:</strong> KSh {{ number_format($invoice->penalty_amount, 2) }}</p>
        <p style="font-size:18px;margin:10px 0 4px;font-weight:800;color:#60a5fa;"><strong>Total Amount:</strong> KSh {{ number_format($invoice->total_amount, 2) }}</p>
        <p style="font-size:14px;margin-bottom:4px;color:#86efac;"><strong>Paid to Date:</strong> KSh {{ number_format($invoice->paid_amount, 2) }}</p>
        <p style="font-size:14px;margin-bottom:4px;color:#fca5a5;"><strong>Remaining Balance:</strong> KSh {{ number_format($invoice->balance_amount, 2) }}</p>
    </div>
</div>

<div class="card">
    <h3 style="font-size:13px;color:#94a3b8;margin-bottom:12px;text-transform:uppercase;">Payment History &amp; Receipts</h3>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Amount</th>
                <th>Method</th>
                <th>Payment Phone</th>
                <th>Status</th>
                <th>Reference / Receipt</th>
                <th style="text-align:right;">Official Receipt</th>
            </tr>
        </thead>
        <tbody>
            @forelse($invoice->payments as $payment)
            <tr>
                <td style="font-size:13px;">{{ $payment->paid_at?->format('d M Y H:i') ?? $payment->created_at?->format('d M Y H:i') }}</td>
                <td style="font-weight:700; color:#86efac;">{{ $payment->amount_formatted }}</td>
                <td>{{ $payment->method ?? 'M-PESA' }}</td>
                <td>{{ $payment->payment_phone ?? 'Not captured' }}</td>
                <td>
                    <span class="badge {{ $payment->status === 'SUCCESSFUL' ? 'badge-green' : 'badge-yellow' }}">
                        {{ $payment->status ?? 'SUCCESSFUL' }}
                    </span>
                </td>
                <td style="font-family:monospace;font-size:12px;">{{ $payment->mpesa_receipt ?? $payment->reference ?? '-' }}</td>
                <td style="text-align:right;">
                    @if($payment->status === 'SUCCESSFUL')
                        <a href="{{ route('admin.payments.receipt-pdf', $payment) }}" target="_blank" class="btn btn-primary" style="min-height:28px; padding:3px 8px; font-size:11px;">
                            Receipt PDF
                        </a>
                    @else
                        <span class="muted" style="font-size:11px;">Pending</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="7" style="color:#94a3b8; text-align:center; padding:20px;">No payments recorded yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
