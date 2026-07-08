@extends('admin.layout')
@section('page-title', $isLandlord ? 'Landlord Dashboard' : 'Operations Dashboard')

@section('content')
@php
    $maxRevenue = max(1, $monthlyRevenue->max('amount') ?: 1);
    $pointCount = max(1, $monthlyRevenue->count());
    $trendPoints = $monthlyRevenue->values()->map(function ($month, $index) use ($pointCount, $maxRevenue) {
        $x = $pointCount === 1 ? 50 : ($index / max(1, $pointCount - 1)) * 100;
        $y = 86 - (((float) $month['amount'] / $maxRevenue) * 64);
        return round($x, 2).','.round($y, 2);
    })->implode(' ');
    $trendAreaPoints = '0,100 '.$trendPoints.' 100,100';

    $totalUnits = max(1, (int) $stats['total_units']);
    $occupiedPct = min(100, max(0, ((int) $stats['occupied_units'] / $totalUnits) * 100));
    $vacantPct = min(100, max(0, ((int) $stats['vacant_units'] / $totalUnits) * 100));
    $pendingPct = min(100, max(0, ((int) $stats['pending_tenant_invites'] / $totalUnits) * 100));

    $kpis = collect([
        ['label' => 'Rent this month', 'value' => 'KSh '.number_format($stats['collected_this_month'], 2), 'tone' => 'green', 'hint' => 'Current month collection'],
        ['label' => 'Outstanding', 'value' => 'KSh '.number_format($stats['outstanding'], 2), 'tone' => 'red', 'hint' => $stats['overdue_invoices'].' overdue invoices'],
        ['label' => 'Occupancy', 'value' => $stats['occupancy_rate'].'%', 'tone' => 'blue', 'hint' => $stats['occupied_units'].' of '.$stats['total_units'].' units occupied'],
        ['label' => 'Open maintenance', 'value' => $stats['open_maintenance'], 'tone' => 'amber', 'hint' => 'Requests needing attention'],
    ]);

    $quickStats = collect([
        !$isLandlord ? ['label' => 'Landlords', 'value' => $stats['total_landlords']] : null,
        ['label' => 'Properties', 'value' => $stats['total_properties']],
        ['label' => 'Active tenants', 'value' => $stats['total_tenants']],
        ['label' => 'Vacant units', 'value' => $stats['vacant_units']],
        ['label' => 'Pending invites', 'value' => $stats['pending_invites']],
        ['label' => 'Total collected', 'value' => 'KSh '.number_format($stats['total_paid'], 0)],
    ])->filter();

    $maintenanceCounts = [
        'New' => $maintenanceStatus->firstWhere('label', 'OPEN')['count'] ?? 0,
        'In progress' => $maintenanceStatus->firstWhere('label', 'IN PROGRESS')['count'] ?? 0,
        'Awaiting' => $maintenanceStatus->firstWhere('label', 'WAITING TENANT')['count'] ?? 0,
        'Closed' => $maintenanceStatus->firstWhere('label', 'CLOSED')['count'] ?? 0,
    ];

    $statusClass = [
        'PAID' => 'badge-green',
        'PENDING' => 'badge-yellow',
        'PARTIAL' => 'badge-blue',
        'OVERDUE' => 'badge-red',
        'CANCELLED' => 'badge-gray',
    ];
@endphp

<style>
    .ops-dashboard { margin:-2px -2px 0; }
    .ops-hero {
        display:grid;
        grid-template-columns:minmax(0,1.1fr) minmax(280px,.9fr);
        gap:16px;
        margin-bottom:16px;
    }
    .ops-hero-card {
        background:linear-gradient(135deg,#0f172a,#1e3a8a);
        color:#fff;
        border-radius:18px;
        padding:22px;
        box-shadow:0 18px 36px rgba(15,23,42,.16);
        overflow:hidden;
        position:relative;
    }
    .ops-hero-card::after {
        content:"";
        position:absolute;
        width:220px;
        height:220px;
        border-radius:999px;
        background:rgba(96,165,250,.18);
        right:-80px;
        top:-90px;
    }
    .ops-eyebrow { font-size:11px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:#bfdbfe;margin-bottom:7px; }
    .ops-title { font-size:26px;font-weight:900;letter-spacing:-.05em;position:relative;z-index:1; }
    .ops-subtitle { color:#cbd5e1;font-size:13px;line-height:1.6;margin-top:8px;max-width:650px;position:relative;z-index:1; }
    .ops-kpis {
        display:grid;
        grid-template-columns:repeat(2,minmax(0,1fr));
        gap:10px;
    }
    .ops-kpi {
        background:#fff;
        border:1px solid #dbe4ef;
        border-radius:16px;
        padding:14px;
        box-shadow:0 10px 24px rgba(15,23,42,.06);
    }
    .ops-kpi span { display:block;font-size:11px;text-transform:uppercase;letter-spacing:.04em;color:#64748b;margin-bottom:7px; }
    .ops-kpi strong { display:block;font-size:23px;line-height:1;font-weight:900;letter-spacing:-.05em; }
    .ops-kpi small { display:block;color:#64748b;font-size:12px;margin-top:8px; }
    .ops-tone-green strong { color:#15803d; }
    .ops-tone-red strong { color:#b91c1c; }
    .ops-tone-blue strong { color:#2563eb; }
    .ops-tone-amber strong { color:#b7791f; }
    .ops-stat-strip {
        display:grid;
        grid-template-columns:repeat(6,minmax(110px,1fr));
        gap:10px;
        margin-bottom:16px;
    }
    .ops-stat {
        background:#fff;
        border:1px solid #dbe4ef;
        border-radius:14px;
        padding:12px 13px;
        box-shadow:0 8px 18px rgba(15,23,42,.05);
    }
    .ops-stat span { display:block;font-size:11px;color:#64748b;margin-bottom:5px; }
    .ops-stat strong { font-size:19px;font-weight:900;letter-spacing:-.04em; }
    .ops-main-grid {
        display:grid;
        grid-template-columns:minmax(0,1.45fr) minmax(320px,.8fr);
        gap:16px;
        margin-bottom:16px;
    }
    .ops-panel {
        background:#fff;
        border:1px solid #dbe4ef;
        border-radius:16px;
        padding:17px;
        box-shadow:0 10px 24px rgba(15,23,42,.06);
        overflow:hidden;
    }
    .ops-panel-head {
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:10px;
        margin-bottom:12px;
    }
    .ops-panel-title { font-size:13px;font-weight:900;text-transform:uppercase;letter-spacing:.04em;color:#0f172a; }
    .ops-panel-note { font-size:12px;color:#64748b; }
    .trend-chart {
        position:relative;
        height:230px;
        border-radius:14px;
        background:
            linear-gradient(to bottom, rgba(148,163,184,.24) 1px, transparent 1px) 0 22px / 100% 48px,
            linear-gradient(180deg,#fff,#f8fafc);
        border:1px solid #eef2f7;
        overflow:hidden;
    }
    .trend-chart svg { position:absolute; inset:0; width:100%; height:100%; overflow:visible; }
    .trend-labels { display:flex;justify-content:space-between;padding:9px 4px 0;font-size:12px;color:#64748b; }
    .health-stack { display:grid; gap:12px; }
    .health-row { display:grid;grid-template-columns:94px 1fr 44px;gap:9px;align-items:center;font-size:12px;color:#334155; }
    .health-track { height:10px;background:#eef2f7;border-radius:999px;overflow:hidden; }
    .health-fill { height:100%;border-radius:999px; }
    .maintenance-grid { display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:13px; }
    .maintenance-box { border:1px solid #e2e8f0;border-radius:12px;padding:10px;text-align:center;background:#f8fafc; }
    .maintenance-box span { display:block;font-size:11px;color:#64748b;margin-bottom:4px; }
    .maintenance-box strong { font-size:18px;letter-spacing:-.04em; }
    .ops-table-grid { display:grid;grid-template-columns:minmax(0,1.15fr) minmax(320px,.85fr);gap:16px; }
    @media (max-width:1200px) {
        .ops-stat-strip { grid-template-columns:repeat(3,minmax(0,1fr)); }
        .ops-hero { grid-template-columns:1fr; }
    }
    @media (max-width:900px) {
        .ops-main-grid,.ops-table-grid { grid-template-columns:1fr; }
        .ops-kpis { grid-template-columns:repeat(2,minmax(0,1fr)); }
    }
    @media (max-width:560px) {
        .ops-kpis,.ops-stat-strip,.maintenance-grid { grid-template-columns:1fr; }
        .ops-title { font-size:22px; }
    }
</style>

<div class="ops-dashboard">
    <div class="ops-hero">
        <div class="ops-hero-card">
            <div class="ops-eyebrow">{{ $isLandlord ? 'Your rental portfolio' : 'Platform operations' }}</div>
            <div class="ops-title">Today’s rental health at a glance</div>
            <p class="ops-subtitle">
                Track rent collection, occupancy, invitations, invoices, and maintenance without crowding the page with too many cards.
            </p>
        </div>
        <div class="ops-kpis">
            @foreach($kpis as $kpi)
                <div class="ops-kpi ops-tone-{{ $kpi['tone'] }}">
                    <span>{{ $kpi['label'] }}</span>
                    <strong>{{ $kpi['value'] }}</strong>
                    <small>{{ $kpi['hint'] }}</small>
                </div>
            @endforeach
        </div>
    </div>

    <div class="ops-stat-strip">
        @foreach($quickStats as $stat)
            <div class="ops-stat">
                <span>{{ $stat['label'] }}</span>
                <strong>{{ $stat['value'] }}</strong>
            </div>
        @endforeach
    </div>

    <div class="ops-main-grid">
        <div class="ops-panel">
            <div class="ops-panel-head">
                <div class="ops-panel-title">Monthly rent collection</div>
                <div class="ops-panel-note">Last {{ $monthlyRevenue->count() }} months</div>
            </div>
            <div class="trend-chart">
                <svg viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true">
                    <defs>
                        <linearGradient id="rentArea" x1="0" x2="1" y1="0" y2="1">
                            <stop offset="0%" stop-color="#38bdf8" stop-opacity=".42" />
                            <stop offset="60%" stop-color="#2563eb" stop-opacity=".20" />
                            <stop offset="100%" stop-color="#9333ea" stop-opacity=".10" />
                        </linearGradient>
                    </defs>
                    <polygon points="{{ $trendAreaPoints }}" fill="url(#rentArea)" />
                    <polyline points="{{ $trendPoints }}" fill="none" stroke="#2563eb" stroke-width="2.1" vector-effect="non-scaling-stroke" />
                    @foreach($monthlyRevenue->values() as $index => $month)
                        @php
                            $x = $pointCount === 1 ? 50 : ($index / max(1, $pointCount - 1)) * 100;
                            $y = 86 - (((float) $month['amount'] / $maxRevenue) * 64);
                        @endphp
                        <circle cx="{{ $x }}" cy="{{ $y }}" r="1.8" fill="#fff" stroke="#2563eb" stroke-width=".9" vector-effect="non-scaling-stroke" />
                    @endforeach
                </svg>
            </div>
            <div class="trend-labels">
                @foreach($monthlyRevenue as $month)
                    <span>{{ $month['label'] }}</span>
                @endforeach
            </div>
        </div>

        <div class="ops-panel">
            <div class="ops-panel-head">
                <div class="ops-panel-title">Portfolio health</div>
                <div class="ops-panel-note">{{ $stats['total_units'] }} total units</div>
            </div>
            <div class="health-stack">
                <div class="health-row">
                    <span>Occupied</span>
                    <div class="health-track"><div class="health-fill" style="width:{{ $occupiedPct }}%;background:#2563eb;"></div></div>
                    <strong>{{ $stats['occupied_units'] }}</strong>
                </div>
                <div class="health-row">
                    <span>Vacant</span>
                    <div class="health-track"><div class="health-fill" style="width:{{ $vacantPct }}%;background:#14b8a6;"></div></div>
                    <strong>{{ $stats['vacant_units'] }}</strong>
                </div>
                <div class="health-row">
                    <span>Pending</span>
                    <div class="health-track"><div class="health-fill" style="width:{{ $pendingPct }}%;background:#f59e0b;"></div></div>
                    <strong>{{ $stats['pending_tenant_invites'] }}</strong>
                </div>
            </div>
            <div style="height:1px;background:#e2e8f0;margin:16px 0;"></div>
            <div class="ops-panel-title" style="margin-bottom:10px;">Maintenance</div>
            <div class="maintenance-grid">
                @foreach($maintenanceCounts as $label => $count)
                    <div class="maintenance-box">
                        <span>{{ $label }}</span>
                        <strong>{{ $count }}</strong>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="ops-table-grid">
        <div class="ops-panel">
            <div class="ops-panel-head">
                <div class="ops-panel-title">Recent invoices</div>
                <a href="{{ route('admin.invoices.index') }}" class="btn btn-secondary">View all</a>
            </div>
            <div class="table-scroll">
                <table>
                    <thead><tr><th>Tenant</th><th>Property</th><th>Amount</th><th>Status</th><th>Due</th></tr></thead>
                    <tbody>
                        @forelse($recentInvoices as $invoice)
                            <tr>
                                <td>{{ $invoice->tenant?->name ?? '-' }}</td>
                                <td>{{ $invoice->unit?->property?->name ?? '-' }} / {{ $invoice->unit?->unit_number ?? '-' }}</td>
                                <td>KSh {{ number_format((float) $invoice->total_amount, 2) }}</td>
                                <td><span class="badge {{ $statusClass[$invoice->status] ?? 'badge-gray' }}">{{ ucfirst(strtolower(str_replace('_',' ', $invoice->status))) }}</span></td>
                                <td>{{ $invoice->due_date?->format('d M Y') ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="empty-state">No invoices yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="ops-panel">
            <div class="ops-panel-head">
                <div class="ops-panel-title">Recent maintenance</div>
                <a href="{{ route('admin.maintenance.index') }}" class="btn btn-secondary">View all</a>
            </div>
            <div class="table-scroll">
                <table>
                    <thead><tr><th>Issue</th><th>Room</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse($recentMaintenance as $request)
                            <tr>
                                <td>{{ $request->title }}</td>
                                <td>{{ $request->unit?->property?->name ?? '-' }} / {{ $request->unit?->unit_number ?? '-' }}</td>
                                <td><span class="badge badge-gray">{{ str_replace('_', ' ', ucfirst(strtolower($request->status))) }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="empty-state">No maintenance requests yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
