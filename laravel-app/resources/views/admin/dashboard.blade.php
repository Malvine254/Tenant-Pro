@extends('admin.layout')
@section('page-title', 'Dashboard')

@section('content')
<div class="stat-grid">
    <div class="stat">
        <div class="stat-label">Properties</div>
        <div class="stat-value">{{ $stats['total_properties'] }}</div>
    </div>
    <div class="stat">
        <div class="stat-label">Total Units</div>
        <div class="stat-value">{{ $stats['total_units'] }}</div>
    </div>
    <div class="stat">
        <div class="stat-label">Occupied Units</div>
        <div class="stat-value" style="color:#16a34a;">{{ $stats['occupied_units'] }}</div>
    </div>
    <div class="stat">
        <div class="stat-label">Active Tenants</div>
        <div class="stat-value">{{ $stats['total_tenants'] }}</div>
    </div>
    <div class="stat">
        <div class="stat-label">Pending Invoices</div>
        <div class="stat-value" style="color:#ca8a04;">{{ $stats['pending_invoices'] }}</div>
    </div>
    <div class="stat">
        <div class="stat-label">Open Maintenance</div>
        <div class="stat-value" style="color:#dc2626;">{{ $stats['open_maintenance'] }}</div>
    </div>
    <div class="stat">
        <div class="stat-label">Total Users</div>
        <div class="stat-value">{{ $stats['total_users'] }}</div>
    </div>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;">
    <a href="{{ route('admin.properties.index') }}" class="card" style="text-decoration:none;color:inherit;display:block;">
        <div style="font-weight:600;margin-bottom:4px;">Manage Properties</div>
        <div style="font-size:13px;color:#94a3b8;">View, add and manage properties</div>
    </a>
    <a href="{{ route('admin.tenants.index') }}" class="card" style="text-decoration:none;color:inherit;display:block;">
        <div style="font-weight:600;margin-bottom:4px;">Manage Tenants</div>
        <div style="font-size:13px;color:#94a3b8;">View tenant profiles and details</div>
    </a>
    <a href="{{ route('admin.invoices.index') }}" class="card" style="text-decoration:none;color:inherit;display:block;">
        <div style="font-weight:600;margin-bottom:4px;">Invoices</div>
        <div style="font-size:13px;color:#94a3b8;">Track payments and outstanding invoices</div>
    </a>
    <a href="{{ route('admin.maintenance.index') }}" class="card" style="text-decoration:none;color:inherit;display:block;">
        <div style="font-weight:600;margin-bottom:4px;">Maintenance</div>
        <div style="font-size:13px;color:#94a3b8;">Review and assign maintenance requests</div>
    </a>
</div>
@endsection
