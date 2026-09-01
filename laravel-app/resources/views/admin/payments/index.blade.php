@extends('admin.layout')
@section('page-title', 'Payments')

@section('content')
@php
    $hasFilters = request()->filled('search') || request()->filled('status') || request()->filled('method')
        || request()->filled('date_from') || request()->filled('date_to');
    $statusClasses = [
        'SUCCESSFUL' => 'badge-green',
        'PENDING' => 'badge-yellow',
        'FAILED' => 'badge-red',
        'CANCELLED' => 'badge-gray',
        'EXPIRED' => 'badge-gray',
        'REVERSED' => 'badge-blue',
    ];
@endphp

<style>
    .payments-page { color:var(--text); }
    .payment-metrics { display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin-bottom:16px; }
    .payment-metric { padding:16px 18px;border:1px solid var(--line);border-radius:15px;background:linear-gradient(180deg,rgba(17,24,39,.96),rgba(11,18,32,.96));box-shadow:0 14px 30px rgba(2,6,23,.2); }
    .payment-metric span { display:block;color:var(--muted);font-size:11px;font-weight:800;letter-spacing:.07em;text-transform:uppercase;margin-bottom:7px; }
    .payment-metric strong { display:block;color:#f8fafc;font-size:22px;line-height:1.2;font-variant-numeric:tabular-nums; }
    .payment-filter-card { padding:0;overflow:hidden;margin-bottom:16px; }
    .payment-filter-heading { display:flex;align-items:center;justify-content:space-between;gap:14px;padding:16px 18px;border-bottom:1px solid var(--line);background:rgba(15,23,42,.6); }
    .payment-filter-heading h3 { font-size:15px;color:#f8fafc;margin:0; }
    .payment-filter-heading p { font-size:12px;color:var(--muted);margin-top:3px; }
    .filter-count { flex:0 0 auto;padding:5px 9px;border-radius:999px;background:rgba(96,165,250,.12);color:#bfdbfe;font-size:11px;font-weight:800; }
    .payment-filter-form { display:grid;grid-template-columns:minmax(240px,1.7fr) repeat(4,minmax(145px,1fr));gap:13px;align-items:end;padding:18px; }
    .payment-field { min-width:0; }
    .payment-field label { display:block;color:#cbd5e1;font-size:11px;font-weight:800;letter-spacing:.035em;text-transform:uppercase;margin-bottom:7px; }
    .payment-control-wrap { position:relative; }
    .payment-control-icon { position:absolute;left:13px;top:50%;width:17px;height:17px;transform:translateY(-50%);color:#64748b;pointer-events:none; }
    .payment-control-icon svg { width:100%;height:100%;fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round; }
    .payment-field input,.payment-field select { width:100%;height:44px;padding:0 13px;border:1px solid rgba(148,163,184,.3);border-radius:11px;background:#0b1220;color:#f8fafc;font-size:13px;outline:none;transition:border-color .15s,box-shadow .15s,background .15s; }
    .payment-field .has-icon { padding-left:41px; }
    .payment-field input::placeholder { color:#64748b;opacity:1; }
    .payment-field input:hover,.payment-field select:hover { border-color:rgba(148,163,184,.55); }
    .payment-field input:focus,.payment-field select:focus { border-color:#60a5fa;background:#0f172a;box-shadow:0 0 0 3px rgba(96,165,250,.14); }
    .payment-filter-actions { display:flex;justify-content:flex-end;gap:9px;padding:0 18px 18px; }
    .payment-filter-actions .btn { min-width:104px; }
    .payments-table-card { padding:0;overflow:hidden; }
    .payments-table-header { display:flex;justify-content:space-between;align-items:center;gap:12px;padding:15px 18px;border-bottom:1px solid var(--line); }
    .payments-table-header h3 { font-size:15px;margin:0; }
    .payments-table-header span { color:var(--muted);font-size:12px; }
    .payments-table-card .table-scroll { width:100%; }
    .payments-table-card table { min-width:1050px; }
    .payments-table-card td { padding-top:14px;padding-bottom:14px; }
    .payment-tenant strong,.payment-location strong { display:block;color:#f8fafc;font-size:13px; }
    .payment-tenant span,.payment-location span { display:block;color:var(--muted);font-size:12px;margin-top:3px; }
    .payment-amount { color:#a7f3d0;font-weight:800;font-variant-numeric:tabular-nums;white-space:nowrap; }
    .payment-phone { white-space:nowrap;font-variant-numeric:tabular-nums; }
    .payment-identifiers { display:grid;gap:4px;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:11px;max-width:240px; }
    .payment-identifiers div { overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#cbd5e1; }
    .payment-identifiers span { display:inline-block;width:60px;color:#64748b;font-family:inherit; }
    .payment-invoice-link { white-space:nowrap;text-decoration:none;font-weight:700;font-size:12px; }
    .payment-empty { padding:42px 18px!important;text-align:center;color:var(--muted); }
    .payment-pagination { padding:0 18px 18px; }
    @media (max-width:1200px) { .payment-filter-form { grid-template-columns:repeat(2,minmax(0,1fr)); }.payment-search-field { grid-column:1/-1; } }
    @media (max-width:700px) { .payment-metrics { grid-template-columns:1fr; }.payment-filter-form { grid-template-columns:1fr; }.payment-search-field { grid-column:auto; }.payment-filter-heading { align-items:flex-start; }.payment-filter-actions { flex-direction:column-reverse; }.payment-filter-actions .btn { width:100%; }.payments-table-header { align-items:flex-start;flex-direction:column; } }
</style>

<div class="payments-page">
    <div class="admin-page-header">
        <div>
            <h2>Payment transactions</h2>
            <p>Find tenant payments, verify M-Pesa references, and review transaction status.</p>
        </div>
    </div>

    <div class="payment-metrics">
        <div class="payment-metric"><span>Matching payments</span><strong>{{ number_format($paymentSummary['total']) }}</strong></div>
        <div class="payment-metric"><span>Successful value</span><strong style="color:#a7f3d0;">KSh {{ number_format($paymentSummary['successful_amount'], 2) }}</strong></div>
        <div class="payment-metric"><span>Pending review</span><strong style="color:{{ $paymentSummary['pending'] > 0 ? '#fcd34d' : '#f8fafc' }};">{{ number_format($paymentSummary['pending']) }}</strong></div>
    </div>

    <div class="card payment-filter-card">
        <div class="payment-filter-heading">
            <div><h3>Filter payments</h3><p>Search across tenant names and transaction identifiers.</p></div>
            @if($hasFilters)<span class="filter-count">Filters active</span>@endif
        </div>
        <form method="GET" action="{{ route('admin.payments.index') }}">
            <div class="payment-filter-form">
                <div class="payment-field payment-search-field">
                    <label for="paymentSearch">Search</label>
                    <div class="payment-control-wrap">
                        <span class="payment-control-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg></span>
                        <input class="has-icon" id="paymentSearch" name="search" value="{{ request('search') }}" placeholder="Receipt, checkout ID, phone or tenant">
                    </div>
                </div>
                <div class="payment-field">
                    <label for="paymentStatus">Status</label>
                    <select id="paymentStatus" name="status">
                        <option value="">All statuses</option>
                        @foreach(['SUCCESSFUL', 'PENDING', 'FAILED', 'CANCELLED', 'EXPIRED', 'REVERSED'] as $status)
                            <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst(strtolower($status)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="payment-field">
                    <label for="paymentMethod">Method</label>
                    <select id="paymentMethod" name="method">
                        <option value="">All methods</option>
                        @foreach($paymentMethods as $method)
                            <option value="{{ $method }}" @selected(request('method') === $method)>{{ ucfirst(strtolower(str_replace('_', ' ', $method))) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="payment-field"><label for="paymentDateFrom">From date</label><input id="paymentDateFrom" type="date" name="date_from" value="{{ request('date_from') }}"></div>
                <div class="payment-field"><label for="paymentDateTo">To date</label><input id="paymentDateTo" type="date" name="date_to" value="{{ request('date_to') }}"></div>
            </div>
            <div class="payment-filter-actions">
                @if($hasFilters)<a class="btn btn-secondary" href="{{ route('admin.payments.index') }}">Clear filters</a>@endif
                <button class="btn btn-primary" type="submit">Apply filters</button>
            </div>
        </form>
    </div>

    <div class="card payments-table-card">
        <div class="payments-table-header">
            <h3>Transactions</h3>
            <span>Showing {{ $payments->firstItem() ?? 0 }}–{{ $payments->lastItem() ?? 0 }} of {{ number_format($payments->total()) }}</span>
        </div>
        <div class="table-scroll">
            <table>
                <thead><tr><th>Date &amp; time</th><th>Tenant</th><th>Property / unit</th><th>Amount</th><th>Phone</th><th>Transaction IDs</th><th>Status</th><th>Invoice</th></tr></thead>
                <tbody>
                    @forelse($payments as $payment)
                        @php $paymentStatus = strtoupper($payment->status ?? 'PENDING'); @endphp
                        <tr>
                            <td style="white-space:nowrap;">{{ ($payment->paid_at ?? $payment->created_at)?->format('d M Y, H:i') ?? '-' }}</td>
                            <td class="payment-tenant"><strong>{{ $payment->invoice?->tenant?->name ?? 'Unknown tenant' }}</strong><span>{{ $payment->invoice?->tenant?->email ?? 'No email available' }}</span></td>
                            <td class="payment-location"><strong>{{ $payment->invoice?->unit?->property?->name ?? 'Unknown property' }}</strong><span>Unit {{ $payment->invoice?->unit?->unit_number ?? '-' }}</span></td>
                            <td class="payment-amount">{{ $payment->amount_formatted }}</td>
                            <td class="payment-phone">{{ $payment->payment_phone ?? '-' }}</td>
                            <td>
                                <div class="payment-identifiers" title="Click and drag to copy a transaction identifier">
                                    <div><span>Receipt</span>{{ $payment->mpesa_receipt ?? '-' }}</div>
                                    <div><span>Checkout</span>{{ $payment->checkout_request_id ?? '-' }}</div>
                                    <div><span>Reference</span>{{ $payment->reference ?? '-' }}</div>
                                </div>
                            </td>
                            <td><span class="badge {{ $statusClasses[$paymentStatus] ?? 'badge-gray' }}">{{ ucfirst(strtolower($paymentStatus)) }}</span></td>
                            <td>
                                @if($payment->invoice)
                                    <a class="payment-invoice-link" href="{{ route('admin.invoices.show', $payment->invoice) }}">View invoice →</a>
                                @else
                                    <span class="muted">Unavailable</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="payment-empty">No payments match the selected filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="payment-pagination">{{ $payments->links() }}</div>
    </div>
</div>
@endsection
