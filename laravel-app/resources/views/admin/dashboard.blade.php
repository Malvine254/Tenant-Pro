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

    $heroHighlights = collect([
        ['label' => 'Occupied units', 'value' => $stats['occupied_units'].' / '.$stats['total_units']],
        ['label' => 'Overdue invoices', 'value' => $stats['overdue_invoices']],
        ['label' => 'Open maintenance', 'value' => $stats['open_maintenance']],
    ]);

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
        background:linear-gradient(145deg,#0b1324,#1e3a8a 58%,#2563eb);
        color:#fff;
        border-radius:18px;
        padding:20px 22px 18px;
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
    .ops-title { font-size:25px;font-weight:900;letter-spacing:-.05em;position:relative;z-index:1; }
    .ops-subtitle { color:#dbeafe;font-size:13px;line-height:1.6;margin-top:8px;max-width:650px;position:relative;z-index:1; }
    .ops-hero-meta {
        position:relative;
        z-index:1;
        display:flex;
        gap:8px;
        flex-wrap:wrap;
        margin-top:13px;
    }
    .ops-hero-chip {
        display:flex;
        gap:6px;
        align-items:center;
        border:1px solid rgba(191,219,254,.35);
        background:rgba(15,23,42,.34);
        color:#e2e8f0;
        border-radius:999px;
        padding:6px 10px;
        font-size:12px;
        line-height:1;
    }
    .ops-hero-chip strong {
        color:#fff;
        font-size:12px;
        letter-spacing:-.02em;
    }
    .ops-actions {
        display:flex;
        gap:8px;
        margin-top:14px;
        flex-wrap:wrap;
        position:relative;
        z-index:1;
    }
    .ops-action-btn {
        border:1px solid rgba(191,219,254,.45);
        background:rgba(30,64,175,.32);
        color:#eff6ff;
        border-radius:10px;
        padding:7px 10px;
        font-size:12px;
        font-weight:700;
        letter-spacing:.01em;
    }
    .ops-action-btn:hover { background:rgba(37,99,235,.5); color:#fff; }
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
    .ops-chart-grid { display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px;margin-bottom:16px; }
    .ops-chart-card canvas { width:100% !important;height:240px !important; }
    .ops-subscription-grid { display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;margin-top:12px; }
    .ops-subscription-pill { border:1px solid #dbe4ef;border-radius:12px;padding:10px;background:#f8fafc; }
    .ops-subscription-pill span { display:block;font-size:11px;color:#64748b;margin-bottom:4px;text-transform:uppercase;letter-spacing:.04em; }
    .ops-subscription-pill strong { font-size:20px;font-weight:900;letter-spacing:-.04em; }
    @media (max-width:1200px) {
        .ops-stat-strip { grid-template-columns:repeat(3,minmax(0,1fr)); }
        .ops-hero { grid-template-columns:1fr; }
        .ops-chart-grid { grid-template-columns:1fr; }
    }
    @media (max-width:900px) {
        .ops-main-grid,.ops-table-grid { grid-template-columns:1fr; }
        .ops-kpis { grid-template-columns:repeat(2,minmax(0,1fr)); }
    }
    @media (max-width:560px) {
        .ops-kpis,.ops-stat-strip,.maintenance-grid { grid-template-columns:1fr; }
        .ops-title { font-size:22px; }
        .ops-actions { display:grid;grid-template-columns:1fr 1fr; }
    }
</style>

<div class="ops-dashboard">
    <div class="ops-hero">
        <div class="ops-hero-card">
            <div class="ops-eyebrow">{{ $isLandlord ? 'Your rental portfolio' : 'Platform operations' }}</div>
            <div class="ops-title">Dashboard snapshot for {{ now()->format('D, d M Y') }}</div>
            <p class="ops-subtitle">
                Keep rent, occupancy, and maintenance decisions in one place with immediate shortcuts to daily actions.
            </p>
            <div class="ops-hero-meta">
                @foreach($heroHighlights as $highlight)
                    <div class="ops-hero-chip">
                        <span>{{ $highlight['label'] }}:</span>
                        <strong>{{ $highlight['value'] }}</strong>
                    </div>
                @endforeach
            </div>
            <div class="ops-actions">
                <a href="{{ route('admin.properties.index') }}" class="ops-action-btn">Manage properties</a>
                <a href="{{ route('admin.invoices.index') }}" class="ops-action-btn">Review invoices</a>
                <a href="{{ route('admin.chats.index') }}" class="ops-action-btn">Open maintenance chats</a>
            </div>
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
                <a href="{{ route('admin.chats.index') }}" class="btn btn-secondary">Open chats</a>
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

    <div class="ops-chart-grid">
        <div class="ops-panel ops-chart-card">
            <div class="ops-panel-head">
                <div class="ops-panel-title">Revenue trend</div>
                <div class="ops-panel-note">Interactive monthly line</div>
            </div>
            <canvas id="revenueTrendChart"></canvas>
        </div>

        <div class="ops-panel ops-chart-card">
            <div class="ops-panel-head">
                <div class="ops-panel-title">Invoice mix</div>
                <div class="ops-panel-note">Paid vs open balances</div>
            </div>
            <canvas id="invoiceMixChart"></canvas>
        </div>

        <div class="ops-panel ops-chart-card">
            <div class="ops-panel-head">
                <div class="ops-panel-title">Maintenance flow</div>
                <div class="ops-panel-note">Request resolution pipeline</div>
            </div>
            <canvas id="maintenanceChart"></canvas>
        </div>
    </div>

    @unless($isLandlord)
        <div class="ops-panel" style="margin-bottom:16px;">
            <div class="ops-panel-head">
                <div class="ops-panel-title">Landlord subscription lifecycle</div>
                <div class="ops-panel-note">1 month free trial then paid service</div>
            </div>
            <div class="ops-subscription-grid">
                <div class="ops-subscription-pill"><span>Trial</span><strong style="color:#1d4ed8;">{{ $landlordSubscription['trial'] ?? 0 }}</strong></div>
                <div class="ops-subscription-pill"><span>Active Paid</span><strong style="color:#15803d;">{{ $landlordSubscription['active'] ?? 0 }}</strong></div>
                <div class="ops-subscription-pill"><span>Past Due</span><strong style="color:#b91c1c;">{{ $landlordSubscription['past_due'] ?? 0 }}</strong></div>
                <div class="ops-subscription-pill"><span>Not Required</span><strong style="color:#475569;">{{ $landlordSubscription['not_required'] ?? 0 }}</strong></div>
            </div>
        </div>
    @endunless
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
(() => {
    const payload = @json($chartSeries);
    const labels = payload.monthlyRevenueLabels || [];
    const revenue = payload.monthlyRevenueValues || [];

    const baseOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                labels: {
                    boxWidth: 12,
                    boxHeight: 12,
                    usePointStyle: true,
                    color: '#334155',
                    font: { size: 11, weight: '600' },
                }
            }
        },
        scales: {
            x: {
                grid: { color: 'rgba(148,163,184,.16)' },
                ticks: { color: '#475569', font: { size: 11 } },
            },
            y: {
                beginAtZero: true,
                grid: { color: 'rgba(148,163,184,.16)' },
                ticks: { color: '#475569', font: { size: 11 } },
            }
        }
    };

    const revenueCtx = document.getElementById('revenueTrendChart');
    if (revenueCtx) {
        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels,
                datasets: [{
                    label: 'Revenue (KSh)',
                    data: revenue,
                    borderColor: '#2563eb',
                    backgroundColor: 'rgba(37, 99, 235, .14)',
                    borderWidth: 3,
                    tension: .35,
                    fill: true,
                    pointRadius: 4,
                    pointBackgroundColor: '#1d4ed8',
                }]
            },
            options: baseOptions,
        });
    }

    const invoiceCtx = document.getElementById('invoiceMixChart');
    if (invoiceCtx) {
        new Chart(invoiceCtx, {
            type: 'doughnut',
            data: {
                labels: payload.invoiceStatusLabels || [],
                datasets: [{
                    data: payload.invoiceStatusValues || [],
                    backgroundColor: ['#f59e0b', '#0ea5e9', '#16a34a', '#dc2626'],
                    borderColor: '#ffffff',
                    borderWidth: 2,
                    hoverOffset: 6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '58%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 11,
                            boxHeight: 11,
                            color: '#334155',
                            font: { size: 11, weight: '600' },
                        }
                    }
                }
            }
        });
    }

    const maintenanceCtx = document.getElementById('maintenanceChart');
    if (maintenanceCtx) {
        new Chart(maintenanceCtx, {
            type: 'bar',
            data: {
                labels: payload.maintenanceStatusLabels || [],
                datasets: [{
                    label: 'Requests',
                    data: payload.maintenanceStatusValues || [],
                    backgroundColor: ['#1d4ed8', '#f59e0b', '#16a34a', '#475569'],
                    borderRadius: 8,
                    borderSkipped: false,
                }]
            },
            options: {
                ...baseOptions,
                plugins: {
                    ...baseOptions.plugins,
                    legend: { display: false },
                },
            },
        });
    }
})();
</script>
@endsection
