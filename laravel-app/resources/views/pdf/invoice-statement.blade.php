<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice Statement - {{ strtoupper(substr($invoice->id, 0, 8)) }}</title>
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
        .invoice-title-box {
            text-align: right;
        }
        .invoice-title {
            font-size: 22px;
            font-weight: bold;
            color: #1F2EDB;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 0;
        }
        .invoice-num {
            font-size: 12px;
            color: #4B5563;
            margin-top: 4px;
        }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
            margin-top: 6px;
        }
        .badge-paid { background-color: #EAF8EF; color: #166534; border: 1px solid #BBF7D0; }
        .badge-pending { background-color: #FFF8E7; color: #92400E; border: 1px solid #FDE68A; }
        .badge-overdue { background-color: #FFF1F1; color: #991B1B; border: 1px solid #FECDD3; }

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
            padding: 10px 12px;
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
        .pay-instructions {
            border: 1px solid #BFDBFE;
            background: #F0F4FF;
            padding: 14px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .pay-instructions strong {
            color: #1F2EDB;
            display: block;
            margin-bottom: 6px;
            font-size: 13px;
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
                <div class="logo-sub">Invoice &amp; Utility Statement</div>
            </td>
            <td class="invoice-title-box">
                <div class="invoice-title">INVOICE</div>
                <div class="invoice-num">#INV-{{ strtoupper(substr($invoice->id, 0, 8)) }}</div>
                <div>
                    @if($invoice->status === 'PAID')
                        <span class="badge badge-paid">PAID IN FULL</span>
                    @elseif($invoice->status === 'OVERDUE')
                        <span class="badge badge-overdue">OVERDUE</span>
                    @else
                        <span class="badge badge-pending">PENDING PAYMENT</span>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <table class="info-grid">
        <tr>
            <td class="info-col" style="padding-right: 10px;">
                <div class="info-heading">Billed To (Tenant)</div>
                <div class="info-row"><span class="info-label">Tenant Name:</span> <span class="info-val">{{ $invoice->tenant?->name ?? 'Tenant' }}</span></div>
                <div class="info-row"><span class="info-label">Phone:</span> <span class="info-val">{{ $invoice->tenant?->phone_number ?? '-' }}</span></div>
                <div class="info-row"><span class="info-label">Email:</span> <span class="info-val">{{ $invoice->tenant?->email ?? '-' }}</span></div>
                <div class="info-row"><span class="info-label">Property:</span> <span class="info-val">{{ $invoice->unit?->property?->name ?? '-' }}</span></div>
                <div class="info-row"><span class="info-label">Unit Number:</span> <span class="info-val">{{ $invoice->unit?->unit_number ?? '-' }}</span></div>
            </td>
            <td class="info-col" style="padding-left: 10px;">
                <div class="info-heading">Billing Details</div>
                <div class="info-row"><span class="info-label">Billing Period:</span> <span class="info-val">{{ \Carbon\Carbon::createFromDate($invoice->period_year ?? now()->year, $invoice->period_month ?? now()->month, 1)->format('F Y') }}</span></div>
                <div class="info-row"><span class="info-label">Issue Date:</span> <span class="info-val">{{ $invoice->issue_date?->format('d M Y') ?? '-' }}</span></div>
                <div class="info-row"><span class="info-label">Due Date:</span> <span class="info-val" style="color: {{ $invoice->status === 'OVERDUE' ? '#DC2626' : '#0F172A' }}; font-weight:bold;">{{ $invoice->due_date?->format('d M Y') ?? '-' }}</span></div>
                <div class="info-row"><span class="info-label">Landlord:</span> <span class="info-val">{{ $invoice->unit?->property?->landlord?->name ?? 'Starmax Properties' }}</span></div>
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 50%;">Item &amp; Description</th>
                <th style="width: 25%; text-align: center;">Usage / Meter Details</th>
                <th style="width: 25%; text-align: right;">Amount (KSh)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <strong>Monthly Base Rent</strong>
                    <div style="font-size: 11px; color: #64748B;">Unit {{ $invoice->unit?->unit_number ?? '' }} - {{ $invoice->billing_type }}</div>
                </td>
                <td style="text-align: center; color: #64748B;">Flat Monthly</td>
                <td style="text-align: right; font-weight: 600;">{{ number_format((float) $invoice->amount, 2) }}</td>
            </tr>

            @if($invoice->water_current_reading !== null && $invoice->water_previous_reading !== null)
            <tr>
                <td>
                    <strong>Water Sub-Meter Usage</strong>
                    <div style="font-size: 11px; color: #64748B;">Prev: {{ $invoice->water_previous_reading }} | Curr: {{ $invoice->water_current_reading }} ({{ $invoice->water_units_consumed }} Units)</div>
                </td>
                <td style="text-align: center; color: #64748B;">{{ $invoice->water_units_consumed }} Units @ {{ number_format((float) $invoice->water_rate_per_unit, 2) }}</td>
                <td style="text-align: right; font-weight: 600;">{{ number_format((float) $invoice->water_cost, 2) }}</td>
            </tr>
            @endif

            @if($invoice->electricity_current_reading !== null && $invoice->electricity_previous_reading !== null)
            <tr>
                <td>
                    <strong>Electricity Sub-Meter Usage</strong>
                    <div style="font-size: 11px; color: #64748B;">Prev: {{ $invoice->electricity_previous_reading }} | Curr: {{ $invoice->electricity_current_reading }} ({{ $invoice->electricity_units_consumed }} Units)</div>
                </td>
                <td style="text-align: center; color: #64748B;">{{ $invoice->electricity_units_consumed }} Units @ {{ number_format((float) $invoice->electricity_rate_per_unit, 2) }}</td>
                <td style="text-align: right; font-weight: 600;">{{ number_format((float) $invoice->electricity_cost, 2) }}</td>
            </tr>
            @endif

            @if((float) ($invoice->penalty_amount ?? 0) > 0)
            <tr>
                <td>
                    <strong style="color: #DC2626;">Late Payment Penalty</strong>
                    <div style="font-size: 11px; color: #64748B;">Overdue penalty fee</div>
                </td>
                <td style="text-align: center; color: #DC2626;">Penalty</td>
                <td style="text-align: right; font-weight: 600; color: #DC2626;">{{ number_format((float) $invoice->penalty_amount, 2) }}</td>
            </tr>
            @endif
        </tbody>
    </table>

    <table class="amount-summary">
        <tr>
            <td class="summary-label">Invoice Total:</td>
            <td class="summary-val">KSh {{ number_format((float) $invoice->total_amount, 2) }}</td>
        </tr>
        <tr>
            <td class="summary-label">Amount Paid:</td>
            <td class="summary-val" style="color: #16A34A;">KSh {{ number_format((float) $invoice->paid_amount, 2) }}</td>
        </tr>
        <tr class="total-row">
            <td class="summary-label" style="font-weight: bold;">TOTAL BALANCE DUE:</td>
            <td class="summary-val">KSh {{ number_format((float) $invoice->balance_amount, 2) }}</td>
        </tr>
    </table>

    <div class="pay-instructions">
        <strong>Payment Instructions (M-Pesa STK &amp; Paybill):</strong>
        <div style="font-size: 12px; color: #1E293B; line-height: 1.6;">
            • <strong>Instant App Pay:</strong> Open the Starmax Tenant app and click <em>Pay via M-Pesa</em> for instant STK Push.<br>
            • <strong>M-Pesa Paybill:</strong> Paybill: <strong>{{ config('services.mpesa.shortcode', '174379') }}</strong> | Account No: <strong>{{ $invoice->unit?->unit_number ?? 'UNIT' }}</strong>
        </div>
    </div>

    <div class="footer">
        Generated electronically by Starmax Tenant Services • app.starmaxltd.com
    </div>

</body>
</html>
