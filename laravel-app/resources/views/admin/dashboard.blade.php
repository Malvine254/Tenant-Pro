@extends('admin.layout')
@section('page-title', $isLandlord ? 'Landlord Operations Dashboard' : 'Platform Operations Dashboard')

@section('content')
@php
    $maxRevenue = max(1, $monthlyRevenue->max('amount') ?: 1);
    $maxInvoiceStatus = max(1, $invoiceStatus->max('count') ?: 1);
    $maxMaintenanceStatus = max(1, $maintenanceStatus->max('count') ?: 1);
@endphp

<div class="stat-grid">
    @unless($isLandlord)
    <div class="stat">
        <div class="stat-label">Total Landlords</div>
        <div class="stat-value">{{ $stats['total_landlords'] }}</div>
    </div>
    <div class="stat">
        <div class="stat-label">Pending Landlord Invites</div>
        <div class="stat-value" style="color:#ca8a04;">{{ $stats['pending_landlord_invites'] }}</div>
    </div>
    @endunless
    <div class="stat">
        <div class="stat-label">{{ $isLandlord ? 'My Properties' : 'Total Properties' }}</div>
        <div class="stat-value">{{ $stats['total_properties'] }}</div>
    </div>
    <div class="stat">
        <div class="stat-label">Occupancy</div>
        <div class="stat-value" style="color:#1d4ed8;">{{ $stats['occupancy_rate'] }}%</div>
    </div>
    <div class="stat">
        <div class="stat-label">Rent Collected This Month</div>
        <div class="stat-value" style="font-size:22px;color:#16a34a;">KSh {{ number_format($stats['collected_this_month'], 2) }}</div>
    </div>
    <div class="stat">
        <div class="stat-label">Total Rent Collected</div>
        <div class="stat-value" style="font-size:22px;color:#16a34a;">KSh {{ number_format($stats['total_paid'], 2) }}</div>
    </div>
    <div class="stat">
        <div class="stat-label">Outstanding Balance</div>
        <div class="stat-value" style="font-size:22px;color:#dc2626;">KSh {{ number_format($stats['outstanding'], 2) }}</div>
    </div>
    <div class="stat">
        <div class="stat-label">Active Tenants</div>
        <div class="stat-value">{{ $stats['total_tenants'] }}</div>
    </div>
    <div class="stat">
        <div class="stat-label">Vacant Units</div>
        <div class="stat-value" style="color:#0f766e;">{{ $stats['vacant_units'] }}</div>
    </div>
    <div class="stat">
        <div class="stat-label">Pending Tenant Invites</div>
        <div class="stat-value" style="color:#9333ea;">{{ $stats['pending_tenant_invites'] }}</div>
    </div>
    <div class="stat">
        <div class="stat-label">Overdue Invoices</div>
        <div class="stat-value" style="color:#dc2626;">{{ $stats['overdue_invoices'] }}</div>
    </div>
    <div class="stat">
        <div class="stat-label">Open Maintenance</div>
        <div class="stat-value" style="color:#ca8a04;">{{ $stats['open_maintenance'] }}</div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1.4fr 1fr;gap:16px;margin-bottom:16px;">
    <div class="card">
        <h3 style="font-size:13px;color:#64748b;margin-bottom:14px;text-transform:uppercase;">Monthly Rent Collection Trend</h3>
        <div style="display:flex;align-items:end;gap:12px;height:220px;border-bottom:1px solid #e2e8f0;padding:0 6px 8px;">
            @foreach($monthlyRevenue as $month)
                <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:7px;">
                    <div style="font-size:11px;color:#64748b;">{{ number_format($month['amount'] / 1000, 1) }}k</div>
                    <div style="width:100%;max-width:44px;height:{{ max(8, ($month['amount'] / $maxRevenue) * 160) }}px;background:#1d4ed8;border-radius:6px 6px 0 0;"></div>
                    <div style="font-size:12px;color:#64748b;">{{ $month['label'] }}</div>
                </div>
            @endforeach
        </div>
    </div>
    <div class="card">
        <h3 style="font-size:13px;color:#64748b;margin-bottom:14px;text-transform:uppercase;">Portfolio Health</h3>
        <div style="margin-bottom:16px;">
            <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:6px;">
                <span>Occupied Units</span><strong>{{ $stats['occupied_units'] }} / {{ $stats['total_units'] }}</strong>
            </div>
            <div style="height:10px;background:#e2e8f0;border-radius:99px;overflow:hidden;">
                <div style="height:10px;width:{{ $stats['occupancy_rate'] }}%;background:#16a34a;"></div>
            </div>
        </div>
        <div style="display:grid;gap:10px;">
            @foreach($invoiceStatus as $item)
                <div>
                    <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:4px;">
                        <span>{{ $item['label'] }}</span><span>{{ $item['count'] }}</span>
                    </div>
                    <div style="height:8px;background:#f1f5f9;border-radius:99px;overflow:hidden;">
                        <div style="height:8px;width:{{ ($item['count'] / $maxInvoiceStatus) * 100 }}%;background:#64748b;"></div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
    <div class="card">
        <h3 style="font-size:13px;color:#64748b;margin-bottom:12px;text-transform:uppercase;">Recent Invoices</h3>
        <table>
            <thead><tr><th>Tenant</th><th>Unit</th><th>Amount</th><th>Status</th></tr></thead>
            <tbody>
                @forelse($recentInvoices as $invoice)
                    <tr>
                        <td>{{ $invoice->tenant?->name ?? '-' }}</td>
                        <td>{{ $invoice->unit?->property?->name ?? '-' }} / {{ $invoice->unit?->unit_number ?? '-' }}</td>
                        <td>{{ $invoice->total_amount_formatted }}</td>
                        <td><span class="badge badge-gray">{{ $invoice->status }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="4" style="color:#94a3b8;text-align:center;padding:18px;">No invoices yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card">
        <h3 style="font-size:13px;color:#64748b;margin-bottom:12px;text-transform:uppercase;">Maintenance Status</h3>
        <div style="display:grid;gap:10px;margin-bottom:16px;">
            @foreach($maintenanceStatus as $item)
                <div>
                    <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:4px;">
                        <span>{{ $item['label'] }}</span><span>{{ $item['count'] }}</span>
                    </div>
                    <div style="height:8px;background:#f1f5f9;border-radius:99px;overflow:hidden;">
                        <div style="height:8px;width:{{ ($item['count'] / $maxMaintenanceStatus) * 100 }}%;background:#ca8a04;"></div>
                    </div>
                </div>
            @endforeach
        </div>
        <table>
            <thead><tr><th>Issue</th><th>Property / Unit</th><th>Status</th></tr></thead>
            <tbody>
                @forelse($recentMaintenance as $request)
                    <tr>
                        <td>{{ $request->title }}</td>
                        <td>{{ $request->unit?->property?->name ?? '-' }} / {{ $request->unit?->unit_number ?? '-' }}</td>
                        <td><span class="badge badge-gray">{{ $request->status }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="3" style="color:#94a3b8;text-align:center;padding:18px;">No maintenance requests yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
    <div class="card">
        <h3 style="font-size:13px;color:#64748b;margin-bottom:12px;text-transform:uppercase;">Recent Payments</h3>
        <table>
            <thead><tr><th>Tenant</th><th>Property / Unit</th><th>Amount</th><th>Date</th></tr></thead>
            <tbody>
                @forelse($recentPayments as $payment)
                    <tr>
                        <td>{{ $payment->invoice?->tenant?->name ?? '-' }}</td>
                        <td>{{ $payment->invoice?->unit?->property?->name ?? '-' }} / {{ $payment->invoice?->unit?->unit_number ?? '-' }}</td>
                        <td style="color:#16a34a;">KSh {{ number_format($payment->amount, 2) }}</td>
                        <td style="font-size:13px;">{{ $payment->created_at?->format('d M Y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" style="color:#94a3b8;text-align:center;padding:18px;">No payments yet. Tenant payments will appear here once received.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card">
        <h3 style="font-size:13px;color:#64748b;margin-bottom:12px;text-transform:uppercase;">Recent Invitations</h3>
        <table>
            <thead><tr><th>Type</th><th>Invitee</th><th>Unit</th><th>Status</th></tr></thead>
            <tbody>
                @forelse($recentInvitations as $invitation)
                    <tr>
                        <td>{{ ucfirst(strtolower($invitation->invite_type ?? 'TENANT')) }}</td>
                        <td>{{ $invitation->invitee_name ?: ($invitation->email ?: $invitation->phone_number) }}</td>
                        <td>{{ $invitation->property?->name ? $invitation->property->name.' / '.$invitation->unit?->unit_number : '-' }}</td>
                        <td><span class="badge badge-gray">{{ $invitation->status }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="4" style="color:#94a3b8;text-align:center;padding:18px;">No invitations yet. Invite {{ $isLandlord ? 'a tenant to a vacant unit' : 'your first landlord or tenant' }}.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:16px;">
    @unless($isLandlord)
        <a href="{{ route('admin.landlords.create') }}" class="card" style="text-decoration:none;color:inherit;display:block;">
            <div style="font-weight:600;margin-bottom:4px;">Invite Landlord</div>
            <div style="font-size:13px;color:#94a3b8;">Email onboarding is preferred; direct create remains for emergency admin use</div>
        </a>
    @endunless
    <a href="{{ route('admin.properties.create') }}" class="card" style="text-decoration:none;color:inherit;display:block;">
        <div style="font-weight:600;margin-bottom:4px;">Add Property</div>
        <div style="font-size:13px;color:#94a3b8;">Create property and assign owner</div>
    </a>
    <a href="{{ route('admin.units.index') }}" class="card" style="text-decoration:none;color:inherit;display:block;">
        <div style="font-weight:600;margin-bottom:4px;">Add Unit</div>
        <div style="font-size:13px;color:#94a3b8;">Choose one of your properties and add a unit</div>
    </a>
    <a href="{{ route('admin.invitations.index') }}" class="card" style="text-decoration:none;color:inherit;display:block;">
        <div style="font-weight:600;margin-bottom:4px;">Invite Tenant</div>
        <div style="font-size:13px;color:#94a3b8;">Invite a tenant to a vacant unit by email</div>
    </a>
    <a href="{{ route('admin.invoices.index') }}" class="card" style="text-decoration:none;color:inherit;display:block;">
        <div style="font-weight:600;margin-bottom:4px;">Invoices</div>
        <div style="font-size:13px;color:#94a3b8;">Track paid and outstanding rent</div>
    </a>
</div>
@endsection
