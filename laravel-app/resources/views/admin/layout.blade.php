<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin — Starmax Ltd</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --bg:#f4f7fb;
            --panel:#ffffff;
            --panel-soft:#f8fafc;
            --line:#dbe4ef;
            --text:#0f172a;
            --muted:#64748b;
            --blue:#2563eb;
            --blue-dark:#1d4ed8;
            --green:#15803d;
            --red:#dc2626;
            --amber:#b7791f;
            --shadow:0 12px 28px rgba(15,23,42,.07);
        }
        body { font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif; background: var(--bg); color: var(--text); display: flex; min-height: 100vh; }
        .admin-shell { display:flex; min-height:100vh; width:100%; }
        .sidebar { width: 224px; background: linear-gradient(180deg,#0b1220,#111827); color: #cbd5e1; flex-shrink: 0; display: flex; flex-direction: column; z-index:40; box-shadow:10px 0 30px rgba(15,23,42,.08); }
        .sidebar-logo { padding: 18px 16px; font-size: 15px; font-weight: 800; color: #fff; border-bottom: 1px solid rgba(148,163,184,.16); letter-spacing:-.02em; }
        .sidebar nav { padding:10px 8px; }
        .sidebar nav a { display: block; padding: 10px 12px; margin-bottom:3px; color: #a7b2c2; text-decoration: none; font-size: 13px; transition: background .15s, color .15s, transform .15s; border-radius:10px; }
        .sidebar nav a:hover, .sidebar nav a.active { background: rgba(37,99,235,.18); color: #fff; }
        .sidebar nav a.active { box-shadow:inset 3px 0 0 #60a5fa; }
        .sidebar-bottom { margin-top: auto; padding: 16px; border-top: 1px solid #1e293b; }
        .main { flex: 1; display: flex; flex-direction: column; overflow: hidden; }
        .topbar { background: rgba(255,255,255,.92); backdrop-filter: blur(14px); padding: 13px 24px; border-bottom: 1px solid var(--line); display: flex; align-items: center; justify-content: space-between; }
        .topbar-left { display:flex; align-items:center; gap:10px; min-width:0; }
        .menu-toggle { display:none; width:38px; height:38px; border:1px solid #cbd5e1; border-radius:9px; background:#fff; color:#0f172a; font-size:20px; cursor:pointer; align-items:center; justify-content:center; }
        .sidebar-close { display:none; margin-left:auto; background:#1e293b; color:#fff; border:0; border-radius:8px; padding:5px 9px; cursor:pointer; }
        .sidebar-backdrop { display:none; position:fixed; inset:0; background:rgba(15,23,42,.48); z-index:30; }
        .content { flex: 1; padding: 22px; overflow-y: auto; }
        .page-title { font-size: 20px; font-weight: 600; margin-bottom: 20px; }
        .card { background: var(--panel); border: 1px solid var(--line); border-radius: 16px; padding: 18px; box-shadow: var(--shadow); }
        .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; margin-bottom: 18px; }
        .stat { background: var(--panel); border: 1px solid var(--line); border-radius: 14px; padding: 14px; box-shadow:0 8px 18px rgba(15,23,42,.05); }
        .stat-label { font-size: 11px; color: var(--muted); margin-bottom: 5px; text-transform:uppercase; letter-spacing:.04em; }
        .stat-value { font-size: 24px; font-weight: 800; color: var(--text); letter-spacing:-.04em; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th { text-align: left; padding: 11px 13px; border-bottom: 1px solid var(--line); font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing:.04em; background:#f8fafc; }
        td { padding: 12px 13px; border-bottom: 1px solid #edf2f7; vertical-align:middle; }
        tbody tr { transition: background .14s; }
        tbody tr:hover { background:#f8fafc; }
        tr:last-child td { border-bottom: none; }
        .badge { display: inline-block; padding: 4px 9px; border-radius: 99px; font-size: 11px; font-weight: 800; letter-spacing:.01em; }
        .badge-green { background: #dcfce7; color: #16a34a; }
        .badge-yellow { background: #fef9c3; color: #ca8a04; }
        .badge-red { background: #fee2e2; color: #dc2626; }
        .badge-blue { background: #dbeafe; color: #1d4ed8; }
        .badge-gray { background: #f1f5f9; color: #64748b; }
        .btn { padding: 8px 13px; border-radius: 10px; border: none; cursor: pointer; font-size: 13px; font-weight:700; text-decoration: none; display: inline-block; transition: transform .12s, box-shadow .12s, background .12s; }
        .btn:hover { transform:translateY(-1px); }
        .btn-primary { background: var(--blue); color: #fff; box-shadow:0 8px 16px rgba(37,99,235,.18); }
        .btn-primary:hover { background:var(--blue-dark); }
        .btn-danger { background: var(--red); color: #fff; box-shadow:0 8px 16px rgba(220,38,38,.15); }
        .btn-secondary { background: #e8eef6; color: #1e293b; }
        .btn[disabled], button[disabled] { opacity: .55; cursor: not-allowed; }
        .muted { color: #64748b; }
        .empty-state { color:#64748b;text-align:center;padding:30px; background:#f8fafc; border-radius:12px; }
        .section-heading { font-size:13px;color:#64748b;margin-bottom:12px;text-transform:uppercase;letter-spacing:.04em; }
        .alert-success { background: #dcfce7; border: 1px solid #86efac; color: #166534; padding: 10px 14px; border-radius: 7px; margin-bottom: 16px; font-size: 14px; }
        .alert-error { background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; padding: 10px 14px; border-radius: 7px; margin-bottom: 16px; font-size: 14px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-size: 12px; font-weight: 800; margin-bottom: 6px; color:#334155; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 11px; font-size: 14px; background:#f8fafc; color:#0f172a; outline:none; transition:border-color .14s, box-shadow .14s, background .14s; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color:var(--blue); background:#fff; box-shadow:0 0 0 3px rgba(37,99,235,.12); }
        .form-error { color: #dc2626; font-size: 12px; margin-top: 3px; }
        .pagination { display: flex; gap: 6px; margin-top: 16px; flex-wrap: wrap; }
        .pagination a, .pagination span { padding: 7px 11px; border: 1px solid var(--line); border-radius: 9px; font-size: 13px; text-decoration: none; color: #374151; background:#fff; }
        .pagination .active span { background: var(--blue); color: #fff; border-color: var(--blue); }
        .admin-page-header { display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:16px;gap:14px; }
        .admin-page-header h2 { font-size:22px;font-weight:800;letter-spacing:-.04em;color:var(--text); }
        .admin-page-header p { font-size:13px;color:var(--muted);margin-top:5px;line-height:1.5;max-width:760px; }
        .admin-actions { display:flex;gap:8px;align-items:center;flex-wrap:wrap;justify-content:flex-end; }
        .admin-filter { display:flex;gap:8px;align-items:center;flex-wrap:wrap; }
        .admin-filter input, .admin-filter select { padding:9px 12px;border:1px solid #cbd5e1;border-radius:10px;background:#f8fafc;font-size:13px;min-height:38px; }
        .table-scroll { overflow-x:auto; }
        details { border:1px solid var(--line) !important;border-radius:13px !important;margin-bottom:10px !important;overflow:hidden;background:#fff; }
        details summary { cursor:pointer;padding:13px 15px !important;background:#f8fafc !important;font-weight:800 !important;display:flex;justify-content:space-between;align-items:center;gap:10px; }
        details[open] summary { border-bottom:1px solid var(--line); }
        .metric-row { display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:12px;margin-bottom:16px; }
        .metric-card { background:#fff;border:1px solid var(--line);border-radius:14px;padding:14px;box-shadow:0 8px 18px rgba(15,23,42,.05); }
        .metric-card span { display:block;font-size:11px;text-transform:uppercase;letter-spacing:.04em;color:var(--muted);margin-bottom:6px; }
        .metric-card strong { display:block;font-size:22px;letter-spacing:-.04em;color:var(--text); }
        a { color:var(--blue); }
        @media (max-width: 900px) {
            body { display:block; }
            .admin-shell { display:block; }
            .sidebar { position:fixed; top:0; bottom:0; left:0; width:min(82vw, 300px); transform:translateX(-105%); transition:transform .2s ease; box-shadow:20px 0 50px rgba(15,23,42,.25); }
            .sidebar.open { transform:translateX(0); }
            .sidebar.open + .sidebar-backdrop { display:block; }
            .sidebar-logo { display:flex; align-items:center; gap:10px; }
            .sidebar-close { display:inline-block; }
            .main { min-height:100vh; width:100%; }
            .topbar { position:sticky; top:0; z-index:20; padding:10px 14px; }
            .menu-toggle { display:inline-flex; flex-shrink:0; }
            .topbar-left span { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
            .content { padding:14px; }
            .stat-grid { grid-template-columns:repeat(2, minmax(0, 1fr)); gap:10px; }
            .stat { padding:12px; }
            .stat-value { font-size:22px; }
            .card { padding:14px; border-radius:9px; overflow-x:auto; }
            table { min-width:680px; }
            .form-group input, .form-group select, .form-group textarea { min-height:40px; }
            .admin-page-header { flex-direction:column; }
            .admin-actions { justify-content:flex-start; width:100%; }
            .admin-filter { width:100%; }
            .admin-filter input, .admin-filter select, .admin-filter .btn { width:100%; }
            .content div[style*="grid-template-columns:1fr 1fr"],
            .content div[style*="grid-template-columns:1fr 1fr 1fr"],
            .content form[style*="grid-template-columns"] {
                grid-template-columns:1fr !important;
            }
        }
        @media (max-width: 560px) {
            .stat-grid { grid-template-columns:1fr; }
            .topbar { align-items:flex-start; gap:8px; }
            .topbar > span:last-child { display:none; }
            .content { padding:12px; }
        }
    </style>
</head>
<body>
@php
    $roleName = auth()->user()?->role?->name;
    $isLandlord = $roleName === 'LANDLORD';
    $isCaretaker = $roleName === 'CARETAKER';
    $isPlatformAdmin = in_array($roleName, ['SUPER_ADMIN', 'ADMIN'], true);
@endphp
<div class="admin-shell">
<aside class="sidebar" id="adminSidebar" aria-hidden="true">
    <div class="sidebar-logo">
        <span>{{ $isLandlord ? 'Landlord Portal' : 'TenantPro Admin' }}</span>
        <button class="sidebar-close" type="button" data-close-menu aria-label="Close menu">×</button>
    </div>
    <nav>
        <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">Dashboard</a>
        @if($isPlatformAdmin)
            <a href="{{ route('admin.landlords.index') }}" class="{{ request()->routeIs('admin.landlords*') ? 'active' : '' }}">Landlords</a>
        @endif
        @unless($isCaretaker)
            <a href="{{ route('admin.properties.index') }}" class="{{ request()->routeIs('admin.properties*') ? 'active' : '' }}">Properties</a>
            <a href="{{ route('admin.units.index') }}" class="{{ request()->routeIs('admin.units*') ? 'active' : '' }}">Units</a>
            <a href="{{ route('admin.tenants.index') }}" class="{{ request()->routeIs('admin.tenants*') ? 'active' : '' }}">Tenants</a>
            <a href="{{ route('admin.invitations.index') }}" class="{{ request()->routeIs('admin.invitations*') ? 'active' : '' }}">{{ $isLandlord ? 'Tenant Invitations' : 'Invitations' }}</a>
            <a href="{{ route('admin.invoices.index') }}" class="{{ request()->routeIs('admin.invoices*') ? 'active' : '' }}">Invoices</a>
        @endunless
        <a href="{{ route('admin.maintenance.index') }}" class="{{ request()->routeIs('admin.maintenance*') ? 'active' : '' }}">Maintenance</a>
        @unless($isCaretaker)
            <a href="{{ route('admin.support.index') }}" class="{{ request()->routeIs('admin.support*') ? 'active' : '' }}">Chat</a>
        @endunless
        @if($isPlatformAdmin)
            <a href="{{ route('admin.deployment-tools.index') }}" class="{{ request()->routeIs('admin.deployment-tools*') ? 'active' : '' }}">Deployment Tools</a>
        @endif
    </nav>
    <div class="sidebar-bottom">
        @auth
            <form method="POST" action="/admin/logout">
                @csrf
                <button type="submit" style="background:none;border:none;color:#94a3b8;cursor:pointer;font-size:13px;padding:0;">Logout ({{ auth()->user()->name }})</button>
            </form>
        @else
            <a href="{{ url('/') }}" style="color:#94a3b8;text-decoration:none;font-size:13px;">Back to website</a>
        @endauth
    </div>
</aside>
<div class="sidebar-backdrop" id="sidebarBackdrop" data-close-menu></div>
<div class="main">
    <div class="topbar">
        <div class="topbar-left">
            <button class="menu-toggle" type="button" id="menuToggle" aria-controls="adminSidebar" aria-expanded="false" aria-label="Open menu">☰</button>
            <span style="font-weight:600;font-size:15px;">@yield('page-title', 'Dashboard')</span>
        </div>
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
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById('adminSidebar');
    const toggle = document.getElementById('menuToggle');
    const closeButtons = document.querySelectorAll('[data-close-menu]');
    if (!sidebar || !toggle) return;

    const openMenu = () => {
        sidebar.classList.add('open');
        sidebar.setAttribute('aria-hidden', 'false');
        toggle.setAttribute('aria-expanded', 'true');
        document.body.style.overflow = 'hidden';
    };

    const closeMenu = () => {
        sidebar.classList.remove('open');
        sidebar.setAttribute('aria-hidden', 'true');
        toggle.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
    };

    toggle.addEventListener('click', () => sidebar.classList.contains('open') ? closeMenu() : openMenu());
    closeButtons.forEach(button => button.addEventListener('click', closeMenu));
    sidebar.querySelectorAll('nav a').forEach(link => link.addEventListener('click', closeMenu));
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape') closeMenu();
    });
});
</script>
</body>
</html>
