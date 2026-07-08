@extends('admin.layout')
@section('page-title', $isLandlord ? 'Landlord Operations Dashboard' : 'Platform Operations Dashboard')

@section('content')
@php
    $maxRevenue = max(1, $monthlyRevenue->max('amount') ?: 1);
    $pointCount = max(1, $monthlyRevenue->count());
    $trendPoints = $monthlyRevenue->values()->map(function ($month, $index) use ($pointCount, $maxRevenue) {
        $x = $pointCount === 1 ? 50 : ($index / max(1, $pointCount - 1)) * 100;
        $y = 88 - (((float) $month['amount'] / $maxRevenue) * 68);
        return round($x, 2).','.round($y, 2);
    })->implode(' ');
    $trendAreaPoints = '0,100 '.$trendPoints.' 100,100';

    $totalUnits = max(1, (int) $stats['total_units']);
    $occupiedPct = min(100, max(0, ((int) $stats['occupied_units'] / $totalUnits) * 100));
    $vacantPct = min(100, max(0, ((int) $stats['vacant_units'] / $totalUnits) * 100));
    $pendingPct = min(100, max(0, ((int) $stats['pending_tenant_invites'] / $totalUnits) * 100));
    $renovationPct = 0;

    $maintenanceCounts = [
        'New' => $maintenanceStatus->firstWhere('label', 'OPEN')['count'] ?? 0,
        'In Progress' => $maintenanceStatus->firstWhere('label', 'IN PROGRESS')['count'] ?? 0,
        'Awaiting Parts' => $maintenanceStatus->firstWhere('label', 'WAITING TENANT')['count'] ?? 0,
        'Closed' => $maintenanceStatus->firstWhere('label', 'CLOSED')['count'] ?? 0,
    ];

    $metricCards = collect([
        !$isLandlord ? ['label' => 'Total Landlords', 'value' => $stats['total_landlords'], 'tone' => 'blue', 'icon' => '👤'] : null,
        ['label' => 'Pending Invites', 'value' => $stats['pending_invites'], 'tone' => 'amber', 'icon' => '⏱'],
        ['label' => 'Total Properties', 'value' => $stats['total_properties'], 'tone' => 'blue', 'icon' => '▦'],
        ['label' => 'Current Occupancy', 'value' => $stats['occupancy_rate'].'%', 'tone' => 'blue', 'icon' => '◒'],
        ['label' => 'Rent (This Month)', 'value' => 'KSh '.number_format($stats['collected_this_month'], 2), 'tone' => 'green solid', 'icon' => '💵'],
        ['label' => 'Rent (Total Collected)', 'value' => 'KSh '.number_format($stats['total_paid'], 2), 'tone' => 'green', 'icon' => '💳'],
        ['label' => 'Outstanding Balance', 'value' => 'KSh '.number_format($stats['outstanding'], 2), 'tone' => 'red', 'icon' => '▰'],
        ['label' => 'Active Tenants', 'value' => $stats['total_tenants'], 'tone' => 'teal', 'icon' => '👥'],
        ['label' => 'Vacant Units', 'value' => $stats['vacant_units'], 'tone' => 'teal', 'icon' => '□'],
        ['label' => 'Pending Invites (Tenant)', 'value' => $stats['pending_tenant_invites'], 'tone' => 'purple', 'icon' => '⏱'],
        ['label' => 'Overdue Invoices', 'value' => $stats['overdue_invoices'], 'tone' => 'red', 'icon' => '▤'],
        ['label' => 'Open Maintenance', 'value' => $stats['open_maintenance'], 'tone' => 'amber', 'icon' => '!'],
    ])->filter();

    $statusClass = [
        'PAID' => 'dash-text-green',
        'PENDING' => 'dash-text-amber',
        'PARTIAL' => 'dash-text-blue',
        'OVERDUE' => 'dash-text-red',
        'CANCELLED' => 'dash-text-muted',
    ];
@endphp

<style>
    .dash-wrap { margin:-6px -4px 0; }
    .dash-metrics {
        display:grid;
        grid-template-columns:repeat(6, minmax(118px, 1fr));
        gap:10px;
        margin-bottom:16px;
    }
    .dash-metric {
        min-height:68px;
        background:#fff;
        border:1px solid #d7dee8;
        border-radius:10px;
        padding:10px 11px;
        box-shadow:0 6px 14px rgba(15,23,42,.08);
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:10px;
    }
    .dash-metric.solid {
        background:linear-gradient(135deg,#2e9662,#238456);
        color:#fff;
        border-color:#238456;
    }
    .dash-metric-label { font-size:11px; color:#111827; margin-bottom:5px; }
    .dash-metric.solid .dash-metric-label { color:#d9fbe8; }
    .dash-metric-value { font-size:20px; line-height:1; font-weight:800; letter-spacing:-.03em; }
    .dash-metric-icon {
        width:27px;height:27px;border-radius:8px;
        display:flex;align-items:center;justify-content:center;
        font-size:15px;background:#eef2f7;opacity:.95;flex:0 0 auto;
    }
    .dash-tone-blue { color:#1d4ed8; }
    .dash-tone-green { color:#15803d; }
    .dash-tone-red { color:#b91c1c; }
    .dash-tone-amber { color:#b7791f; }
    .dash-tone-teal { color:#0f766e; }
    .dash-tone-purple { color:#7e22ce; }
    .dash-text-green { color:#15803d; }
    .dash-text-red { color:#b91c1c; }
    .dash-text-amber { color:#b7791f; }
    .dash-text-blue { color:#1d4ed8; }
    .dash-text-muted { color:#64748b; }
    .dash-grid {
        display:grid;
        grid-template-columns:minmax(0,1.35fr) minmax(340px,.95fr);
        gap:16px;
        margin-bottom:16px;
    }
    .dash-panel {
        background:#fff;
        border:1px solid #d7dee8;
        border-radius:12px;
        padding:18px 18px 14px;
        box-shadow:0 8px 18px rgba(15,23,42,.08);
        overflow:hidden;
    }
    .dash-panel-title {
        font-size:13px;
        font-weight:800;
        color:#111827;
        text-transform:uppercase;
        margin-bottom:12px;
    }
    .trend-chart {
        position:relative;
        height:170px;
        background:
            linear-gradient(to bottom, rgba(148,163,184,.25) 1px, transparent 1px) 0 18px / 100% 44px,
            linear-gradient(180deg,#fff,#f8fafc);
        border-bottom:1px solid #d7dee8;
    }
    .trend-chart svg { position:absolute; inset:0; width:100%; height:100%; overflow:visible; }
    .trend-labels { display:flex; justify-content:space-between; padding-top:8px; font-size:12px; color:#334155; }
    .trend-legend { position:absolute; top:2px; right:8px; font-size:12px; color:#111827; display:flex; align-items:center; gap:6px; }
    .legend-dot { width:9px; height:9px; border-radius:3px; background:#1d4ed8; display:inline-block; }
    .portfolio-bars {
        height:145px;
        display:grid;
        grid-template-columns:repeat(4, 1fr);
        align-items:end;
        gap:18px;
        padding:14px 18px 0 18px;
        border-bottom:1px solid #d7dee8;
        background:linear-gradient(to bottom, rgba(148,163,184,.22) 1px, transparent 1px) 0 20px / 100% 42px;
    }
    .portfolio-bar { display:flex; flex-direction:column; align-items:center; justify-content:end; gap:8px; height:100%; }
    .bar-column { width:44px; height:112px; background:#cbd5e1; display:flex; align-items:end; border-radius:2px 2px 0 0; overflow:hidden; }
    .bar-fill { width:100%; min-height:3px; }
    .portfolio-labels { display:grid; grid-template-columns:repeat(4,1fr); gap:18px; padding:8px 18px 0; font-size:11px; text-align:center; color:#111827; }
    .portfolio-summary { display:flex; justify-content:space-between; font-size:12px; margin-bottom:6px; }
    .portfolio-legend { display:grid; grid-template-columns:1fr; gap:4px; font-size:11px; margin-top:8px; }
    .dash-table th { padding:8px 10px; font-size:12px; color:#111827; background:#f8fafc; border-bottom:1px solid #d7dee8; text-transform:none; }
    .dash-table td { padding:8px 10px; font-size:13px; }
    .maintenance-strip { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:12px; }
    .maintenance-chip { text-align:center; }
    .maintenance-chip span { display:block; font-size:12px; color:#111827; margin-bottom:4px; }
    .maintenance-chip strong { font-size:18px; }
    @media (max-width: 1250px) {
        .dash-metrics { grid-template-columns:repeat(4, minmax(118px, 1fr)); }
    }
    @media (max-width: 980px) {
        .dash-grid { grid-template-columns:1fr; }
        .dash-metrics { grid-template-columns:repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 560px) {
        .dash-metrics { grid-template-columns:1fr; }
        .dash-metric { min-height:66px; }
        .maintenance-strip { grid-template-columns:repeat(2,1fr); }
    }
</style>

<div class="dash-wrap">
    <div class="dash-metrics">
        @foreach($metricCards as $card)
            @php
                $parts = explode(' ', $card['tone']);
                $tone = $parts[0];
                $solid = in_array('solid', $parts, true);
            @endphp
            <div class="dash-metric {{ $solid ? 'solid' : '' }}">
                <div>
                    <div class="dash-metric-label">{{ $card['label'] }}</div>
                    <div class="dash-metric-value {{ $solid ? '' : 'dash-tone-'.$tone }}">{{ $card['value'] }}</div>
                </div>
                <div class="dash-metric-icon {{ $solid ? '' : 'dash-tone-'.$tone }}">{{ $card['icon'] }}</div>
            </div>
        @endforeach
    </div>

    <div class="dash-grid">
        <div class="dash-panel">
            <div class="dash-panel-title">Monthly Rent Collection Trend</div>
            <div class="trend-chart">
                <div class="trend-legend"><span class="legend-dot"></span> Rent Collected</div>
                <svg viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true">
                    <defs>
                        <linearGradient id="rentArea" x1="0" x2="1" y1="0" y2="1">
                            <stop offset="0%" stop-color="#0ea5e9" stop-opacity=".38" />
                            <stop offset="55%" stop-color="#6366f1" stop-opacity=".24" />
                            <stop offset="100%" stop-color="#9333ea" stop-opacity=".12" />
                        </linearGradient>
                    </defs>
                    <polygon points="{{ $trendAreaPoints }}" fill="url(#rentArea)" />
                    <polyline points="{{ $trendPoints }}" fill="none" stroke="#2563eb" stroke-width="1.8" vector-effect="non-scaling-stroke" />
                    @foreach($monthlyRevenue->values() as $index => $month)
                        @php
                            $x = $pointCount === 1 ? 50 : ($index / max(1, $pointCount - 1)) * 100;
                            $y = 88 - (((float) $month['amount'] / $maxRevenue) * 68);
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

        <div class="dash-panel">
            <div class="dash-panel-title">Portfolio Health</div>
            <div class="portfolio-summary">
                <span>Occupied Units: {{ $stats['occupied_units'] }}/{{ $stats['total_units'] }}</span>
                <span>Total Units: {{ $stats['total_units'] }}</span>
            </div>
            <div style="display:grid;grid-template-columns:1fr 120px;gap:10px;align-items:start;">
                <div>
                    <div class="portfolio-bars">
                        <div class="portfolio-bar"><div class="bar-column"><div class="bar-fill" style="height:{{ $occupiedPct }}%;background:#2563eb;"></div></div></div>
                        <div class="portfolio-bar"><div class="bar-column"><div class="bar-fill" style="height:{{ $vacantPct }}%;background:#94a3b8;"></div></div></div>
                        <div class="portfolio-bar"><div class="bar-column"><div class="bar-fill" style="height:{{ $pendingPct }}%;background:#f59e0b;"></div></div></div>
                        <div class="portfolio-bar"><div class="bar-column"><div class="bar-fill" style="height:{{ $renovationPct }}%;background:#b91c1c;"></div></div></div>
                    </div>
                    <div class="portfolio-labels">
                        <span>Occupied</span><span>Vacant</span><span>Pending</span><span>Under</span>
                    </div>
                </div>
                <div class="portfolio-legend">
                    <strong>Unit Status</strong>
                    <span><i class="legend-dot" style="background:#2563eb;"></i> Occupied</span>
                    <span><i class="legend-dot" style="background:#94a3b8;"></i> Vacant</span>
                    <span><i class="legend-dot" style="background:#f59e0b;"></i> Pending Tenant</span>
                    <span><i class="legend-dot" style="background:#b91c1c;"></i> Under Renovation</span>
                </div>
            </div>
        </div>
    </div>

    <div class="dash-grid">
        <div class="dash-panel">
            <div class="dash-panel-title">Recent Invoices</div>
            <table class="dash-table">
                <thead><tr><th>Tenant</th><th>Property</th><th>Amount KSh</th><th>Status</th><th>Due Date</th></tr></thead>
                <tbody>
                    @forelse($recentInvoices as $invoice)
                        <tr>
                            <td>{{ $invoice->tenant?->name ?? '-' }}</td>
                            <td>{{ $invoice->unit?->property?->name ?? '-' }} / {{ $invoice->unit?->unit_number ?? '-' }}</td>
                            <td>{{ number_format((float) $invoice->total_amount, 2) }}</td>
                            <td class="{{ $statusClass[$invoice->status] ?? 'dash-text-muted' }}">{{ ucfirst(strtolower(str_replace('_',' ', $invoice->status))) }}</td>
                            <td>{{ $invoice->due_date?->format('d/m/Y') ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty-state">No invoices yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="dash-panel">
            <div class="dash-panel-title">Maintenance Status</div>
            <div class="maintenance-strip">
                @foreach($maintenanceCounts as $label => $count)
                    <div class="maintenance-chip">
                        <span>{{ $label }}</span>
                        <strong class="{{ $label === 'New' ? 'dash-text-red' : ($label === 'Awaiting Parts' || $label === 'Closed' ? 'dash-text-green' : 'dash-text-amber') }}">{{ $count }}</strong>
                    </div>
                @endforeach
            </div>
            <table class="dash-table">
                <thead><tr><th>Issue</th><th>Apartment / Room</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse($recentMaintenance as $request)
                        <tr>
                            <td>{{ $request->title }}</td>
                            <td>{{ $request->unit?->property?->name ?? '-' }} / {{ $request->unit?->unit_number ?? '-' }}</td>
                            <td>{{ str_replace('_', ' ', ucfirst(strtolower($request->status))) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="empty-state">No maintenance requests yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
