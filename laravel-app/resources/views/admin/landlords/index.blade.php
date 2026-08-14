@extends('admin.layout')
@section('page-title', 'Landlords')

@section('content')
@php
    $visibleLandlords = $landlords->getCollection();
    $activeCount = $visibleLandlords->where('is_active', true)->count();
    $inactiveCount = $visibleLandlords->where('is_active', false)->count();
    $propertyCount = $visibleLandlords->sum('properties_count');
    $unitCount = $visibleLandlords->sum('units_count');
    $tenantCount = $visibleLandlords->sum('tenants_count');
    $monthlyCollected = $visibleLandlords->sum('collected_this_month');
    $outstandingBalance = $visibleLandlords->sum('outstanding_balance');
@endphp

<style>
    .landlord-page {
        margin:-4px -2px 0;
        color:#e2e8f0;
    }
    .landlord-hero {
        display:flex;
        justify-content:space-between;
        gap:16px;
        align-items:flex-start;
        margin-bottom:16px;
    }
    .landlord-eyebrow {
        font-size:11px;
        font-weight:800;
        letter-spacing:.08em;
        text-transform:uppercase;
        color:#93c5fd;
        margin-bottom:5px;
    }
    .landlord-title {
        font-size:24px;
        font-weight:800;
        letter-spacing:-.04em;
        color:#f8fafc;
    }
    .landlord-subtitle {
        font-size:13px;
        color:#94a3b8;
        margin-top:5px;
        max-width:680px;
        line-height:1.5;
    }
    .landlord-actions {
        display:flex;
        gap:8px;
        flex-wrap:wrap;
        justify-content:flex-end;
    }
    .landlord-summary {
        display:grid;
        grid-template-columns:repeat(6, minmax(112px, 1fr));
        gap:10px;
        margin-bottom:14px;
    }
    .landlord-mini-card {
        background:linear-gradient(180deg,#111827,#0f172a);
        border:1px solid rgba(148,163,184,.2);
        border-radius:12px;
        padding:11px 12px;
        box-shadow:0 8px 18px rgba(2,6,23,.28);
    }
    .landlord-mini-card span {
        display:block;
        color:#94a3b8;
        font-size:11px;
        margin-bottom:5px;
    }
    .landlord-mini-card strong {
        display:block;
        color:#f8fafc;
        font-size:20px;
        line-height:1;
        letter-spacing:-.03em;
    }
    .landlord-mini-card.green strong { color:#86efac; }
    .landlord-mini-card.red strong { color:#fca5a5; }
    .landlord-toolbar {
        background:linear-gradient(180deg,#111827,#0f172a);
        border:1px solid rgba(148,163,184,.2);
        border-radius:14px;
        padding:12px;
        margin-bottom:14px;
        box-shadow:0 8px 18px rgba(2,6,23,.26);
    }
    .landlord-filter {
        display:grid;
        grid-template-columns:minmax(220px,1fr) 190px auto auto;
        gap:9px;
        align-items:center;
    }
    .landlord-input,
    .landlord-select {
        width:100%;
        min-height:38px;
        padding:8px 11px;
        border:1px solid rgba(148,163,184,.25);
        border-radius:10px;
        background:#0b1220;
        font-size:13px;
        color:#e2e8f0;
        outline:none;
    }
    .landlord-input::placeholder { color:#64748b; }
    .landlord-input:focus,
    .landlord-select:focus {
        border-color:#60a5fa;
        background:#0f172a;
        box-shadow:0 0 0 3px rgba(96,165,250,.18);
    }
    .landlord-card {
        background:linear-gradient(180deg,#111827,#0b1220);
        border:1px solid rgba(148,163,184,.18);
        border-radius:14px;
        overflow:hidden;
        box-shadow:0 10px 24px rgba(2,6,23,.35);
    }
    .landlord-table-wrap { overflow-x:auto; }
    .landlord-table { min-width:980px; }
    .landlord-table th {
        background:rgba(15,23,42,.92);
        color:#cbd5e1;
        font-size:11px;
        letter-spacing:.04em;
        padding:11px 14px;
        border-bottom:1px solid rgba(148,163,184,.18);
    }
    .landlord-table td {
        padding:13px 14px;
        vertical-align:middle;
        border-bottom:1px solid rgba(148,163,184,.12);
        color:#e2e8f0;
    }
    tbody tr:hover { background:rgba(15,23,42,.8); }
    .landlord-person {
        display:flex;
        align-items:center;
        gap:10px;
        min-width:210px;
    }
    .landlord-avatar {
        width:38px;
        height:38px;
        border-radius:50%;
        display:flex;
        align-items:center;
        justify-content:center;
        font-size:13px;
        font-weight:800;
        color:#fff;
        background:linear-gradient(135deg,#3b82f6,#0ea5e9);
        flex:0 0 auto;
        box-shadow:0 8px 16px rgba(59,130,246,.2);
        overflow:hidden;
    }
    .landlord-avatar img { width:100%;height:100%;display:block;object-fit:cover;border-radius:50%; }
    .landlord-name {
        font-size:14px;
        font-weight:800;
        color:#f8fafc;
        margin-bottom:3px;
    }
    .landlord-email,
    .landlord-muted {
        color:#94a3b8;
        font-size:12px;
    }
    .landlord-counts {
        display:flex;
        gap:8px;
        flex-wrap:wrap;
    }
    .landlord-pill {
        display:inline-flex;
        align-items:center;
        gap:5px;
        padding:5px 8px;
        border-radius:999px;
        background:rgba(148,163,184,.12);
        color:#dbeafe;
        font-size:12px;
        font-weight:700;
        white-space:nowrap;
        border:1px solid rgba(148,163,184,.16);
    }
    .landlord-money {
        font-size:13px;
        font-weight:800;
        color:#86efac;
        white-space:nowrap;
    }
    .landlord-money.red { color:#fca5a5; }
    .landlord-row-actions {
        display:flex;
        gap:7px;
        align-items:center;
        justify-content:flex-end;
        white-space:nowrap;
    }
    .landlord-empty {
        padding:40px 18px;
        text-align:center;
        color:#94a3b8;
    }
    .landlord-empty strong {
        display:block;
        color:#f8fafc;
        font-size:16px;
        margin-bottom:5px;
    }
    .landlord-pagination {
        padding:0 14px 14px;
    }
    .landlord-page .badge {
        border:1px solid rgba(148,163,184,.18);
        box-shadow:inset 0 0 0 1px rgba(255,255,255,.02);
    }
    .landlord-page .badge-green { background:rgba(22,163,74,.18); color:#bbf7d0; }
    .landlord-page .badge-yellow { background:rgba(202,138,4,.18); color:#fcd34d; }
    .landlord-page .badge-red { background:rgba(220,38,38,.18); color:#fecaca; }
    .landlord-page .badge-blue { background:rgba(37,99,235,.18); color:#bfdbfe; }
    .landlord-page .badge-gray { background:rgba(148,163,184,.12); color:#e2e8f0; }
    .landlord-page .btn {
        border:1px solid rgba(148,163,184,.18);
        box-shadow:none;
    }
    .landlord-page .btn-primary {
        background:linear-gradient(180deg,#2563eb,#1d4ed8);
        color:#eff6ff;
        box-shadow:0 8px 16px rgba(37,99,235,.22);
    }
    .landlord-page .btn-primary:hover { background:linear-gradient(180deg,#1d4ed8,#1e40af); }
    .landlord-page .btn-secondary {
        background:rgba(148,163,184,.1);
        color:#e2e8f0;
        border-color:rgba(148,163,184,.18);
    }
    .landlord-page .btn-danger {
        background:linear-gradient(180deg,#ef4444,#dc2626);
        color:#fff;
        box-shadow:0 8px 16px rgba(239,68,68,.2);
    }
    .landlord-page .btn-secondary:hover { background:rgba(148,163,184,.16); }
    @media (max-width: 1180px) {
        .landlord-summary { grid-template-columns:repeat(3, minmax(0, 1fr)); }
    }
    @media (max-width: 820px) {
        .landlord-hero { flex-direction:column; }
        .landlord-actions { justify-content:flex-start; }
        .landlord-filter { grid-template-columns:1fr; }
        .landlord-summary { grid-template-columns:repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 520px) {
        .landlord-title { font-size:21px; }
        .landlord-summary { grid-template-columns:1fr; }
        .landlord-actions .btn { width:100%; text-align:center; }
    }
</style>

<div class="landlord-page">
    <div class="landlord-hero">
        <div>
            <div class="landlord-eyebrow">Portfolio owners</div>
            <h2 class="landlord-title">Landlords</h2>
            <p class="landlord-subtitle">
                Monitor onboarding, property coverage, units, active tenants, rent collection, and balances from one cleaner workspace.
            </p>
        </div>
        <div class="landlord-actions">
            <a href="{{ route('admin.invitations.index', ['type' => 'LANDLORD']) }}" class="btn btn-primary">Invite Landlord</a>
            <a href="{{ route('admin.landlords.create') }}" class="btn btn-secondary">Emergency Create</a>
        </div>
    </div>

    <div class="landlord-summary">
        <div class="landlord-mini-card">
            <span>Showing</span>
            <strong>{{ $landlords->count() }}/{{ $landlords->total() }}</strong>
        </div>
        <div class="landlord-mini-card green">
            <span>Active</span>
            <strong>{{ $activeCount }}</strong>
        </div>
        <div class="landlord-mini-card">
            <span>Inactive</span>
            <strong>{{ $inactiveCount }}</strong>
        </div>
        <div class="landlord-mini-card">
            <span>Properties / Units</span>
            <strong>{{ $propertyCount }}/{{ $unitCount }}</strong>
        </div>
        <div class="landlord-mini-card">
            <span>Tenants</span>
            <strong>{{ $tenantCount }}</strong>
        </div>
        <div class="landlord-mini-card red">
            <span>Outstanding</span>
            <strong>KSh {{ number_format($outstandingBalance, 0) }}</strong>
        </div>
    </div>

    <div class="landlord-toolbar" style="display:grid;gap:10px;">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
            <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                <span class="badge badge-blue">Trial: first month included</span>
                <span class="badge badge-green">Subscription: active after payment</span>
                <span class="badge badge-red">Past due: access blocked</span>
            </div>
            <div style="font-size:12px;color:#475569;">
                <strong>Admin action:</strong> <span class="badge badge-primary">Mark Paid +1M</span>
            </div>
        </div>
        <form method="GET" class="landlord-filter">
            <input class="landlord-input" type="text" name="search" value="{{ request('search') }}" placeholder="Search by name, email, or phone">
            <select class="landlord-select" name="status">
                <option value="">All statuses</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active only</option>
                <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Inactive only</option>
            </select>
            <button type="submit" class="btn btn-primary">Apply Filter</button>
            <a href="{{ route('admin.landlords.index') }}" class="btn btn-secondary">Reset</a>
        </form>
    </div>

    <div class="landlord-card">
        <div class="landlord-table-wrap">
            <table class="landlord-table">
                <thead>
                    <tr>
                        <th>Landlord</th>
                        <th>Phone</th>
                        <th>Portfolio</th>
                        <th>Subscription</th>
                        <th>Collected This Month</th>
                        <th>Outstanding</th>
                        <th>Status</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($landlords as $landlord)
                        @php
                            $initials = collect(explode(' ', trim($landlord->name)))->filter()->take(2)->map(fn($part) => substr($part, 0, 1))->implode('');
                        @endphp
                        <tr>
                            <td>
                                <div class="landlord-person">
                                    <div class="landlord-avatar">
                                        @if($landlord->profile_image_url)
                                            <img src="{{ str_starts_with($landlord->profile_image_url, 'http') ? $landlord->profile_image_url : asset(ltrim($landlord->profile_image_url, '/')) }}" alt="{{ $landlord->name }}">
                                        @else
                                            {{ strtoupper($initials ?: 'L') }}
                                        @endif
                                    </div>
                                    <div>
                                        <div class="landlord-name">{{ $landlord->name }}</div>
                                        <div class="landlord-email">{{ $landlord->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="landlord-muted">{{ $landlord->phone_number ?? 'No phone added' }}</div>
                            </td>
                            <td>
                                <div class="landlord-counts">
                                    <span class="landlord-pill">{{ $landlord->properties_count }} properties</span>
                                    <span class="landlord-pill">{{ $landlord->units_count }} units</span>
                                    <span class="landlord-pill">{{ $landlord->tenants_count }} tenants</span>
                                </div>
                            </td>
                            <td>
                                @php
                                    $billingTone = match($landlord->billing_status) {
                                        'active' => 'badge-green',
                                        'trial' => 'badge-blue',
                                        'past_due' => 'badge-red',
                                        default => 'badge-gray',
                                    };
                                    $subscriptionLabel = match($landlord->billing_status ?? 'not_required') {
                                        'trial' => 'Trial month included',
                                        'active' => 'Subscription active',
                                        'past_due' => 'Subscription overdue',
                                        default => 'Not required',
                                    };
                                    $trialEnds = $landlord->trial_ends_at?->format('d M Y');
                                    $paidUntil = $landlord->service_paid_until?->format('d M Y');
                                @endphp
                                <div class="landlord-muted" style="display:grid;gap:5px;">
                                    <span class="badge {{ $billingTone }}">{{ strtoupper(str_replace('_', ' ', $landlord->billing_status ?? 'not_required')) }}</span>
                                    <span><strong>{{ $subscriptionLabel }}</strong></span>
                                    <span>Trial ends: {{ $trialEnds ?? '-' }}</span>
                                    <span>Paid until: {{ $paidUntil ?? '-' }}</span>
                                </div>
                            </td>
                            <td><span class="landlord-money">KSh {{ number_format($landlord->collected_this_month, 2) }}</span></td>
                            <td><span class="landlord-money red">KSh {{ number_format($landlord->outstanding_balance, 2) }}</span></td>
                            <td>
                                <span class="badge {{ $landlord->is_active ? 'badge-green' : 'badge-gray' }}">
                                    {{ $landlord->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>
                                <div class="landlord-row-actions">
                                    <a href="{{ route('admin.landlords.edit', $landlord) }}" class="btn btn-secondary">Edit</a>
                                    <form method="POST" action="{{ route('admin.landlords.payments.record', $landlord) }}">
                                        @csrf
                                        <input type="hidden" name="months" value="1">
                                        <button type="submit" class="btn btn-primary" onclick="return confirm('Record one month service payment for this landlord?')">
                                            Mark Paid +1M
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.landlords.status', $landlord) }}">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="is_active" value="{{ $landlord->is_active ? 0 : 1 }}">
                                        <button type="submit" class="btn {{ $landlord->is_active ? 'btn-danger' : 'btn-primary' }}" onclick="return confirm('{{ $landlord->is_active ? 'Suspend this landlord account?' : 'Reactivate this landlord account?' }}')">
                                            {{ $landlord->is_active ? 'Suspend' : 'Reactivate' }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="landlord-empty">
                                    <strong>No landlords found</strong>
                                    Invite your first landlord to start onboarding properties and tenants.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="landlord-pagination pagination">{{ $landlords->links() }}</div>
    </div>
</div>
@endsection
