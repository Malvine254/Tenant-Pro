<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin — Starmax Ltd</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; background: #f1f5f9; color: #1e293b; display: flex; min-height: 100vh; }
        .sidebar { width: 220px; background: #0f172a; color: #cbd5e1; flex-shrink: 0; display: flex; flex-direction: column; }
        .sidebar-logo { padding: 20px 16px; font-size: 16px; font-weight: bold; color: #fff; border-bottom: 1px solid #1e293b; }
        .sidebar nav a { display: block; padding: 11px 20px; color: #94a3b8; text-decoration: none; font-size: 14px; transition: background .15s; }
        .sidebar nav a:hover, .sidebar nav a.active { background: #1e293b; color: #fff; }
        .sidebar-bottom { margin-top: auto; padding: 16px; border-top: 1px solid #1e293b; }
        .main { flex: 1; display: flex; flex-direction: column; overflow: hidden; }
        .topbar { background: #fff; padding: 14px 24px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; }
        .content { flex: 1; padding: 24px; overflow-y: auto; }
        .page-title { font-size: 20px; font-weight: 600; margin-bottom: 20px; }
        .card { background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 20px; }
        .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 14px; margin-bottom: 24px; }
        .stat { background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px; }
        .stat-label { font-size: 12px; color: #94a3b8; margin-bottom: 4px; }
        .stat-value { font-size: 26px; font-weight: 700; color: #1e293b; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th { text-align: left; padding: 10px 12px; border-bottom: 2px solid #e2e8f0; font-size: 12px; color: #64748b; text-transform: uppercase; }
        td { padding: 10px 12px; border-bottom: 1px solid #f1f5f9; }
        tr:last-child td { border-bottom: none; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 99px; font-size: 11px; font-weight: 600; }
        .badge-green { background: #dcfce7; color: #16a34a; }
        .badge-yellow { background: #fef9c3; color: #ca8a04; }
        .badge-red { background: #fee2e2; color: #dc2626; }
        .badge-blue { background: #dbeafe; color: #1d4ed8; }
        .badge-gray { background: #f1f5f9; color: #64748b; }
        .btn { padding: 7px 14px; border-radius: 6px; border: none; cursor: pointer; font-size: 13px; text-decoration: none; display: inline-block; }
        .btn-primary { background: #1d4ed8; color: #fff; }
        .btn-danger { background: #dc2626; color: #fff; }
        .btn-secondary { background: #e2e8f0; color: #1e293b; }
        .alert-success { background: #dcfce7; border: 1px solid #86efac; color: #166534; padding: 10px 14px; border-radius: 7px; margin-bottom: 16px; font-size: 14px; }
        .alert-error { background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; padding: 10px 14px; border-radius: 7px; margin-bottom: 16px; font-size: 14px; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 5px; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; }
        .form-error { color: #dc2626; font-size: 12px; margin-top: 3px; }
        .pagination { display: flex; gap: 6px; margin-top: 16px; flex-wrap: wrap; }
        .pagination a, .pagination span { padding: 6px 12px; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 13px; text-decoration: none; color: #374151; }
        .pagination .active span { background: #1d4ed8; color: #fff; border-color: #1d4ed8; }
    </style>
</head>
<body>
<div class="sidebar">
    <div class="sidebar-logo">Starmax Admin</div>
    <nav>
        <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">Dashboard</a>
        <a href="{{ route('admin.properties.index') }}" class="{{ request()->routeIs('admin.properties*') ? 'active' : '' }}">Properties</a>
        <a href="{{ route('admin.tenants.index') }}" class="{{ request()->routeIs('admin.tenants*') ? 'active' : '' }}">Tenants</a>
        <a href="{{ route('admin.invoices.index') }}" class="{{ request()->routeIs('admin.invoices*') ? 'active' : '' }}">Invoices</a>
        <a href="{{ route('admin.maintenance.index') }}" class="{{ request()->routeIs('admin.maintenance*') ? 'active' : '' }}">Maintenance</a>
    </nav>
    <div class="sidebar-bottom">
        @auth
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit" style="background:none;border:none;color:#94a3b8;cursor:pointer;font-size:13px;padding:0;">Logout ({{ auth()->user()->name }})</button>
            </form>
        @else
            <a href="{{ url('/') }}" style="color:#94a3b8;text-decoration:none;font-size:13px;">Back to website</a>
        @endauth
    </div>
</div>
<div class="main">
    <div class="topbar">
        <span style="font-weight:600;font-size:15px;">@yield('page-title', 'Dashboard')</span>
        <span style="font-size:13px;color:#64748b;">{{ now()->format('D, d M Y') }}</span>
    </div>
    <div class="content">
        @if(session('success'))
            <div class="alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert-error">{{ session('error') }}</div>
        @endif
        @yield('content')
    </div>
</div>
</body>
</html>
