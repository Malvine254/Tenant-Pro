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

    $superAdminExecutiveStats = collect([
        ['label' => 'Total landlords', 'value' => $superAdminLandlordStats['total_landlords'] ?? 0, 'tone' => 'blue'],
        ['label' => 'Active paid landlords', 'value' => $superAdminLandlordStats['active_paid_landlords'] ?? 0, 'tone' => 'green'],
        ['label' => 'Past due landlords', 'value' => $superAdminLandlordStats['past_due_landlords'] ?? 0, 'tone' => 'red'],
        ['label' => 'Properties', 'value' => $stats['total_properties'], 'tone' => 'blue'],
        ['label' => 'Active tenants', 'value' => $stats['total_tenants'], 'tone' => 'green'],
        ['label' => 'Occupied units', 'value' => $stats['occupied_units'].' / '.$stats['total_units'], 'tone' => 'blue'],
        ['label' => 'Collected this month', 'value' => 'KSh '.number_format((float) $stats['collected_this_month'], 2), 'tone' => 'green'],
        ['label' => 'Outstanding', 'value' => 'KSh '.number_format((float) $stats['outstanding'], 2), 'tone' => 'red'],
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
    .ops-dashboard {
        margin: -2px -2px 0;
        color: #e2e8f0;
    }
    .dashboard-welcome {
        display:flex;align-items:flex-start;justify-content:space-between;gap:18px;margin-bottom:16px;
    }
    .dashboard-welcome h1 { font-size:25px;line-height:1.2;letter-spacing:-.04em;color:#f8fafc;margin-bottom:5px; }
    .dashboard-welcome p { color:#94a3b8;font-size:13px;max-width:680px; }
    .dashboard-quick-actions { display:flex;align-items:center;justify-content:flex-end;gap:8px;flex-wrap:wrap; }

    .dashboard-tabs {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin: 0 0 16px;
        padding: 6px;
        background: rgba(15, 23, 42, 0.9);
        border: 1px solid rgba(148, 163, 184, 0.18);
        border-radius: 14px;
        box-shadow: 0 12px 30px rgba(2, 6, 23, 0.18);
    }

    .dashboard-tab {
        appearance: none;
        border: 1px solid rgba(148, 163, 184, 0.16);
        background: transparent;
        color: #cbd5e1;
        border-radius: 10px;
        padding: 8px 12px;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .dashboard-tab:hover,
    .dashboard-tab.active {
        background: linear-gradient(135deg, rgba(37, 99, 235, 0.24), rgba(59, 130, 246, 0.14));
        border-color: rgba(96, 165, 250, 0.48);
        color: #f8fafc;
    }

    .dashboard-tab-panel {
        display: none;
    }

    .dashboard-tab-panel.active {
        display: block;
    }

    .ops-kpis {
        display:grid;
        grid-template-columns:repeat(2,minmax(0,1fr));
        gap:10px;
    }
    .ops-kpi {
        background:linear-gradient(180deg, rgba(15, 23, 42, 0.9), rgba(15, 23, 42, 0.72));
        border:1px solid rgba(148,163,184,0.18);
        border-radius:16px;
        padding:14px;
        box-shadow:0 10px 24px rgba(2,6,23,.18);
    }
    .ops-kpi span { display:block;font-size:11px;text-transform:uppercase;letter-spacing:.04em;color:#94a3b8;margin-bottom:7px; }
    .ops-kpi strong { display:block;font-size:23px;line-height:1;font-weight:900;letter-spacing:-.05em;color:#f8fafc; }
    .ops-kpi small { display:block;color:#94a3b8;font-size:12px;margin-top:8px; }
    .ops-tone-green strong { color:#4ade80; }
    .ops-tone-red strong { color:#f87171; }
    .ops-tone-blue strong { color:#7dd3fc; }
    .ops-tone-amber strong { color:#fbbf24; }
    .ops-stat-strip {
        display:grid;
        grid-template-columns:repeat(6,minmax(110px,1fr));
        gap:10px;
        margin-bottom:16px;
    }
    .ops-stat {
        background:linear-gradient(180deg, rgba(15,23,42,.9), rgba(15,23,42,.72));
        border:1px solid rgba(148,163,184,0.18);
        border-radius:14px;
        padding:12px 13px;
        box-shadow:0 8px 18px rgba(2,6,23,.16);
    }
    .ops-stat span { display:block;font-size:11px;color:#94a3b8;margin-bottom:5px; }
    .ops-stat strong { font-size:19px;font-weight:900;letter-spacing:-.04em;color:#f8fafc; }
    .ops-insight-grid {
        display:grid;
        grid-template-columns:repeat(2,minmax(0,1fr));
        gap:16px;
        margin-bottom:16px;
    }
    .ops-panel {
        background:linear-gradient(180deg, rgba(15,23,42,.92), rgba(15,23,42,.72));
        border:1px solid rgba(148,163,184,0.18);
        border-radius:16px;
        padding:17px;
        box-shadow:0 10px 24px rgba(2,6,23,.18);
        overflow:hidden;
    }
    .ops-panel-head {
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:10px;
        margin-bottom:12px;
    }
    .ops-panel-title { font-size:13px;font-weight:900;text-transform:uppercase;letter-spacing:.04em;color:#f8fafc; }
    .ops-panel-note { font-size:12px;color:#94a3b8; }
    .ops-chart-host {
        width:100%;
        height:220px;
        min-height:220px;
        max-height:220px;
    }
    .ops-mini-table {
        width:100%;
        border-collapse:collapse;
        font-size:13px;
    }
    .ops-mini-table th,
    .ops-mini-table td {
        border-bottom:1px solid rgba(148,163,184,.15);
        padding:10px 8px;
        text-align:left;
        color:#e2e8f0;
    }
    .ops-mini-table th { color:#cbd5e1;font-weight:700;font-size:12px;text-transform:uppercase;letter-spacing:.03em; }
    .ops-mini-table td:last-child,
    .ops-mini-table th:last-child { text-align:right; }
    .ops-table-grid { display:grid;grid-template-columns:minmax(0,1.15fr) minmax(320px,.85fr);gap:16px; }
    .ops-chart-grid { display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px;margin-bottom:16px; }
    .ops-subscription-grid { display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;margin-top:12px; }
    .ops-subscription-pill { border:1px solid rgba(148,163,184,.18);border-radius:12px;padding:10px;background:rgba(15,23,42,.68); }
    .ops-subscription-pill span { display:block;font-size:11px;color:#94a3b8;margin-bottom:4px;text-transform:uppercase;letter-spacing:.04em; }
    .ops-subscription-pill strong { font-size:20px;font-weight:900;letter-spacing:-.04em;color:#f8fafc; }
    .ops-landlord-kpis {
        display:grid;
        grid-template-columns:repeat(5,minmax(0,1fr));
        gap:10px;
        margin-bottom:16px;
    }
    .ops-landlord-kpi {
        border:1px solid rgba(148,163,184,.18);
        border-radius:12px;
        padding:10px;
        background:rgba(15,23,42,.68);
    }
    .ops-landlord-kpi span { display:block;font-size:11px;color:#94a3b8;margin-bottom:4px;text-transform:uppercase;letter-spacing:.04em; }
    .ops-landlord-kpi strong { font-size:20px;font-weight:900;letter-spacing:-.04em;color:#f8fafc; }
    .ops-landlord-kpi small { display:block;color:#94a3b8;font-size:12px;margin-top:4px; }
    .ops-landlord-name { font-weight:700;color:#f8fafc; }
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
    .ops-health-healthy { background:#14532d;color:#bbf7d0; }
    .ops-health-attention { background:#78350f;color:#fef3c7; }
    .ops-health-risk { background:#7f1d1d;color:#fee2e2; }
    .subscription-notice {
        display:grid;
        grid-template-columns:minmax(0,1fr) auto;
        gap:18px;
        align-items:center;
        margin-bottom:16px;
        padding:18px;
        border-radius:16px;
        border:1px solid rgba(251,191,36,.34);
        background:linear-gradient(135deg,rgba(120,53,15,.4),rgba(15,23,42,.92));
        box-shadow:0 14px 30px rgba(2,6,23,.22);
    }
    .subscription-notice.locked { border-color:rgba(248,113,113,.42);background:linear-gradient(135deg,rgba(127,29,29,.46),rgba(15,23,42,.94)); }
    .subscription-notice h2 { font-size:18px;color:#fff;margin-bottom:6px; }
    .subscription-notice p { color:#cbd5e1;line-height:1.55;font-size:13px;max-width:780px; }
    .subscription-notice-meta { display:grid;gap:5px;min-width:190px;padding:12px 14px;border-radius:12px;background:rgba(2,6,23,.36); }
    .subscription-notice-meta span { color:#94a3b8;font-size:11px;text-transform:uppercase;letter-spacing:.04em; }
    .subscription-notice-meta strong { color:#f8fafc;font-size:15px; }
    .ops-exec-grid {
        display:grid;
        grid-template-columns:repeat(4,minmax(0,1fr));
        gap:10px;
        margin-bottom:16px;
    }
    .ops-exec-card {
        background:linear-gradient(180deg, rgba(15,23,42,.9), rgba(15,23,42,.72));
        border:1px solid rgba(148,163,184,0.18);
        border-radius:12px;
        padding:12px;
        box-shadow:0 8px 18px rgba(2,6,23,.16);
    }
    .ops-exec-card span {
        display:block;
        font-size:11px;
        color:#94a3b8;
        margin-bottom:6px;
        text-transform:uppercase;
        letter-spacing:.04em;
    }
    .ops-exec-card strong {
        font-size:21px;
        font-weight:900;
        letter-spacing:-.04em;
        line-height:1.1;
        color:#f8fafc;
    }
    .ops-exec-tone-green strong { color:#4ade80; }
    .ops-exec-tone-red strong { color:#f87171; }
    .ops-exec-tone-blue strong { color:#7dd3fc; }
    .ops-table-table thead th,
    .ops-table-table tbody td {
        color:#e2e8f0;
    }
    .ops-table-table tbody tr:hover {
        background: rgba(148,163,184,0.03);
    }

    @media (max-width:1200px) {
        .ops-stat-strip { grid-template-columns:repeat(3,minmax(0,1fr)); }
        .ops-chart-grid { grid-template-columns:1fr; }
        .ops-landlord-kpis { grid-template-columns:repeat(3,minmax(0,1fr)); }
        .ops-exec-grid { grid-template-columns:repeat(2,minmax(0,1fr)); }
    }
    @media (max-width:900px) {
        .dashboard-welcome { flex-direction:column; }
        .dashboard-quick-actions { justify-content:flex-start;width:100%; }
        .ops-insight-grid,.ops-table-grid { grid-template-columns:1fr; }
        .ops-kpis { grid-template-columns:repeat(2,minmax(0,1fr)); }
        .ops-landlord-kpis { grid-template-columns:repeat(2,minmax(0,1fr)); }
        .dashboard-tabs { overflow-x:auto; }
    }
    @media (max-width:560px) {
        .ops-kpis,.ops-stat-strip { grid-template-columns:1fr; }
        .ops-landlord-kpis { grid-template-columns:1fr; }
        .ops-exec-grid { grid-template-columns:1fr; }
        .ops-subscription-grid { grid-template-columns:repeat(2,minmax(0,1fr)); }
        .subscription-notice { grid-template-columns:1fr; }
    }
</style>

<div class="ops-dashboard">
    <header class="dashboard-welcome">
        <div>
            <h1>{{ now()->hour < 12 ? 'Good morning' : (now()->hour < 17 ? 'Good afternoon' : 'Good evening') }}, {{ auth()->user()->first_name ?: \Illuminate\Support\Str::before(auth()->user()->name ?: 'there', ' ') }}</h1>
            <p>{{ $isLandlord ? 'Here is the current position of your properties, tenants, invoices, and collections.' : 'Monitor portfolio health and move directly to the work that needs attention.' }}</p>
        </div>
        @if(!($isLandlord && !($landlordAccess['allowed'] ?? true)))
            <div class="dashboard-quick-actions" aria-label="Quick actions">
                @if($isLandlord)
                    <a class="btn btn-secondary" href="{{ route('admin.properties.create') }}">Add property</a>
                    <a class="btn btn-primary" href="{{ route('admin.invitations.index') }}">Invite tenant</a>
                @else
                    <a class="btn btn-secondary" href="{{ route('admin.landlords.index') }}">Review landlords</a>
                    <a class="btn btn-primary" href="{{ route('admin.landlords.create') }}">Add landlord</a>
                @endif
            </div>
        @endif
    </header>

    @if($isLandlord && !($landlordAccess['allowed'] ?? true))
        <section class="subscription-notice locked" role="alert">
            <div>
                <span class="badge badge-red" style="margin-bottom:9px;">Past due</span>
                <h2>Tenant operations are locked</h2>
                <p>{{ $landlordAccess['message'] }}</p>
                <p style="margin-top:7px;">You can still access this dashboard, account settings and notifications. Ask the Starmax Tenant Services administrator to record your renewal; access is restored immediately.</p>
            </div>
            <div class="subscription-notice-meta">
                <span>Expired</span>
                <strong>{{ ($landlordAccess['due_at'] ?? null)?->format('d M Y, H:i') ?? 'Renewal date reached' }}</strong>
                <span style="margin-top:5px;">Monthly fee</span>
                <strong>KSh {{ number_format((float) (auth()->user()->monthly_service_fee ?? 0), 2) }}</strong>
            </div>
        </section>
    @elseif($isLandlord && ($landlordAccess['days_remaining'] ?? 99) <= 7)
        <section class="subscription-notice">
            <div>
                <span class="badge badge-yellow" style="margin-bottom:9px;">Renewal approaching</span>
                <h2>{{ ($landlordAccess['days_remaining'] ?? 0) === 0 ? 'Subscription due today' : 'Subscription due in '.$landlordAccess['days_remaining'].' '.(($landlordAccess['days_remaining'] ?? 0) === 1 ? 'day' : 'days') }}</h2>
                <p>Renew before the due time to keep tenant billing, payments, maintenance, invitations and support available without interruption.</p>
            </div>
            <div class="subscription-notice-meta">
                <span>Renewal date</span>
                <strong>{{ ($landlordAccess['due_at'] ?? null)?->format('d M Y, H:i') }}</strong>
                <span style="margin-top:5px;">Monthly fee</span>
                <strong>KSh {{ number_format((float) (auth()->user()->monthly_service_fee ?? 0), 2) }}</strong>
            </div>
        </section>
    @endif

    <div class="dashboard-tabs ui-tabs" role="tablist" aria-label="Dashboard sections" data-ui-tabs data-tab-param="dashboard_tab">
        <button id="overview-tab-button" class="dashboard-tab ui-tab active" type="button" role="tab" aria-selected="true" aria-controls="overview-tab" data-ui-tab="overview" data-tab-panel="overview-tab">Overview</button>
        <button id="performance-tab-button" class="dashboard-tab ui-tab" type="button" role="tab" aria-selected="false" aria-controls="performance-tab" data-ui-tab="performance" data-tab-panel="performance-tab">Performance</button>
        <button id="portfolio-tab-button" class="dashboard-tab ui-tab" type="button" role="tab" aria-selected="false" aria-controls="portfolio-tab" data-ui-tab="portfolio" data-tab-panel="portfolio-tab">Portfolio</button>
        @unless($isLandlord)
            <button id="landlords-tab-button" class="dashboard-tab ui-tab" type="button" role="tab" aria-selected="false" aria-controls="landlords-tab" data-ui-tab="landlords" data-tab-panel="landlords-tab">Landlords</button>
        @endunless
    </div>

    <div id="overview-tab" class="dashboard-tab-panel ui-tab-panel active" role="tabpanel" aria-labelledby="overview-tab-button">
        @if($isSuperAdmin)
            <div class="ops-panel" style="margin-bottom:16px;">
                <div class="ops-panel-head">
                    <div class="ops-panel-title">Super admin whole portfolio stats</div>
                    <div class="ops-panel-note">Live multi-landlord operational snapshot</div>
                </div>
                <div class="ops-exec-grid">
                    @foreach($superAdminExecutiveStats as $item)
                        <div class="ops-exec-card ops-exec-tone-{{ $item['tone'] }}">
                            <span>{{ $item['label'] }}</span>
                            <strong>{{ $item['value'] }}</strong>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="ops-insight-grid">
            <div class="ops-panel">
                <div class="ops-panel-head">
                    <div class="ops-panel-title">Portfolio occupancy split</div>
                    <div class="ops-panel-note">Unit distribution</div>
                </div>
                <div id="portfolioSplitChart" class="ops-chart-host"></div>
            </div>

            <div class="ops-panel">
                <div class="ops-panel-head">
                    <div class="ops-panel-title">Collection cash position</div>
                    <div class="ops-panel-note">Collected vs outstanding</div>
                </div>
                <div id="cashPositionChart" class="ops-chart-host"></div>
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
                    <table class="ops-table-table">
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
                    <table class="ops-table-table">
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

    <div id="performance-tab" class="dashboard-tab-panel ui-tab-panel" role="tabpanel" aria-labelledby="performance-tab-button">
        <div class="ops-chart-grid">
            <div class="ops-panel ops-chart-card">
                <div class="ops-panel-head">
                    <div class="ops-panel-title">Revenue trend</div>
                    <div class="ops-panel-note">Interactive monthly line</div>
                </div>
                <div id="revenueTrendChart" class="ops-chart-host"></div>
            </div>

            <div class="ops-panel ops-chart-card">
                <div class="ops-panel-head">
                    <div class="ops-panel-title">Invoice mix</div>
                    <div class="ops-panel-note">Paid vs open balances</div>
                </div>
                <div id="invoiceMixChart" class="ops-chart-host"></div>
            </div>

            <div class="ops-panel ops-chart-card">
                <div class="ops-panel-head">
                    <div class="ops-panel-title">Maintenance flow</div>
                    <div class="ops-panel-note">Request resolution pipeline</div>
                </div>
                <div id="maintenanceChart" class="ops-chart-host"></div>
            </div>
        </div>
    </div>

    <div id="portfolio-tab" class="dashboard-tab-panel ui-tab-panel" role="tabpanel" aria-labelledby="portfolio-tab-button">
        <div class="ops-insight-grid">
            <div class="ops-panel">
                <div class="ops-panel-head">
                    <div class="ops-panel-title">Portfolio occupancy split</div>
                    <div class="ops-panel-note">Unit distribution</div>
                </div>
                <div id="portfolioSplitChartAlt" class="ops-chart-host"></div>
            </div>

            <div class="ops-panel">
                <div class="ops-panel-head">
                    <div class="ops-panel-title">Collection cash position</div>
                    <div class="ops-panel-note">Collected vs outstanding</div>
                </div>
                <div id="cashPositionChartAlt" class="ops-chart-host"></div>
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
    </div>

    @unless($isLandlord)
        <div id="landlords-tab" class="dashboard-tab-panel ui-tab-panel" role="tabpanel" aria-labelledby="landlords-tab-button">
            <div class="ops-panel" style="margin-bottom:16px;">
                <div class="ops-panel-head">
                    <div class="ops-panel-title">Landlord subscription lifecycle</div>
                    <div class="ops-panel-note">1 month free trial then paid service</div>
                </div>
                <div class="ops-subscription-grid">
                    <div class="ops-subscription-pill"><span>Trial</span><strong style="color:#7dd3fc;">{{ $landlordSubscription['trial'] ?? 0 }}</strong></div>
                    <div class="ops-subscription-pill"><span>Active Paid</span><strong style="color:#4ade80;">{{ $landlordSubscription['active'] ?? 0 }}</strong></div>
                    <div class="ops-subscription-pill"><span>Past Due</span><strong style="color:#f87171;">{{ $landlordSubscription['past_due'] ?? 0 }}</strong></div>
                    <div class="ops-subscription-pill"><span>Not Required</span><strong style="color:#cbd5e1;">{{ $landlordSubscription['not_required'] ?? 0 }}</strong></div>
                </div>
            </div>

            @if($isSuperAdmin)
                <div class="ops-panel" style="margin-bottom:16px;">
                    <div class="ops-panel-head">
                        <div class="ops-panel-title">Landlord performance command center</div>
                        <div class="ops-panel-note">Operational risk and collections visibility</div>
                    </div>
                    <div class="ops-landlord-kpis">
                        <div class="ops-landlord-kpi"><span>Total landlords</span><strong>{{ $superAdminLandlordStats['total_landlords'] ?? 0 }}</strong></div>
                        <div class="ops-landlord-kpi"><span>Active paid</span><strong style="color:#4ade80;">{{ $superAdminLandlordStats['active_paid_landlords'] ?? 0 }}</strong></div>
                        <div class="ops-landlord-kpi"><span>Past due</span><strong style="color:#f87171;">{{ $superAdminLandlordStats['past_due_landlords'] ?? 0 }}</strong></div>
                        <div class="ops-landlord-kpi"><span>Overdue invoices</span><strong style="color:#fbbf24;">{{ $superAdminLandlordStats['landlords_with_overdue_invoices'] ?? 0 }}</strong></div>
                        <div class="ops-landlord-kpi"><span>Avg collection</span><strong>KSh {{ number_format((float) ($superAdminLandlordStats['avg_monthly_collection_per_landlord'] ?? 0), 2) }}</strong><small>Per landlord</small></div>
                    </div>

                    <div class="ops-insight-grid" style="margin-bottom:0;">
                        <div class="ops-panel" style="box-shadow:none;">
                            <div class="ops-panel-head">
                                <div class="ops-panel-title">Top landlord cashflow</div>
                                <div class="ops-panel-note">Collection vs outstanding</div>
                            </div>
                            <div id="landlordPerformanceChart" class="ops-chart-host"></div>
                        </div>

                        <div class="ops-panel" style="box-shadow:none;">
                            <div class="ops-panel-head">
                                <div class="ops-panel-title">Landlord health split</div>
                                <div class="ops-panel-note">Healthy vs attention vs risk</div>
                            </div>
                            <div id="landlordHealthChart" class="ops-chart-host"></div>
                        </div>
                    </div>

                    <div class="ops-panel" style="box-shadow:none;margin-top:16px;">
                        <div class="ops-panel-head">
                            <div class="ops-panel-title">Landlord leaderboard</div>
                            <div class="ops-panel-note">Sorted by monthly collection</div>
                        </div>
                        <div class="table-scroll">
                            <table class="ops-table-table">
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
        </div>
    @endunless
</div>

<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.53.0/dist/apexcharts.min.js"></script>
<script>
(() => {
    const payload = @json($chartSeries);
    const labels = payload.monthlyRevenueLabels || [];
    const revenue = payload.monthlyRevenueValues || [];

    const common = {
        chart: {
            toolbar: { show: false },
            zoom: { enabled: false },
            foreColor: '#475569',
            fontFamily: 'Segoe UI, Inter, system-ui, sans-serif',
        },
        dataLabels: { enabled: false },
        grid: { borderColor: 'rgba(148,163,184,.16)' },
        legend: {
            position: 'bottom',
            fontSize: '12px',
            fontWeight: 600,
            labels: { colors: '#334155' },
        },
    };

    const mount = (selector, options) => {
        const el = document.querySelector(selector);
        if (!el) return;
        const chart = new ApexCharts(el, options);
        chart.render();
    };

    mount('#revenueTrendChart', {
        ...common,
        chart: { ...common.chart, type: 'area', height: 220 },
        series: [{ name: 'Revenue (KSh)', data: revenue }],
        xaxis: { categories: labels },
        stroke: { curve: 'smooth', width: 3, colors: ['#2563eb'] },
        fill: {
            type: 'gradient',
            gradient: { shadeIntensity: 1, opacityFrom: 0.34, opacityTo: 0.06, stops: [0, 95, 100] },
        },
        colors: ['#2563eb'],
        tooltip: { y: { formatter: (val) => 'KSh ' + Number(val || 0).toLocaleString() } },
    });

    mount('#invoiceMixChart', {
        ...common,
        chart: { ...common.chart, type: 'donut', height: 220 },
        series: payload.invoiceStatusValues || [],
        labels: payload.invoiceStatusLabels || [],
        colors: ['#f59e0b', '#0ea5e9', '#16a34a', '#dc2626'],
        plotOptions: { pie: { donut: { size: '62%' } } },
    });

    mount('#maintenanceChart', {
        ...common,
        chart: { ...common.chart, type: 'bar', height: 220 },
        series: [{ name: 'Requests', data: payload.maintenanceStatusValues || [] }],
        xaxis: { categories: payload.maintenanceStatusLabels || [] },
        colors: ['#1d4ed8'],
        plotOptions: { bar: { borderRadius: 7, columnWidth: '42%' } },
    });

    const portfolioSeries = [
        {{ (int) $stats['occupied_units'] }},
        {{ (int) $stats['vacant_units'] }},
        {{ (int) $stats['pending_tenant_invites'] }}
    ];

    mount('#portfolioSplitChart', {
        ...common,
        chart: { ...common.chart, type: 'donut', height: 220 },
        series: portfolioSeries,
        labels: ['Occupied', 'Vacant', 'Pending invites'],
        colors: ['#2563eb', '#14b8a6', '#f59e0b'],
        plotOptions: { pie: { donut: { size: '60%' } } },
    });

    mount('#portfolioSplitChartAlt', {
        ...common,
        chart: { ...common.chart, type: 'donut', height: 220 },
        series: portfolioSeries,
        labels: ['Occupied', 'Vacant', 'Pending invites'],
        colors: ['#2563eb', '#14b8a6', '#f59e0b'],
        plotOptions: { pie: { donut: { size: '60%' } } },
    });

    const cashPositionSeries = [{{ (float) $stats['total_paid'] }}, {{ (float) $stats['outstanding'] }}];

    mount('#cashPositionChart', {
        ...common,
        chart: { ...common.chart, type: 'bar', height: 220 },
        series: [{ name: 'KSh', data: cashPositionSeries }],
        xaxis: { categories: ['Collected', 'Outstanding'] },
        colors: ['#16a34a', '#dc2626'],
        plotOptions: { bar: { distributed: true, borderRadius: 8, columnWidth: '44%' } },
        tooltip: { y: { formatter: (val) => 'KSh ' + Number(val || 0).toLocaleString() } },
        legend: { show: false },
    });

    mount('#cashPositionChartAlt', {
        ...common,
        chart: { ...common.chart, type: 'bar', height: 220 },
        series: [{ name: 'KSh', data: cashPositionSeries }],
        xaxis: { categories: ['Collected', 'Outstanding'] },
        colors: ['#16a34a', '#dc2626'],
        plotOptions: { bar: { distributed: true, borderRadius: 8, columnWidth: '44%' } },
        tooltip: { y: { formatter: (val) => 'KSh ' + Number(val || 0).toLocaleString() } },
        legend: { show: false },
    });

    mount('#landlordPerformanceChart', {
        ...common,
        chart: { ...common.chart, type: 'bar', height: 240 },
        series: [
            { name: 'Collected', data: payload.landlordPerformanceCollectionValues || [] },
            { name: 'Outstanding', data: payload.landlordPerformanceOutstandingValues || [] },
        ],
        xaxis: { categories: payload.landlordPerformanceLabels || [] },
        colors: ['#16a34a', '#dc2626'],
        plotOptions: { bar: { borderRadius: 7, columnWidth: '50%' } },
        tooltip: { y: { formatter: (val) => 'KSh ' + Number(val || 0).toLocaleString() } },
    });

    mount('#landlordHealthChart', {
        ...common,
        chart: { ...common.chart, type: 'donut', height: 240 },
        series: payload.landlordHealthValues || [],
        labels: payload.landlordHealthLabels || [],
        colors: ['#16a34a', '#f59e0b', '#dc2626'],
        plotOptions: { pie: { donut: { size: '64%' } } },
    });
})();
</script>
@endsection
