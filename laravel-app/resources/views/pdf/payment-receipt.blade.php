<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Receipt - {{ $payment->mpesa_receipt ?? $payment->id }}</title>
    <style>
        @page {
            margin: 24px;
            size: a4 portrait;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #071226;
            margin: 0;
            padding: 24px;
            font-size: 13px;
            line-height: 1.5;
            background: #fff;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
            border-bottom: 2px solid #1F2EDB;
            padding-bottom: 16px;
        }
        .logo-text {
            font-size: 20px;
            font-weight: bold;
            color: #071226;
            letter-spacing: -0.5px;
        }
        .logo-sub {
            font-size: 11px;
            color: #6B7280;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .receipt-title-box {
            text-align: right;
        }
        .receipt-title {
            font-size: 22px;
            font-weight: bold;
            color: #1F2EDB;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 0;
        }
        .receipt-num {
            font-size: 12px;
            color: #4B5563;
            margin-top: 4px;
        }
        .badge-paid {
            display: inline-block;
            background-color: #EAF8EF;
            color: #166534;
            border: 1px solid #BBF7D0;
            padding: 4px 12px;
            border-radius: 12px;
            font-weight: bold;
            font-size: 12px;
            text-transform: uppercase;
            margin-top: 6px;
        }
        .info-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }
        .info-col {
            width: 50%;
            vertical-align: top;
            padding: 12px 14px;
            background: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-radius: 8px;
        }
        .info-col:first-child {
            margin-right: 10px;
        }
        .info-heading {
            font-size: 11px;
            text-transform: uppercase;
            color: #6B7280;
            font-weight: bold;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
            border-bottom: 1px solid #E2E8F0;
            padding-bottom: 4px;
        }
        .info-row {
            margin-bottom: 4px;
        }
        .info-label {
            color: #64748B;
            font-size: 12px;
        }
        .info-val {
            font-weight: 600;
            color: #0F172A;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }
        .items-table th {
            background-color: #0B1B3A;
            color: #FFFFFF;
            text-align: left;
            padding: 10px 12px;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .items-table td {
            padding: 12px;
            border-bottom: 1px solid #E2E8F0;
            font-size: 12px;
        }
        .items-table tr:nth-child(even) td {
            background-color: #F8FAFC;
        }
        .amount-summary {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 24px;
        }
        .amount-summary td {
            padding: 8px 12px;
            font-size: 13px;
        }
        .summary-label {
            text-align: right;
            color: #64748B;
            width: 70%;
        }
        .summary-val {
            text-align: right;
            font-weight: bold;
            color: #0F172A;
            width: 30%;
        }
        .total-row td {
            background: #EEF2FF;
            color: #1F2EDB;
            font-size: 15px;
            border-top: 2px solid #1F2EDB;
            border-bottom: 2px solid #1F2EDB;
            font-weight: bold;
        }
        .stamp-box {
            border: 2px dashed #16A34A;
            border-radius: 8px;
            padding: 12px;
            text-align: center;
            background: #F0FDF4;
            color: #166534;
            margin-bottom: 20px;
        }
        .stamp-title {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .footer {
            margin-top: 30px;
            border-top: 1px solid #E2E8F0;
            padding-top: 12px;
            text-align: center;
            font-size: 10px;
            color: #94A3B8;
        }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td style="vertical-align: middle;">
                <div class="logo-text">STARMAX TENANT SERVICES</div>
                <div class="logo-sub">Property Management &amp; Payment Settlement</div>
            </td>
            <td class="receipt-title-box">
                <div class="receipt-title">Official Receipt</div>
                <div class="receipt-num">Receipt # {{ $payment->mpesa_receipt ?? strtoupper(substr($payment->id, 0, 12)) }}</div>
                <div><span class="badge-paid">Payment Verified</span></div>
            </td>
        </tr>
    </table>

    <table class="info-grid">
        <tr>
            <td class="info-col" style="padding-right: 10px;">
                <div class="info-heading">Paid By (Tenant)</div>
                <div class="info-row"><span class="info-label">Name:</span> <span class="info-val">{{ $payment->invoice?->tenant?->name ?? 'Tenant' }}</span></div>
                <div class="info-row"><span class="info-label">Phone:</span> <span class="info-val">{{ $payment->payment_phone ?? $payment->invoice?->tenant?->phone_number ?? '-' }}</span></div>
                <div class="info-row"><span class="info-label">Email:</span> <span class="info-val">{{ $payment->invoice?->tenant?->email ?? '-' }}</span></div>
                <div class="info-row"><span class="info-label">Property:</span> <span class="info-val">{{ $payment->invoice?->unit?->property?->name ?? '-' }}</span></div>
                <div class="info-row"><span class="info-label">Unit / Room:</span> <span class="info-val">{{ $payment->invoice?->unit?->unit_number ?? '-' }}</span></div>
            </td>
            <td class="info-col" style="padding-left: 10px;">
                <div class="info-heading">Payment Information</div>
                <div class="info-row"><span class="info-label">M-Pesa Receipt:</span> <span class="info-val" style="color:#16A34A;">{{ $payment->mpesa_receipt ?? 'SIMULATED' }}</span></div>
                <div class="info-row"><span class="info-label">Date &amp; Time:</span> <span class="info-val">{{ ($payment->paid_at ?? $payment->created_at)?->format('d M Y, h:i A') }}</span></div>
                <div class="info-row"><span class="info-label">Payment Method:</span> <span class="info-val">{{ $payment->method ?? 'M-PESA' }}</span></div>
                <div class="info-row"><span class="info-label">Invoice Ref:</span> <span class="info-val">{{ strtoupper(substr($payment->invoice_id, 0, 8)) }} ({{ $payment->invoice?->billing_type ?? 'RENT' }})</span></div>
                <div class="info-row"><span class="info-label">Landlord / Owner:</span> <span class="info-val">{{ $payment->invoice?->unit?->property?->landlord?->name ?? 'Starmax Properties' }}</span></div>
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 55%;">Description</th>
                <th style="width: 20%; text-align: right;">Invoice Amount</th>
                <th style="width: 25%; text-align: right;">Amount Paid</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <strong>{{ $payment->invoice?->billing_type ?? 'Rental Payment' }}</strong> - Unit {{ $payment->invoice?->unit?->unit_number ?? '' }} ({{ $payment->invoice?->unit?->property?->name ?? '' }})
                    <div style="font-size: 11px; color: #64748B; margin-top: 2px;">
                        Period: {{ \Carbon\Carbon::createFromDate($payment->invoice?->period_year ?? now()->year, $payment->invoice?->period_month ?? now()->month, 1)->format('F Y') }}
                    </div>
                </td>
                <td style="text-align: right;">KSh {{ number_format((float) ($payment->invoice?->total_amount ?? $payment->amount), 2) }}</td>
                <td style="text-align: right; font-weight: bold; color: #16A34A;">KSh {{ number_format((float) $payment->amount, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <table class="amount-summary">
        <tr>
            <td class="summary-label">Invoice Total:</td>
            <td class="summary-val">KSh {{ number_format((float) ($payment->invoice?->total_amount ?? $payment->amount), 2) }}</td>
        </tr>
        <tr>
            <td class="summary-label">Total Paid to Date:</td>
            <td class="summary-val">KSh {{ number_format((float) ($payment->invoice?->paid_amount ?? $payment->amount), 2) }}</td>
        </tr>
        <tr>
            <td class="summary-label">Remaining Balance:</td>
            <td class="summary-val">KSh {{ number_format((float) ($payment->invoice?->balance_amount ?? 0), 2) }}</td>
        </tr>
        <tr class="total-row">
            <td class="summary-label" style="font-weight: bold;">AMOUNT SETTLED IN THIS RECEIPT:</td>
            <td class="summary-val" style="color: #1F2EDB;">KSh {{ number_format((float) $payment->amount, 2) }}</td>
        </tr>
    </table>

    <div class="stamp-box">
        <div class="stamp-title">★ STARMAX VERIFIED PAYMENT TRANSACTION ★</div>
        <div style="font-size: 11px; margin-top: 4px;">Settled via M-Pesa STK Push • Transaction Reference: {{ $payment->reference ?? $payment->checkout_request_id ?? 'VERIFIED' }}</div>
    </div>

    <div class="footer">
        This is an official computer-generated receipt issued by Starmax Tenant Services.<br>
        For inquiries, contact support via the Starmax mobile app or visit app.starmaxltd.com.
    </div>

</body>
</html>
