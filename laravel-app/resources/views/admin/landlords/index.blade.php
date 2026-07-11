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
    .landlord-page { margin:-4px -2px 0; }
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
        color:#2563eb;
        margin-bottom:5px;
    }
    .landlord-title {
        font-size:24px;
        font-weight:800;
        letter-spacing:-.04em;
        color:#0f172a;
    }
    .landlord-subtitle {
        font-size:13px;
        color:#64748b;
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
        background:#fff;
        border:1px solid #dbe3ee;
        border-radius:12px;
        padding:11px 12px;
        box-shadow:0 8px 18px rgba(15,23,42,.06);
    }
    .landlord-mini-card span {
        display:block;
        color:#64748b;
        font-size:11px;
        margin-bottom:5px;
    }
    .landlord-mini-card strong {
        display:block;
        color:#0f172a;
        font-size:20px;
        line-height:1;
        letter-spacing:-.03em;
    }
    .landlord-mini-card.green strong { color:#15803d; }
    .landlord-mini-card.red strong { color:#b91c1c; }
    .landlord-toolbar {
        background:#fff;
        border:1px solid #dbe3ee;
        border-radius:14px;
        padding:12px;
        margin-bottom:14px;
        box-shadow:0 8px 18px rgba(15,23,42,.05);
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
        border:1px solid #cbd5e1;
        border-radius:10px;
        background:#f8fafc;
        font-size:13px;
        color:#0f172a;
        outline:none;
    }
    .landlord-input:focus,
    .landlord-select:focus {
        border-color:#2563eb;
        background:#fff;
        box-shadow:0 0 0 3px rgba(37,99,235,.12);
    }
    .landlord-card {
        background:#fff;
        border:1px solid #dbe3ee;
        border-radius:14px;
        overflow:hidden;
        box-shadow:0 10px 24px rgba(15,23,42,.07);
    }
    .landlord-table-wrap { overflow-x:auto; }
    .landlord-table { min-width:980px; }
    .landlord-table th {
        background:#f8fafc;
        color:#475569;
        font-size:11px;
        letter-spacing:.04em;
        padding:11px 14px;
        border-bottom:1px solid #e2e8f0;
    }
    .landlord-table td {
        padding:13px 14px;
        vertical-align:middle;
        border-bottom:1px solid #eef2f7;
    }
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
        background:linear-gradient(135deg,#2563eb,#0f766e);
        flex:0 0 auto;
        box-shadow:0 8px 16px rgba(37,99,235,.18);
        overflow:hidden;
    }
    .landlord-avatar img { width:100%;height:100%;display:block;object-fit:cover;border-radius:50%; }
    .landlord-name {
        font-size:14px;
        font-weight:800;
        color:#0f172a;
        margin-bottom:3px;
    }
    .landlord-email,
    .landlord-muted {
        color:#64748b;
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
        background:#f1f5f9;
        color:#334155;
        font-size:12px;
        font-weight:700;
        white-space:nowrap;
    }
    .landlord-money {
        font-size:13px;
        font-weight:800;
        color:#15803d;
        white-space:nowrap;
    }
    .landlord-money.red { color:#b91c1c; }
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
        color:#64748b;
    }
    .landlord-empty strong {
        display:block;
        color:#0f172a;
        font-size:16px;
        margin-bottom:5px;
    }
    .landlord-pagination {
        padding:0 14px 14px;
    }
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

    <div class="landlord-toolbar">
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
