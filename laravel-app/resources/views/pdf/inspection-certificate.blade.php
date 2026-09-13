<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Unit Inspection Certificate - {{ $inspection->unit?->unit_number ?? 'Unit' }}</title>
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
            font-size: 20px;
            font-weight: bold;
            color: #1F2EDB;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 0;
        }
        .cert-num {
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
            background-color: #EAF8EF;
            color: #166534;
            border: 1px solid #BBF7D0;
        }
        .info-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
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
        .meter-box {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            background: #EFF6FF;
            border: 1px solid #BFDBFE;
            border-radius: 8px;
        }
        .meter-box td {
            padding: 12px;
            vertical-align: middle;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
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
        .cond-good { color: #166534; font-weight: bold; }
        .cond-fair { color: #92400E; font-weight: bold; }
        .cond-damaged { color: #991B1B; font-weight: bold; }
        .signature-grid {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
        }
        .sig-box {
            width: 48%;
            border: 1px solid #CBD5E1;
            padding: 16px;
            border-radius: 8px;
            vertical-align: top;
        }
        .sig-line {
            margin-top: 40px;
            border-top: 1px dashed #64748B;
            padding-top: 6px;
            font-size: 11px;
            color: #64748B;
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
                <div class="logo-sub">Property Inspection &amp; Condition Report</div>
            </td>
            <td class="cert-title-box">
                <div class="cert-title">{{ $inspection->type_label }}</div>
                <div class="cert-num">Ref # {{ strtoupper(substr($inspection->id, 0, 10)) }}</div>
                <div><span class="badge">{{ $inspection->status_label }}</span></div>
            </td>
        </tr>
    </table>

    <table class="info-grid">
        <tr>
            <td class="info-col" style="padding-right: 10px;">
                <div class="info-heading">Premises &amp; Tenancy</div>
                <div class="info-row"><span class="info-label">Property:</span> <span class="info-val">{{ $inspection->property?->name ?? '-' }}</span></div>
                <div class="info-row"><span class="info-label">Unit Number:</span> <span class="info-val">{{ $inspection->unit?->unit_number ?? '-' }}</span></div>
                <div class="info-row"><span class="info-label">Tenant:</span> <span class="info-val">{{ $inspection->tenant?->name ?? 'Unassigned' }}</span></div>
                <div class="info-row"><span class="info-label">Tenant Contact:</span> <span class="info-val">{{ $inspection->tenant?->phone_number ?? '-' }}</span></div>
            </td>
            <td class="info-col" style="padding-left: 10px;">
                <div class="info-heading">Inspection Meta</div>
                <div class="info-row"><span class="info-label">Inspection Date:</span> <span class="info-val">{{ $inspection->inspection_date?->format('d M Y') }}</span></div>
                <div class="info-row"><span class="info-label">Inspector Name:</span> <span class="info-val">{{ $inspection->inspector_name }}</span></div>
                <div class="info-row"><span class="info-label">Inspection Type:</span> <span class="info-val">{{ $inspection->type_label }}</span></div>
                <div class="info-row"><span class="info-label">Landlord:</span> <span class="info-val">{{ $inspection->property?->landlord?->name ?? '-' }}</span></div>
            </td>
        </tr>
    </table>

    <table class="meter-box">
        <tr>
            <td style="width: 50%;">
                <strong style="color: #1E40AF;">💧 Water Sub-Meter Reading:</strong>
                <span style="font-size: 14px; font-weight: bold; margin-left: 8px;">{{ $inspection->meter_reading_water !== null ? number_format((float) $inspection->meter_reading_water, 2).' Units' : 'Not Recorded' }}</span>
            </td>
            <td style="width: 50%;">
                <strong style="color: #1E40AF;">⚡ Power Meter / Token Units:</strong>
                <span style="font-size: 14px; font-weight: bold; margin-left: 8px;">{{ $inspection->meter_reading_electricity !== null ? number_format((float) $inspection->meter_reading_electricity, 2).' Units' : 'Not Recorded' }}</span>
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 30%;">Room / Category</th>
                <th style="width: 30%;">Item Inspected</th>
                <th style="width: 15%; text-align: center;">Condition</th>
                <th style="width: 25%;">Remarks / Notes</th>
            </tr>
        </thead>
        <tbody>
            @forelse($inspection->checklist_data ?? [] as $item)
            <tr>
                <td><strong>{{ $item['category'] ?? 'General' }}</strong></td>
                <td>{{ $item['item_name'] ?? '-' }}</td>
                <td style="text-align: center;">
                    @php $cond = strtoupper($item['condition'] ?? 'GOOD'); @endphp
                    @if($cond === 'GOOD')
                        <span class="cond-good">GOOD</span>
                    @elseif($cond === 'FAIR')
                        <span class="cond-fair">FAIR</span>
                    @else
                        <span class="cond-damaged">DAMAGED</span>
                    @endif
                </td>
                <td>{{ $item['notes'] ?? '-' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="4" style="text-align:center; color:#64748B;">No checklist items recorded.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @if($inspection->general_notes)
    <div style="background:#F8FAFC; border:1px solid #E2E8F0; padding:12px 14px; border-radius:8px; margin-bottom:20px;">
        <strong style="color:#0F172A; display:block; margin-bottom:4px; font-size:12px;">Inspector General Notes:</strong>
        <div style="font-size:12px; color:#475569;">{{ $inspection->general_notes }}</div>
    </div>
    @endif

    <table class="signature-grid">
        <tr>
            <td class="sig-box" style="margin-right: 15px;">
                <strong>Inspector Certification:</strong>
                <div style="font-size: 11px; color:#64748B; margin-top: 4px;">I certify that the above condition report reflects the true state of the unit on the inspection date.</div>
                <div class="sig-line">Signature: <strong>{{ $inspection->inspector_name }}</strong></div>
            </td>
            <td class="sig-box">
                <strong>Tenant Acknowledgment:</strong>
                <div style="font-size: 11px; color:#64748B; margin-top: 4px;">Tenant confirms key handover and unit condition noted above.</div>
                <div class="sig-line">Signature: <strong>{{ $inspection->tenant?->name ?? 'Tenant Acknowledged' }}</strong></div>
            </td>
        </tr>
    </table>

    <div class="footer">
        Official Digital Inspection Document • Starmax Tenant Services • app.starmaxltd.com
    </div>

</body>
</html>
