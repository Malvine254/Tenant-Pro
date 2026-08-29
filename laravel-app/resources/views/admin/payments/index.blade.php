@extends('admin.layout')
@section('page-title', 'Payments')

@section('content')
<div class="card" style="margin-bottom:16px;">
    <form method="GET" style="display:flex;gap:10px;align-items:end;flex-wrap:wrap;">
        <div style="flex:1;min-width:220px;">
            <label>Search receipt, phone, reference or tenant</label>
            <input name="search" value="{{ request('search') }}" placeholder="e.g. QHX12ABC34">
        </div>
        <div style="min-width:180px;">
            <label>Status</label>
            <select name="status">
                <option value="">All statuses</option>
                @foreach(['SUCCESSFUL', 'PENDING', 'FAILED', 'CANCELLED', 'EXPIRED', 'REVERSED'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst(strtolower($status)) }}</option>
                @endforeach
            </select>
        </div>
        <button class="btn btn-primary" type="submit">Filter</button>
    </form>
</div>

<div class="card">
    <table>
        <thead>
        <tr><th>Date &amp; time</th><th>Tenant</th><th>Property / unit</th><th>Amount</th><th>Phone</th><th>Transaction IDs</th><th>Status</th><th>Invoice</th></tr>
        </thead>
        <tbody>
        @forelse($payments as $payment)
            <tr>
                <td>{{ ($payment->paid_at ?? $payment->created_at)?->format('d M Y, H:i') ?? '-' }}</td>
                <td>{{ $payment->invoice?->tenant?->name ?? '-' }}</td>
                <td>{{ $payment->invoice?->unit?->property?->name ?? '-' }} / {{ $payment->invoice?->unit?->unit_number ?? '-' }}</td>
                <td>{{ $payment->amount_formatted }}</td>
                <td>{{ $payment->payment_phone ?? '-' }}</td>
                <td style="font-family:monospace;font-size:12px;">
                    <div><strong>Receipt:</strong> {{ $payment->mpesa_receipt ?? '-' }}</div>
                    <div><strong>Checkout:</strong> {{ $payment->checkout_request_id ?? '-' }}</div>
                    <div><strong>Merchant:</strong> {{ $payment->reference ?? '-' }}</div>
                </td>
                <td><span class="badge badge-gray">{{ ucfirst(strtolower($payment->status ?? 'PENDING')) }}</span></td>
                <td>
                    @if($payment->invoice)
                        <a href="{{ route('admin.invoices.show', $payment->invoice) }}">View</a>
                    @else
                        -
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="8" style="color:#94a3b8;">No payments match these filters.</td></tr>
        @endforelse
        </tbody>
    </table>
    <div style="margin-top:16px;">{{ $payments->links() }}</div>
</div>
@endsection
