<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tenant Trust &amp; Payment Certificate - {{ $tenantUser->name }}</title>
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
        .cert-title-box {
            text-align: right;
        }
        .cert-title {
            font-size: 18px;
            font-weight: bold;
            color: #1F2EDB;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 0;
        }
        .score-hero {
            background: linear-gradient(135deg, #0B1B3A, #071226);
            color: #FFFFFF;
            border-radius: 12px;
            padding: 24px;
            text-align: center;
            margin-bottom: 24px;
        }
        .score-num {
            font-size: 52px;
            font-weight: 900;
            color: #60A5FA;
            line-height: 1;
        }
        .score-grade {
            font-size: 24px;
            font-weight: bold;
            color: #BBF7D0;
            margin-left: 8px;
        }
        .score-tag {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #93C5FD;
            margin-top: 6px;
        }
        .metrics-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }
        .metric-cell {
            width: 25%;
            text-align: center;
            padding: 14px;
            background: #F8FAFC;
            border: 1px solid #E2E8F0;
        }
        .metric-val {
            font-size: 18px;
            font-weight: bold;
            color: #0F172A;
        }
        .metric-label {
            font-size: 11px;
            color: #64748B;
            text-transform: uppercase;
            margin-top: 4px;
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
        .stamp-box {
            border: 2px dashed #16A34A;
            border-radius: 8px;
            padding: 12px;
            text-align: center;
            background: #F0FDF4;
            color: #166534;
            margin-bottom: 20px;
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
                <div class="logo-sub">Payment Reliability &amp; Tenancy Reference</div>
            </td>
            <td class="cert-title-box">
                <div class="cert-title">Trust Score Certificate</div>
                <div style="font-size: 11px; color:#64748B; margin-top: 4px;">Issued: {{ now()->format('d M Y') }}</div>
            </td>
        </tr>
    </table>

    <div class="score-hero">
        <div class="score-num">{{ $trustData['score'] }}<span class="score-grade">({{ $trustData['grade'] }})</span></div>
        <div class="score-tag">★ {{ $trustData['rating'] }} ★</div>
        <div style="font-size: 11px; color: #94A3B8; margin-top: 8px;">
            Verified payment history across Starmax residential properties in Kenya
        </div>
    </div>

    <table class="metrics-grid">
        <tr>
            <td class="metric-cell" style="border-radius: 8px 0 0 8px;">
                <div class="metric-val" style="color:#16A34A;">{{ $trustData['on_time_percentage'] }}%</div>
                <div class="metric-label">On-Time Payment Rate</div>
            </td>
            <td class="metric-cell">
                <div class="metric-val">{{ $trustData['settled_invoices_count'] }} / {{ $trustData['total_invoices_count'] }}</div>
                <div class="metric-label">Invoices Cleared</div>
            </td>
            <td class="metric-cell">
                <div class="metric-val">KSh {{ number_format($trustData['total_paid_kes'], 0) }}</div>
                <div class="metric-label">Total Rent Paid</div>
            </td>
            <td class="metric-cell" style="border-radius: 0 8px 8px 0;">
                <div class="metric-val" style="color: {{ $trustData['outstanding_balance_kes'] > 0 ? '#DC2626' : '#16A34A' }};">
                    KSh {{ number_format($trustData['outstanding_balance_kes'], 0) }}
                </div>
                <div class="metric-label">Outstanding Balance</div>
            </td>
        </tr>
    </table>

    <table class="info-grid">
        <tr>
            <td class="info-col" style="padding-right: 10px;">
                <div class="info-heading">Tenant Details</div>
                <div class="info-row"><strong>Name:</strong> {{ $tenantUser->name }}</div>
                <div class="info-row"><strong>Email:</strong> {{ $tenantUser->email }}</div>
                <div class="info-row"><strong>Phone:</strong> {{ $tenantUser->phone_number ?? '-' }}</div>
                <div class="info-row"><strong>Member Since:</strong> {{ $tenantUser->created_at?->format('d M Y') }}</div>
            </td>
            <td class="info-col" style="padding-left: 10px;">
                <div class="info-heading">Tenancy Verification</div>
                <div class="info-row"><strong>Current / Recent Unit:</strong> {{ $tenantUser->activeTenancy()?->unit?->unit_number ?? 'Verified Resident' }}</div>
                <div class="info-row"><strong>Property:</strong> {{ $tenantUser->activeTenancy()?->unit?->property?->name ?? 'Starmax Managed Portfolio' }}</div>
                <div class="info-row"><strong>Landlord Contact:</strong> {{ $tenantUser->activeTenancy()?->unit?->property?->landlord?->name ?? 'Starmax Platform' }}</div>
            </td>
        </tr>
    </table>

    <div class="stamp-box">
        <div style="font-weight: bold; font-size: 13px; text-transform: uppercase;">
            ✓ VERIFIED DIGITAL TENANCY REFERENCE
        </div>
        <div style="font-size: 11px; margin-top: 4px;">
            This certificate is accepted across Starmax properties to expedite new lease approvals and waiver of enhanced deposits.
        </div>
    </div>

    <div class="footer">
        Generated electronically by Starmax Tenant Services • app.starmaxltd.com • Verification ID: {{ strtoupper(substr($tenantUser->id, 0, 12)) }}
    </div>

</body>
</html>
