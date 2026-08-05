@extends('admin.layout')
@section('page-title', $isLandlord ? 'Landlord Dashboard' : 'Operations Dashboard')

@section('content')
@php
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
    .ops-insight-grid {
        display:grid;
        grid-template-columns:repeat(2,minmax(0,1fr));
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
    .ops-chart-card canvas { width:100% !important;height:260px !important; }
    .ops-mini-table {
        width:100%;
        border-collapse:collapse;
        font-size:13px;
    }
    .ops-mini-table th,
    .ops-mini-table td {
        border-bottom:1px solid #e2e8f0;
        padding:10px 8px;
        text-align:left;
    }
    .ops-mini-table th { color:#64748b;font-weight:700;font-size:12px;text-transform:uppercase;letter-spacing:.03em; }
    .ops-mini-table td:last-child,
    .ops-mini-table th:last-child { text-align:right; }
    .ops-table-grid { display:grid;grid-template-columns:minmax(0,1.15fr) minmax(320px,.85fr);gap:16px; }
    .ops-chart-grid { display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px;margin-bottom:16px; }
    .ops-subscription-grid { display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;margin-top:12px; }
    .ops-subscription-pill { border:1px solid #dbe4ef;border-radius:12px;padding:10px;background:#f8fafc; }
    .ops-subscription-pill span { display:block;font-size:11px;color:#64748b;margin-bottom:4px;text-transform:uppercase;letter-spacing:.04em; }
    .ops-subscription-pill strong { font-size:20px;font-weight:900;letter-spacing:-.04em; }
    .ops-landlord-kpis {
        display:grid;
        grid-template-columns:repeat(5,minmax(0,1fr));
        gap:10px;
        margin-bottom:16px;
    }
    .ops-landlord-kpi {
        border:1px solid #dbe4ef;
        border-radius:12px;
        padding:10px;
        background:#f8fafc;
    }
    .ops-landlord-kpi span { display:block;font-size:11px;color:#64748b;margin-bottom:4px;text-transform:uppercase;letter-spacing:.04em; }
    .ops-landlord-kpi strong { font-size:20px;font-weight:900;letter-spacing:-.04em;color:#0f172a; }
    .ops-landlord-kpi small { display:block;color:#64748b;font-size:12px;margin-top:4px; }
    .ops-landlord-name { font-weight:700;color:#0f172a; }
    .ops-landlord-health {
        display:inline-flex;
        align-items:center;
        border-radius:999px;
        padding:3px 8px;
        font-size:11px;
        font-weight:700;
        text-transform:uppercase;
        letter-spacing:.03em;
    }
    .ops-health-healthy { background:#dcfce7;color:#166534; }
    .ops-health-attention { background:#fef3c7;color:#92400e; }
    .ops-health-risk { background:#fee2e2;color:#991b1b; }
    @media (max-width:1200px) {
        .ops-stat-strip { grid-template-columns:repeat(3,minmax(0,1fr)); }
        .ops-hero { grid-template-columns:1fr; }
        .ops-chart-grid { grid-template-columns:1fr; }
        .ops-landlord-kpis { grid-template-columns:repeat(3,minmax(0,1fr)); }
    }
    @media (max-width:900px) {
        .ops-insight-grid,.ops-table-grid { grid-template-columns:1fr; }
        .ops-kpis { grid-template-columns:repeat(2,minmax(0,1fr)); }
        .ops-landlord-kpis { grid-template-columns:repeat(2,minmax(0,1fr)); }
    }
    @media (max-width:560px) {
        .ops-kpis,.ops-stat-strip { grid-template-columns:1fr; }
        .ops-title { font-size:22px; }
        .ops-actions { display:grid;grid-template-columns:1fr 1fr; }
        .ops-landlord-kpis { grid-template-columns:1fr; }
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

    <div class="ops-insight-grid">
        <div class="ops-panel">
            <div class="ops-panel-head">
                <div class="ops-panel-title">Portfolio occupancy split</div>
                <div class="ops-panel-note">Unit distribution</div>
            </div>
            <canvas id="portfolioSplitChart"></canvas>
        </div>

        <div class="ops-panel">
            <div class="ops-panel-head">
                <div class="ops-panel-title">Collection cash position</div>
                <div class="ops-panel-note">Collected vs outstanding</div>
            </div>
            <canvas id="cashPositionChart"></canvas>
        </div>
    </div>

    <div class="ops-insight-grid">
        <div class="ops-panel">
            <div class="ops-panel-head">
                <div class="ops-panel-title">Monthly collection table</div>
                <div class="ops-panel-note">Last {{ $monthlyRevenue->count() }} months</div>
            </div>
            <div class="table-scroll">
                <table class="ops-mini-table">
                    <thead>
                        <tr><th>Month</th><th>Amount</th></tr>
                    </thead>
                    <tbody>
                        @forelse($monthlyRevenue as $month)
                            <tr>
                                <td>{{ $month['label'] }}</td>
                                <td>KSh {{ number_format((float) $month['amount'], 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="empty-state">No monthly collection data yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="ops-panel">
            <div class="ops-panel-head">
                <div class="ops-panel-title">Maintenance status table</div>
                <div class="ops-panel-note">Open to resolved flow</div>
            </div>
            <div class="table-scroll">
                <table class="ops-mini-table">
                    <thead>
                        <tr><th>Status</th><th>Count</th></tr>
                    </thead>
                    <tbody>
                        @forelse($maintenanceStatus as $status)
                            <tr>
                                <td>{{ ucfirst(strtolower((string) ($status['label'] ?? '-'))) }}</td>
                                <td>{{ $status['count'] ?? 0 }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="empty-state">No maintenance status data yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
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

        @if($isSuperAdmin)
            <div class="ops-panel" style="margin-bottom:16px;">
                <div class="ops-panel-head">
                    <div class="ops-panel-title">Landlord performance command center</div>
                    <div class="ops-panel-note">Operational risk and collections visibility</div>
                </div>
                <div class="ops-landlord-kpis">
                    <div class="ops-landlord-kpi">
                        <span>Total landlords</span>
                        <strong>{{ $superAdminLandlordStats['total_landlords'] ?? 0 }}</strong>
                    </div>
                    <div class="ops-landlord-kpi">
                        <span>Active paid landlords</span>
                        <strong style="color:#15803d;">{{ $superAdminLandlordStats['active_paid_landlords'] ?? 0 }}</strong>
                    </div>
                    <div class="ops-landlord-kpi">
                        <span>Past due landlords</span>
                        <strong style="color:#b91c1c;">{{ $superAdminLandlordStats['past_due_landlords'] ?? 0 }}</strong>
                    </div>
                    <div class="ops-landlord-kpi">
                        <span>With overdue invoices</span>
                        <strong style="color:#b7791f;">{{ $superAdminLandlordStats['landlords_with_overdue_invoices'] ?? 0 }}</strong>
                    </div>
                    <div class="ops-landlord-kpi">
                        <span>Avg monthly collection</span>
                        <strong>KSh {{ number_format((float) ($superAdminLandlordStats['avg_monthly_collection_per_landlord'] ?? 0), 2) }}</strong>
                        <small>Per landlord this month</small>
                    </div>
                </div>

                <div class="ops-insight-grid" style="margin-bottom:0;">
                    <div class="ops-panel" style="box-shadow:none;">
                        <div class="ops-panel-head">
                            <div class="ops-panel-title">Top landlord cashflow</div>
                            <div class="ops-panel-note">Collection vs outstanding</div>
                        </div>
                        <canvas id="landlordPerformanceChart"></canvas>
                    </div>

                    <div class="ops-panel" style="box-shadow:none;">
                        <div class="ops-panel-head">
                            <div class="ops-panel-title">Landlord health split</div>
                            <div class="ops-panel-note">Healthy vs attention vs risk</div>
                        </div>
                        <canvas id="landlordHealthChart"></canvas>
                    </div>
                </div>

                <div class="ops-panel" style="box-shadow:none;margin-top:16px;">
                    <div class="ops-panel-head">
                        <div class="ops-panel-title">Landlord leaderboard</div>
                        <div class="ops-panel-note">Sorted by monthly collection</div>
                    </div>
                    <div class="table-scroll">
                        <table>
                            <thead>
                                <tr>
                                    <th>Landlord</th>
                                    <th>Units</th>
                                    <th>Occupancy</th>
                                    <th>Collected</th>
                                    <th>Outstanding</th>
                                    <th>Overdue</th>
                                    <th>Health</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($landlordPerformance->take(10) as $landlord)
                                    <tr>
                                        <td class="ops-landlord-name">{{ $landlord['name'] }}</td>
                                        <td>{{ $landlord['units'] }}</td>
                                        <td>{{ $landlord['occupancy_rate'] }}%</td>
                                        <td>KSh {{ number_format((float) $landlord['monthly_collection'], 2) }}</td>
                                        <td>KSh {{ number_format((float) $landlord['outstanding'], 2) }}</td>
                                        <td>{{ $landlord['overdue_invoices'] }}</td>
                                        <td>
                                            <span class="ops-landlord-health ops-health-{{ $landlord['health'] === 'risk' ? 'risk' : ($landlord['health'] === 'attention' ? 'attention' : 'healthy') }}">
                                                {{ $landlord['health'] === 'risk' ? 'At risk' : ($landlord['health'] === 'attention' ? 'Needs attention' : 'Healthy') }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="empty-state">No landlord performance data yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
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

    const portfolioSplitCtx = document.getElementById('portfolioSplitChart');
    if (portfolioSplitCtx) {
        new Chart(portfolioSplitCtx, {
            type: 'doughnut',
            data: {
                labels: ['Occupied', 'Vacant', 'Pending invites'],
                datasets: [{
                    data: [
                        {{ (int) $stats['occupied_units'] }},
                        {{ (int) $stats['vacant_units'] }},
                        {{ (int) $stats['pending_tenant_invites'] }}
                    ],
                    backgroundColor: ['#2563eb', '#14b8a6', '#f59e0b'],
                    borderColor: '#ffffff',
                    borderWidth: 2,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '56%',
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

    const cashPositionCtx = document.getElementById('cashPositionChart');
    if (cashPositionCtx) {
        new Chart(cashPositionCtx, {
            type: 'bar',
            data: {
                labels: ['Collected', 'Outstanding'],
                datasets: [{
                    label: 'KSh',
                    data: [{{ (float) $stats['total_paid'] }}, {{ (float) $stats['outstanding'] }}],
                    backgroundColor: ['#16a34a', '#dc2626'],
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
            }
        });
    }

    const landlordPerformanceCtx = document.getElementById('landlordPerformanceChart');
    if (landlordPerformanceCtx) {
        new Chart(landlordPerformanceCtx, {
            type: 'bar',
            data: {
                labels: payload.landlordPerformanceLabels || [],
                datasets: [
                    {
                        label: 'Collected',
                        data: payload.landlordPerformanceCollectionValues || [],
                        backgroundColor: '#16a34a',
                        borderRadius: 7,
                        borderSkipped: false,
                    },
                    {
                        label: 'Outstanding',
                        data: payload.landlordPerformanceOutstandingValues || [],
                        backgroundColor: '#dc2626',
                        borderRadius: 7,
                        borderSkipped: false,
                    }
                ]
            },
            options: {
                ...baseOptions,
                plugins: {
                    ...baseOptions.plugins,
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
            },
        });
    }

    const landlordHealthCtx = document.getElementById('landlordHealthChart');
    if (landlordHealthCtx) {
        new Chart(landlordHealthCtx, {
            type: 'doughnut',
            data: {
                labels: payload.landlordHealthLabels || [],
                datasets: [{
                    data: payload.landlordHealthValues || [],
                    backgroundColor: ['#16a34a', '#f59e0b', '#dc2626'],
                    borderColor: '#fff',
                    borderWidth: 2,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '62%',
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
})();
</script>
@endsection
