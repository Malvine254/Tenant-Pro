<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Residential Tenancy Lease Agreement - {{ $tenant->unit?->unit_number ?? 'Unit' }}</title>
    <style>
        @page {
            margin: 28px;
            size: a4 portrait;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #071226;
            margin: 0;
            padding: 24px;
            font-size: 12px;
            line-height: 1.6;
            background: #fff;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
            border-bottom: 2px solid #1F2EDB;
            padding-bottom: 14px;
        }
        .logo-text {
            font-size: 18px;
            font-weight: bold;
            color: #071226;
        }
        .doc-title {
            text-align: right;
            font-size: 18px;
            font-weight: bold;
            color: #1F2EDB;
            text-transform: uppercase;
        }
        .clause-heading {
            font-size: 13px;
            font-weight: bold;
            color: #0B1B3A;
            margin-top: 16px;
            margin-bottom: 6px;
            border-bottom: 1px solid #E2E8F0;
            padding-bottom: 2px;
            text-transform: uppercase;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
            background: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-radius: 6px;
        }
        .info-table td {
            padding: 8px 12px;
            border-bottom: 1px solid #E2E8F0;
            font-size: 12px;
        }
        .info-table tr:last-child td {
            border-bottom: none;
        }
        .sig-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 28px;
        }
        .sig-box {
            width: 48%;
            border: 1px solid #CBD5E1;
            border-radius: 8px;
            padding: 14px;
            vertical-align: top;
        }
        .sig-line {
            margin-top: 36px;
            border-top: 1px dashed #64748B;
            padding-top: 4px;
            font-size: 11px;
            color: #64748B;
        }
        .footer {
            margin-top: 24px;
            border-top: 1px solid #E2E8F0;
            padding-top: 10px;
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
                <div style="font-size:11px; color:#6B7280; text-transform:uppercase;">Residential Tenancy Agreement</div>
            </td>
            <td class="doc-title">
                <div>Lease Agreement</div>
                <div style="font-size:11px; color:#64748B; margin-top:2px;">Ref: LEASE-{{ strtoupper(substr($tenant->id, 0, 8)) }}</div>
            </td>
        </tr>
    </table>

    <div style="margin-bottom: 14px; font-size: 12px;">
        This <strong>Residential Tenancy Agreement</strong> is made this <strong>{{ $tenant->move_in_date?->format('d M Y') ?? now()->format('d M Y') }}</strong> between the Property Owner/Landlord and the Tenant described below:
    </div>

    <table class="info-table">
        <tr>
            <td style="width: 25%; font-weight: bold; color: #64748B;">LANDLORD / LESSOR:</td>
            <td style="width: 75%; font-weight: 600;">{{ $tenant->unit?->property?->landlord?->name ?? 'Starmax Properties Ltd' }} ({{ $tenant->unit?->property?->landlord?->email ?? '-' }})</td>
        </tr>
        <tr>
            <td style="font-weight: bold; color: #64748B;">TENANT / LESSEE:</td>
            <td style="font-weight: 600;">{{ $tenant->user->name }} (ID / Phone: {{ $tenant->user->phone_number ?? '-' }} • Email: {{ $tenant->user->email }})</td>
        </tr>
        <tr>
            <td style="font-weight: bold; color: #64748B;">DEMISED PREMISES:</td>
            <td>Unit <strong>{{ $tenant->unit?->unit_number }}</strong>, {{ $tenant->unit?->property?->name }}, located at {{ $tenant->unit?->property?->address_line ?? 'Nairobi, Kenya' }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold; color: #64748B;">MONTHLY RENT:</td>
            <td style="font-weight: bold; color: #1F2EDB;">KSh {{ number_format((float) ($tenant->unit?->rent_amount ?? 0), 2) }} per calendar month</td>
        </tr>
        <tr>
            <td style="font-weight: bold; color: #64748B;">COMMENCEMENT DATE:</td>
            <td>{{ $tenant->move_in_date?->format('d F Y') ?? '-' }}</td>
        </tr>
    </table>

    <div class="clause-heading">1. Rent &amp; Utility Payment Terms</div>
    <div>
        1.1 The Tenant agrees to pay the agreed monthly rent on or before the <strong>5th day</strong> of each calendar month.<br>
        1.2 Rent shall be settled electronically via <strong>M-Pesa STK Push</strong> within the Starmax Tenant app or official Paybill.<br>
        1.3 Sub-metered utilities (water and electricity) shall be billed monthly based on verified meter readings and appended to the monthly invoice statement.
    </div>

    <div class="clause-heading">2. Maintenance &amp; Care of Premises</div>
    <div>
        2.1 The Tenant shall maintain the interior fixtures, taps, walls, and fittings in good order as verified in the Initial Move-In Inspection Report.<br>
        2.2 Routine wear and tear is covered by the Landlord. Damages arising from negligence shall be rectified by the Tenant or deducted from the security deposit.<br>
        2.3 Maintenance requests shall be logged digitally via the Starmax mobile application for tracking and resolution.
    </div>

    <div class="clause-heading">3. Notice &amp; Tenancy Termination</div>
    <div>
        3.1 Either party may terminate this agreement by giving <strong>one full calendar month's written notice</strong>.<br>
        3.2 Upon vacating, a Move-Out Condition Inspection shall be conducted to verify meter readings and confirm keys handover.
    </div>

    <table class="sig-table">
        <tr>
            <td class="sig-box" style="margin-right: 14px;">
                <strong>Signed on behalf of Landlord:</strong>
                <div style="font-size:11px; color:#64748B; margin-top:2px;">Authorized Property Manager</div>
                <div class="sig-line">Signature: <strong>{{ $tenant->unit?->property?->landlord?->name ?? 'Starmax Management' }}</strong></div>
            </td>
            <td class="sig-box">
                <strong>Signed by Tenant:</strong>
                <div style="font-size:11px; color:#64748B; margin-top:2px;">Digital Acknowledgment</div>
                <div class="sig-line">Signature: <strong>{{ $tenant->user->name }}</strong></div>
            </td>
        </tr>
    </table>

    <div class="footer">
        Standard Electronic Tenancy Lease Agreement • Starmax Tenant Services • app.starmaxltd.com
    </div>

</body>
</html>
