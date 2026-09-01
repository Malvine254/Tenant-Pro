<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>@yield('page-title', 'Dashboard') · TenantPro</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            color-scheme: dark;
            --bg:#0b1020;
            --bg-alt:#0f172a;
            --panel:#111827;
            --panel-soft:#0f172a;
            --panel-strong:#0b1220;
            --line:rgba(148,163,184,.18);
            --text:#f8fafc;
            --muted:#94a3b8;
            --blue:#60a5fa;
            --blue-dark:#3b82f6;
            --green:#34d399;
            --red:#f87171;
            --amber:#fbbf24;
            --shadow:0 18px 42px rgba(2,6,23,.32);
        }
        html { background:var(--bg); }
        body { font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif; background: radial-gradient(circle at top, rgba(59,130,246,.12), transparent 25%), var(--bg); color: var(--text); height:100vh; overflow:hidden; line-height:1.45; }
        ::selection { background:rgba(96,165,250,.35);color:#fff; }
        :focus-visible { outline:3px solid #93c5fd;outline-offset:3px; }
        .skip-link { position:fixed;left:14px;top:10px;z-index:100;transform:translateY(-160%);background:#fff;color:#0f172a;padding:9px 13px;border-radius:9px;font-weight:800;text-decoration:none; }
        .skip-link:focus { transform:translateY(0); }
        .sr-only { position:absolute!important;width:1px!important;height:1px!important;padding:0!important;margin:-1px!important;overflow:hidden!important;clip:rect(0,0,0,0)!important;white-space:nowrap!important;border:0!important; }
        .admin-shell { min-height:100vh; height:100vh; width:100%; }
        .sidebar { position:fixed; inset:0 auto 0 0; width:224px; height:100vh; background: linear-gradient(180deg,#0b1220,#0f172a); color: #cbd5e1; display:flex; flex-direction:column; z-index:40; border-right:1px solid var(--line); box-shadow:14px 0 30px rgba(2,6,23,.24); }
        .sidebar-logo { padding:18px 16px; font-size:15px; font-weight:800; color:#f8fafc; border-bottom:1px solid rgba(148,163,184,.16); letter-spacing:-.02em; display:flex;align-items:center;gap:10px; }
        .brand-mark { width:34px;height:34px;display:grid;place-items:center;border-radius:11px;background:linear-gradient(145deg,#2563eb,#60a5fa);color:#fff;font-size:14px;box-shadow:0 8px 22px rgba(37,99,235,.3);flex:0 0 auto; }
        .brand-copy { min-width:0; }
        .brand-copy strong { display:block;font-size:14px; }
        .brand-copy small { display:block;color:#94a3b8;font-size:10px;letter-spacing:.08em;text-transform:uppercase;margin-top:2px; }
        .sidebar nav { padding:12px 9px; overflow-y:auto; scrollbar-width:thin; scrollbar-color:#334155 transparent; }
        .nav-section { padding:12px 11px 6px;color:#64748b;font-size:10px;font-weight:900;letter-spacing:.1em;text-transform:uppercase; }
        .sidebar nav a { display:flex; align-items:center; gap:11px; min-height:42px; padding:9px 11px; margin-bottom:4px; color:#cbd5e1; text-decoration:none; font-size:13px; font-weight:700; transition:background .15s, color .15s, transform .15s, border-color .15s; border-radius:11px; border:1px solid transparent; }
        .nav-icon { width:19px;height:19px;flex:0 0 19px;display:grid;place-items:center;color:#94a3b8;transition:color .15s; }
        .nav-icon svg { width:19px;height:19px;display:block;fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round; }
        .sidebar nav a:hover, .sidebar nav a.active { background: rgba(96,165,250,.12); color:#f8fafc; border-color: rgba(96,165,250,.22); }
        .sidebar nav a:hover .nav-icon,.sidebar nav a.active .nav-icon{color:#bfdbfe}
        .sidebar nav a.active { box-shadow:inset 3px 0 0 #60a5fa; }
        .sidebar-bottom { margin-top:auto; padding:16px; border-top:1px solid rgba(148,163,184,.14); }
        .main { margin-left:224px; width:calc(100% - 224px); height:100vh; display:flex; flex-direction:column; overflow:hidden; }
        .topbar { background: rgba(15,23,42,.9); backdrop-filter: blur(12px); padding:11px 24px; min-height:64px;border-bottom:1px solid var(--line); display:flex; align-items:center; justify-content:space-between;gap:16px; }
        .topbar-left { display:flex; align-items:center; gap:14px; min-width:0; }
        .topbar-title { font-weight:800;font-size:15px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap; }
        .topbar-nav { display:flex;align-items:center;gap:4px;padding-left:12px;border-left:1px solid var(--line); }
        .topbar-nav a { display:inline-flex;align-items:center;min-height:34px;padding:7px 10px;border:1px solid transparent;border-radius:9px;color:#94a3b8;text-decoration:none;font-size:12px;font-weight:700;transition:background .15s,border-color .15s,color .15s; }
        .topbar-nav a:hover,.topbar-nav a.active { color:#f8fafc;background:rgba(148,163,184,.08);border-color:var(--line); }
        .topbar-meta { display:flex;align-items:center;gap:12px; }
        .topbar-date { font-size:12px;color:#94a3b8;white-space:nowrap; }
        .user-chip { display:flex;align-items:center;gap:9px;padding:6px 9px;border:1px solid var(--line);border-radius:12px;background:rgba(15,23,42,.72);min-width:0; }
        .user-avatar { width:30px;height:30px;border-radius:9px;background:rgba(96,165,250,.15);color:#bfdbfe;display:grid;place-items:center;font-size:11px;font-weight:900; }
        .user-chip strong { display:block;font-size:12px;max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap; }
        .user-chip small { display:block;font-size:10px;color:#94a3b8;margin-top:1px; }
        .topbar-action { position:relative;width:36px;height:36px;display:grid;place-items:center;border:1px solid var(--line);border-radius:10px;background:rgba(15,23,42,.72);color:#cbd5e1;text-decoration:none;transition:background .15s,border-color .15s,color .15s; }
        .topbar-action:hover,.topbar-action.active { color:#f8fafc;background:rgba(148,163,184,.1);border-color:rgba(148,163,184,.3); }
        .topbar-action svg { width:18px;height:18px;fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round; }
        .topbar-count { position:absolute;right:-5px;top:-5px;min-width:17px;height:17px;padding:0 4px;display:grid;place-items:center;border-radius:999px;background:#2563eb;color:#fff;font-size:9px;font-weight:900;border:2px solid #0f172a; }
        .menu-toggle { display:none; width:38px; height:38px; border:1px solid rgba(148,163,184,.2); border-radius:9px; background: rgba(15,23,42,.8); color:#f8fafc; font-size:20px; cursor:pointer; align-items:center; justify-content:center; }
        .sidebar-close { display:none; margin-left:auto; background:rgba(15,23,42,.9); color:#f8fafc; border:1px solid rgba(148,163,184,.18); border-radius:8px; padding:5px 9px; cursor:pointer; }
        .sidebar-backdrop { display:none; position:fixed; inset:0; background:rgba(15,23,42,.48); z-index:30; }
        .content { flex:1; padding:24px; overflow-y:auto; background:linear-gradient(180deg, rgba(15,23,42,.8), rgba(2,6,23,.85)); scroll-behavior:smooth; }
        .content-inner { width:min(100%,1480px);margin-inline:auto; }
        .guest-main { margin-left:0;width:100%;min-height:100vh;height:100vh;overflow-y:auto; }
        .guest-main .content { min-height:100vh;display:grid;align-items:center;padding:28px 16px; }
        .auth-shell { width:min(440px,100%);margin:auto; }
        .auth-brand { display:flex;align-items:center;justify-content:center;gap:10px;margin-bottom:18px;color:#fff; }
        .page-title { font-size:20px; font-weight:700; margin-bottom:20px; }
        .card { background:linear-gradient(180deg,#111827,#0b1220); border:1px solid var(--line); border-radius:16px; padding:18px; box-shadow:var(--shadow); }
        .stat-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(150px, 1fr)); gap:12px; margin-bottom:18px; }
        .stat { background:linear-gradient(180deg,#111827,#0b1220); border:1px solid var(--line); border-radius:14px; padding:14px; box-shadow:0 14px 28px rgba(2,6,23,.18); }
        .stat-label { font-size:11px; color:var(--muted); margin-bottom:5px; text-transform:uppercase; letter-spacing:.04em; }
        .stat-value { font-size:24px; font-weight:800; color:var(--text); letter-spacing:-.04em; }
        table { width:100%; border-collapse:collapse; font-size:14px; }
        th { text-align:left; padding:11px 13px; border-bottom:1px solid var(--line); font-size:11px; color:#cbd5e1; text-transform:uppercase; letter-spacing:.04em; background:rgba(15,23,42,.92); }
        td { padding:12px 13px; border-bottom:1px solid rgba(148,163,184,.12); vertical-align:middle; }
        tbody tr { transition:background .14s; }
        tbody tr:hover { background: rgba(15,23,42,.7); }
        tr:last-child td { border-bottom:none; }
        .badge { display:inline-block; padding:4px 9px; border-radius:999px; font-size:11px; font-weight:800; letter-spacing:.01em; }
        .badge-green { background: rgba(52,211,153,.12); color:#a7f3d0; }
        .badge-yellow { background: rgba(251,191,36,.12); color:#fcd34d; }
        .badge-red { background: rgba(248,113,113,.12); color:#fca5a5; }
        .badge-blue { background: rgba(96,165,250,.12); color:#bfdbfe; }
        .badge-gray { background: rgba(148,163,184,.12); color:#cbd5e1; }
        .btn { min-height:40px;padding:8px 13px; border-radius:10px; border:1px solid transparent; cursor:pointer; font-size:13px; font-weight:700; text-decoration:none; display:inline-flex;align-items:center;justify-content:center;gap:7px; transition:transform .12s, box-shadow .12s, background .12s, border-color .12s; }
        .btn:hover { transform:translateY(-1px); }
        .btn-primary { background:linear-gradient(180deg,#2563eb,#1d4ed8); color:#eff6ff; box-shadow:0 8px 18px rgba(37,99,235,.28); }
        .btn-primary:hover { background:linear-gradient(180deg,#3b82f6,#2563eb); }
        .btn-danger { background:linear-gradient(180deg,#ef4444,#dc2626); color:#fff; box-shadow:0 8px 18px rgba(239,68,68,.18); }
        .btn-secondary { background:rgba(148,163,184,.07); border-color:rgba(148,163,184,.18); color:#e2e8f0; }
        .btn[disabled], button[disabled] { opacity:.55; cursor:not-allowed; }
        .muted { color:var(--muted); }
        .empty-state { color:var(--muted); text-align:center; padding:30px; background:rgba(15,23,42,.35); border:1px solid rgba(148,163,184,.12); border-radius:12px; }
        .empty-state strong,.empty-state span { display:block; }
        .empty-state strong { color:#e2e8f0;margin-bottom:5px; }
        .section-heading { font-size:13px;color:var(--muted);margin-bottom:12px;text-transform:uppercase;letter-spacing:.04em; }
        .alert-success { background: rgba(52,211,153,.12); border:1px solid rgba(52,211,153,.25); color:#bbf7d0; padding:10px 14px; border-radius:10px; margin-bottom:16px; font-size:14px; }
        .alert-error { background: rgba(248,113,113,.12); border:1px solid rgba(248,113,113,.25); color:#fecaca; padding:10px 14px; border-radius:10px; margin-bottom:16px; font-size:14px; }
        .readiness-alert { display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:16px;padding:14px 16px;border:1px solid rgba(251,191,36,.3);border-radius:14px;background:linear-gradient(135deg,rgba(120,53,15,.2),rgba(15,23,42,.92));box-shadow:0 12px 28px rgba(2,6,23,.2); }
        .readiness-main { display:flex;align-items:flex-start;gap:12px;min-width:0; }
        .readiness-icon { width:34px;height:34px;display:grid;place-items:center;flex:0 0 34px;border-radius:10px;background:rgba(251,191,36,.14);color:#fcd34d;font-weight:900; }
        .readiness-copy strong { display:block;color:#fef3c7;font-size:13px;margin-bottom:4px; }
        .readiness-copy p { color:#cbd5e1;font-size:12px;line-height:1.5; }
        .readiness-items { display:flex;gap:7px;flex-wrap:wrap;margin-top:9px; }
        .readiness-item { display:inline-flex;align-items:center;gap:6px;padding:5px 8px;border-radius:8px;background:rgba(15,23,42,.72);border:1px solid rgba(251,191,36,.16);color:#e2e8f0;font-size:11px; }
        .readiness-item::before { content:'!';display:grid;place-items:center;width:15px;height:15px;border-radius:50%;background:rgba(251,191,36,.16);color:#fcd34d;font-size:10px;font-weight:900; }
        .readiness-actions { display:flex;gap:7px;flex-wrap:wrap;justify-content:flex-end;flex:0 0 auto; }
        .readiness-actions a { min-height:34px;padding:6px 10px;font-size:11px; }
        .form-group, .field { margin-bottom:15px; }
        .form-group label, .field label { display:block; font-size:12px; font-weight:800; margin-bottom:6px; color:#e2e8f0; }
        .form-group input, .form-group select, .form-group textarea, .field input, .field select, .field textarea { width:100%; min-height:44px;padding:10px 12px; border:1px solid rgba(148,163,184,.48); border-radius:11px; font-size:14px; background:rgba(2,6,23,.18); color:var(--text); outline:none; transition:border-color .14s, box-shadow .14s, background .14s; }
        .form-group input::placeholder, .form-group textarea::placeholder, .field input::placeholder, .field textarea::placeholder { color:rgba(248,250,252,.68); }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus, .field input:focus, .field select:focus, .field textarea:focus { border-color:#fff; background: rgba(255,255,255,.03); box-shadow: 0 0 0 3px rgba(255,255,255,.12); }
        .form-error { color:#fca5a5; font-size:12px; margin-top:3px; }
        .pagination { display:flex; gap:6px; margin-top:16px; flex-wrap:wrap; }
        .pagination a, .pagination span { padding:7px 11px; border:1px solid rgba(148,163,184,.18); border-radius:9px; font-size:13px; text-decoration:none; color:#e2e8f0; background:rgba(15,23,42,.7); }
        .pagination .active span { background:linear-gradient(180deg,#2563eb,#1d4ed8); color:#eff6ff; border-color:#2563eb; }
        .admin-page-header { display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:16px;gap:14px; }
        .admin-page-header h2 { font-size:22px;font-weight:800;letter-spacing:-.04em;color:var(--text); }
        .admin-page-header p { font-size:13px;color:var(--muted);margin-top:5px;line-height:1.5;max-width:760px; }
        .admin-actions { display:flex;gap:8px;align-items:center;flex-wrap:wrap;justify-content:flex-end; }
        .admin-filter { display:flex;gap:8px;align-items:center;flex-wrap:wrap; }
        .admin-filter input, .admin-filter select { padding:9px 12px;border:1px solid rgba(255,255,255,.62);border-radius:10px;background:transparent;color:var(--text);font-size:13px;min-height:38px; }
        .admin-filter input::placeholder { color:rgba(248,250,252,.68); }
        .admin-filter input:focus, .admin-filter select:focus { border-color:#fff; background:rgba(255,255,255,.03); box-shadow:0 0 0 3px rgba(255,255,255,.12); outline:none; }
        .ui-tabs { display:flex;align-items:center;gap:5px;width:fit-content;max-width:100%;margin-bottom:18px;padding:5px;border:1px solid var(--line);border-radius:13px;background:rgba(15,23,42,.72);overflow-x:auto;scrollbar-width:thin; }
        .ui-tab { min-height:38px;display:inline-flex;align-items:center;justify-content:center;gap:7px;padding:8px 14px;border:1px solid transparent;border-radius:9px;background:transparent;color:#94a3b8;font:inherit;font-size:12px;font-weight:800;white-space:nowrap;cursor:pointer;transition:background .15s,border-color .15s,color .15s,box-shadow .15s; }
        .ui-tab:hover { color:#e2e8f0;background:rgba(148,163,184,.07); }
        .ui-tab.active { color:#fff;background:#2563eb;border-color:rgba(96,165,250,.4);box-shadow:0 7px 16px rgba(37,99,235,.2); }
        .ui-tabs-compact { padding:4px; }
        .ui-tabs-compact .ui-tab { min-height:34px;padding:7px 12px; }
        .ui-tab-panel { display:none; }
        .ui-tab-panel.active { display:block; }
        input[type="file"] { color:#f8fafc; }
        input[type="checkbox"], input[type="radio"] { accent-color:#60a5fa; }
        select option, optgroup { background:#0f172a; color:#f8fafc; }
        .table-scroll { overflow-x:auto; }
        details { border:1px solid var(--line) !important;border-radius:13px !important;margin-bottom:10px !important;overflow:hidden;background:rgba(15,23,42,.7); }
        details summary { cursor:pointer;padding:13px 15px !important;background:rgba(15,23,42,.55) !important;font-weight:800 !important;display:flex;justify-content:space-between;align-items:center;gap:10px; color:#f8fafc; }
        details[open] summary { border-bottom:1px solid var(--line); }
        .metric-row { display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:12px;margin-bottom:16px; }
        .metric-card { background:linear-gradient(180deg,#111827,#0b1220);border:1px solid var(--line);border-radius:14px;padding:14px;box-shadow:0 10px 26px rgba(2,6,23,.18); }
        .metric-card span { display:block;font-size:11px;text-transform:uppercase;letter-spacing:.04em;color:var(--muted);margin-bottom:6px; }
        .metric-card strong { display:block;font-size:22px;letter-spacing:-.04em;color:var(--text); }
        a { color:var(--blue); }
        code { color:#bfdbfe;background:rgba(96,165,250,.08);padding:2px 5px;border-radius:5px;overflow-wrap:anywhere; }
        @media (max-width: 1200px) {
            .topbar-nav { display:none; }
        }
        @media (max-width: 900px) {
            body { display:block; }
            .admin-shell { display:block; }
            .sidebar { position:fixed; top:0; bottom:0; left:0; width:min(82vw, 300px); transform:translateX(-105%); transition:transform .2s ease; box-shadow:20px 0 50px rgba(15,23,42,.25); }
            .sidebar.open { transform:translateX(0); }
            .sidebar.open + .sidebar-backdrop { display:block; }
            .sidebar-logo { display:flex; align-items:center; gap:10px; }
            .sidebar-close { display:inline-block; }
            .main { min-height:100vh; width:100%; }
            .main { margin-left:0; height:100vh; }
            .topbar { position:sticky; top:0; z-index:20; padding:10px 14px; }
            .menu-toggle { display:inline-flex; flex-shrink:0; }
            .topbar-left span { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
            .topbar-nav { display:none; }
            .content { padding:14px; }
            .stat-grid { grid-template-columns:repeat(2, minmax(0, 1fr)); gap:10px; }
            .stat { padding:12px; }
            .stat-value { font-size:22px; }
            .card { padding:14px; border-radius:9px; overflow-x:auto; }
            table { min-width:680px; }
            .form-group input, .form-group select, .form-group textarea, .field input, .field select, .field textarea { min-height:40px; }
            .admin-page-header { flex-direction:column; }
            .admin-actions { justify-content:flex-start; width:100%; }
            .admin-filter { width:100%; }
            .admin-filter input, .admin-filter select, .admin-filter .btn { width:100%; }
            .readiness-alert { flex-direction:column; }
            .readiness-actions { width:100%;justify-content:flex-start; }
            .content div[style*="grid-template-columns:1fr 1fr"],
            .content div[style*="grid-template-columns:1fr 1fr 1fr"],
            .content form[style*="grid-template-columns"] {
                grid-template-columns:1fr !important;
            }
        }
        @media (max-width: 560px) {
            .stat-grid { grid-template-columns:1fr; }
            .topbar { align-items:flex-start; gap:8px; }
            .topbar-date { display:none; }
            .user-chip strong { max-width:95px; }
            .content { padding:12px; }
        }
    </style>
</head>
<body>
<a class="skip-link" href="#main-content">Skip to main content</a>
@php
    $roleName = auth()->user()?->role?->name;
    $isLandlord = $roleName === 'LANDLORD';
    $isLandlordOwner = $isLandlord && auth()->user()->isLandlordOwner();
    $isLandlordStaff = $isLandlord && auth()->user()->isLandlordStaff();
    $isCaretaker = $roleName === 'CARETAKER';
    $isSuperAdmin = $roleName === 'SUPER_ADMIN';
    $isPlatformAdmin = in_array($roleName, ['SUPER_ADMIN', 'ADMIN'], true);
    $landlordLocked = $isLandlord && !auth()->user()->hasActiveServiceAccess();
@endphp
<div class="admin-shell">
@auth
<aside class="sidebar" id="adminSidebar" aria-hidden="true">
    <div class="sidebar-logo">
        <span class="brand-mark" aria-hidden="true">TP</span>
        <span class="brand-copy"><strong>{{ $isLandlord ? 'Landlord Portal' : 'TenantPro' }}</strong><small>{{ $landlordLocked ? 'Subscription locked' : ($isLandlordStaff ? 'Team manager workspace' : ($isLandlord ? 'Property workspace' : 'Operations console')) }}</small></span>
        <button class="sidebar-close" type="button" data-close-menu aria-label="Close menu">×</button>
    </div>
    <nav aria-label="Primary navigation">
        <div class="nav-section">Overview</div>
        <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"><i class="nav-icon"><svg viewBox="0 0 24 24"><path d="M3 11 12 3l9 8"/><path d="M5 10v10h14V10M9 20v-6h6v6"/></svg></i><span>Dashboard</span></a>
        @if($isPlatformAdmin)
            <a href="{{ route('admin.landlords.index') }}" class="{{ request()->routeIs('admin.landlords*') ? 'active' : '' }}"><i class="nav-icon"><svg viewBox="0 0 24 24"><circle cx="12" cy="7" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/></svg></i><span>Landlords</span></a>
        @endif
        @if(!$landlordLocked)
        @unless($isCaretaker)
            <div class="nav-section">Rental operations</div>
            <a href="{{ route('admin.properties.index') }}" class="{{ request()->routeIs('admin.properties*') ? 'active' : '' }}"><i class="nav-icon"><svg viewBox="0 0 24 24"><rect x="4" y="3" width="16" height="18" rx="1"/><path d="M8 7h2m4 0h2M8 11h2m4 0h2M9 21v-5h6v5"/></svg></i><span>Properties</span></a>
            <a href="{{ route('admin.units.index') }}" class="{{ request()->routeIs('admin.units*') ? 'active' : '' }}"><i class="nav-icon"><svg viewBox="0 0 24 24"><path d="M4 21V5l8-3 8 3v16M4 9h16M9 21v-5h6v5"/></svg></i><span>Units</span></a>
            <a href="{{ route('admin.tenants.index') }}" class="{{ request()->routeIs('admin.tenants*') ? 'active' : '' }}"><i class="nav-icon"><svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="4"/><path d="M2 21v-2a7 7 0 0 1 14 0v2m1-12a4 4 0 0 1 0 8m2 4v-2a7 7 0 0 0-3-5.8"/></svg></i><span>Tenants</span></a>
            <a href="{{ route('admin.invitations.index') }}" class="{{ request()->routeIs('admin.invitations*') ? 'active' : '' }}"><i class="nav-icon"><svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg></i><span>{{ $isLandlord ? 'Tenant Invitations' : 'Invitations' }}</span></a>
            <a href="{{ route('admin.invoices.index') }}" class="{{ request()->routeIs('admin.invoices*') ? 'active' : '' }}"><i class="nav-icon"><svg viewBox="0 0 24 24"><path d="M6 2h12v20l-3-2-3 2-3-2-3 2zM9 7h6m-6 4h6m-6 4h4"/></svg></i><span>Invoices</span></a>
            <a href="{{ route('admin.payments.index') }}" class="{{ request()->routeIs('admin.payments*') ? 'active' : '' }}"><i class="nav-icon"><svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18M7 15h3"/></svg></i><span>Payments</span></a>
        @endunless
        <a href="{{ route('admin.chats.index') }}" class="{{ request()->routeIs('admin.chats*') ? 'active' : '' }}"><i class="nav-icon"><svg viewBox="0 0 24 24"><path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"/><path d="M8 9h8m-8 4h5"/></svg></i><span>Chats</span></a>
        @endif
        @if($isLandlord || $isPlatformAdmin)
            <div class="nav-section">Account</div>
            <a href="{{ route('admin.settings.index') }}" class="{{ request()->routeIs('admin.settings*') ? 'active' : '' }}"><i class="nav-icon"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.87l.06.06a2 2 0 1 1-2.82 2.82l-.06-.06A1.7 1.7 0 0 0 15 19.4a1.7 1.7 0 0 0-1 .98 1.7 1.7 0 0 0-.2 1.02V22a2 2 0 1 1-4 0v-.09a1.7 1.7 0 0 0-.23-1.02 1.7 1.7 0 0 0-1-.98 1.7 1.7 0 0 0-1.87.34l-.06.06a2 2 0 1 1-2.82-2.82l.06-.06A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-.98-1 1.7 1.7 0 0 0-1.02-.2H2.5a2 2 0 1 1 0-4h.09A1.7 1.7 0 0 0 3.6 9a1.7 1.7 0 0 0 .98-1 1.7 1.7 0 0 0-.34-1.87l-.06-.06A2 2 0 1 1 6.99 3.25l.06.06A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1-.98 1.7 1.7 0 0 0 .2-1.02V2.5a2 2 0 1 1 4 0v.09c.04.35.1.7.23 1.02.18.42.52.76.98 1a1.7 1.7 0 0 0 1.87-.34l.06-.06A2 2 0 0 1 20.75 6.99l-.06.06A1.7 1.7 0 0 0 19.4 9c.25.39.35.84.31 1.3a1.7 1.7 0 0 0 .98 1 1.7 1.7 0 0 0 1.02.2h.09a2 2 0 1 1 0 4h-.09a1.7 1.7 0 0 0-1.02.2 1.7 1.7 0 0 0-.98 1z"/></svg></i><span>Settings</span></a>
            @if($isLandlordOwner)
                <a href="{{ route('admin.team.index') }}" class="{{ request()->routeIs('admin.team*') ? 'active' : '' }}"><i class="nav-icon"><svg viewBox="0 0 24 24"><circle cx="8" cy="8" r="3"/><circle cx="17" cy="9" r="2.5"/><path d="M2 20v-2a6 6 0 0 1 12 0v2m1-6a5 5 0 0 1 7 4.6V20"/></svg></i><span>Team Access</span></a>
            @endif
        @endif
        @if($isSuperAdmin && \Illuminate\Support\Facades\Route::has('admin.mpesa.sandbox-test.index'))
            <div class="nav-section">System</div>
            <a href="{{ route('admin.mpesa.sandbox-test.index') }}" class="{{ request()->routeIs('admin.mpesa.sandbox-test*') ? 'active' : '' }}"><i class="nav-icon"><svg viewBox="0 0 24 24"><path d="M3 12h18M12 3v18"/><rect x="5" y="5" width="14" height="14" rx="2"/></svg></i><span>Sandbox Pay Test</span></a>
        @endif
        @if(\Illuminate\Support\Facades\Route::has('admin.downloads.index'))
            <a href="{{ route('admin.downloads.index') }}" class="{{ request()->routeIs('admin.downloads*') ? 'active' : '' }}"><i class="nav-icon"><svg viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M7 10l5 5 5-5"/><path d="M12 15V3"/></svg></i><span>Download App</span></a>
        @endif
        @unless($isCaretaker)
        @endunless
        @if($isSuperAdmin)
            @unless(\Illuminate\Support\Facades\Route::has('admin.mpesa.sandbox-test.index'))<div class="nav-section">System</div>@endunless
            <a href="{{ route('admin.audit-logs.index') }}" class="{{ request()->routeIs('admin.audit-logs*') ? 'active' : '' }}"><i class="nav-icon"><svg viewBox="0 0 24 24"><path d="M9 3h6l1 2h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h3z"/><path d="m8 13 2 2 5-5"/></svg></i><span>Audit Log</span></a>
            <a href="{{ route('admin.deployment-tools.index') }}" class="{{ request()->routeIs('admin.deployment-tools*') ? 'active' : '' }}"><i class="nav-icon"><svg viewBox="0 0 24 24"><path d="M14.7 6.3a4 4 0 0 0-5 5L3 18l3 3 6.7-6.7a4 4 0 0 0 5-5l-3 3-3-3z"/></svg></i><span>Deployment Tools</span></a>
        @endif
    </nav>
    <div class="sidebar-bottom">
        @auth
            <form method="POST" action="/admin/logout">
                @csrf
                <button type="submit" class="btn btn-secondary" style="width:100%;">Sign out</button>
            </form>
        @else
            <a href="{{ url('/') }}" style="color:#94a3b8;text-decoration:none;font-size:13px;">Back to website</a>
        @endauth
    </div>
</aside>
<div class="sidebar-backdrop" id="sidebarBackdrop" data-close-menu></div>
@endauth
<div class="main {{ auth()->check() ? '' : 'guest-main' }}">
    @auth
    <div class="topbar">
        <div class="topbar-left">
            <button class="menu-toggle" type="button" id="menuToggle" aria-controls="adminSidebar" aria-expanded="false" aria-label="Open menu">☰</button>
            <span class="topbar-title">@yield('page-title', 'Dashboard')</span>
            <nav class="topbar-nav" aria-label="Quick navigation">
                <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">Dashboard</a>
                @if(!$landlordLocked && !$isCaretaker)
                    <a href="{{ route('admin.invitations.index') }}" class="{{ request()->routeIs('admin.invitations*') ? 'active' : '' }}">Invitations</a>
                    <a href="{{ route('admin.payments.index') }}" class="{{ request()->routeIs('admin.payments*') ? 'active' : '' }}">Payments</a>
                @endif
                @if(!$landlordLocked)
                    <a href="{{ route('admin.chats.index') }}" class="{{ request()->routeIs('admin.chats*') ? 'active' : '' }}">Chats</a>
                @endif
            </nav>
        </div>
        <div class="topbar-meta">
            <span class="topbar-date">{{ now()->format('D, d M Y') }}</span>
            <a class="topbar-action {{ request()->routeIs('admin.notifications*') ? 'active' : '' }}" href="{{ route('admin.notifications.index') }}" aria-label="Notifications{{ $adminUnreadNotifications ? ', '.$adminUnreadNotifications.' unread' : '' }}" title="Notifications">
                <svg viewBox="0 0 24 24"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/><path d="M10 21h4"/></svg>
                @if($adminUnreadNotifications > 0)<span class="topbar-count">{{ min($adminUnreadNotifications, 99) }}</span>@endif
            </a>
            <a class="topbar-action {{ request()->routeIs('admin.settings*') ? 'active' : '' }}" href="{{ route('admin.settings.index') }}" aria-label="Settings" title="Settings">
                <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19 12a7 7 0 0 0-.1-1l2-1.5-2-3.4-2.4 1A8 8 0 0 0 15 6l-.3-2.5h-4L10.5 6A8 8 0 0 0 9 7.1l-2.4-1-2 3.4 2 1.5a7 7 0 0 0 0 2l-2 1.5 2 3.4 2.4-1A8 8 0 0 0 10.5 18l.2 2.5h4L15 18a8 8 0 0 0 1.5-1.1l2.4 1 2-3.4-2-1.5a7 7 0 0 0 .1-1z"/></svg>
            </a>
            <div class="user-chip" title="{{ auth()->user()->email }}">
                <span class="user-avatar" aria-hidden="true">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(auth()->user()->name ?: auth()->user()->email, 0, 2)) }}</span>
                <span><strong>{{ auth()->user()->name ?: auth()->user()->email }}</strong><small>{{ str_replace('_', ' ', \Illuminate\Support\Str::title($roleName ?? 'User')) }}</small></span>
            </div>
        </div>
    </div>
    @endauth
    <main class="content" id="main-content" tabindex="-1">
        <div class="content-inner">
        @if(session('success'))
            <div class="alert-success" role="status" aria-live="polite">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert-error" role="alert">{{ session('error') }}</div>
        @endif
        @if(($adminReadiness['missing_count'] ?? 0) > 0)
            <section class="readiness-alert" role="alert" aria-label="Account setup warning">
                <div class="readiness-main">
                    <span class="readiness-icon" aria-hidden="true">!</span>
                    <div class="readiness-copy">
                        <strong>{{ $adminReadiness['missing_count'] }} setup {{ $adminReadiness['missing_count'] === 1 ? 'checkpoint needs' : 'checkpoints need' }} attention</strong>
                        <p>Complete the missing information to unlock all dependent operations safely.</p>
                        <div class="readiness-items">
                            @foreach($adminReadiness['missing'] as $checkpoint)
                                <span class="readiness-item" title="{{ $checkpoint['message'] }}">{{ $checkpoint['label'] }}</span>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="readiness-actions">
                    @foreach(collect($adminReadiness['missing'])->filter(fn($checkpoint) => filled($checkpoint['tab'] ?? null))->unique('tab') as $checkpoint)
                        <a class="btn btn-secondary" href="{{ route('admin.settings.index', ['tab' => $checkpoint['tab']]) }}">Fix {{ $checkpoint['label'] }}</a>
                    @endforeach
                </div>
            </section>
        @endif
        @yield('content')
        </div>
    </main>
</div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-ui-tabs]').forEach(tabList => {
        const tabs = Array.from(tabList.querySelectorAll(':scope > [data-ui-tab]'));
        if (!tabs.length) return;

        const activate = (value, focus = false, updateUrl = true) => {
            const selected = tabs.find(tab => tab.dataset.uiTab === value) || tabs[0];
            tabs.forEach(tab => {
                const active = tab === selected;
                tab.classList.toggle('active', active);
                tab.setAttribute('aria-selected', String(active));
                tab.tabIndex = active ? 0 : -1;
                const panel = document.getElementById(tab.dataset.tabPanel);
                if (panel) {
                    panel.classList.toggle('active', active);
                    panel.hidden = !active;
                }
            });

            const parameter = tabList.dataset.tabParam;
            if (updateUrl && parameter) {
                const url = new URL(window.location.href);
                url.searchParams.set(parameter, selected.dataset.uiTab);
                window.history.replaceState({}, '', url);
            }
            if (parameter) {
                document.querySelectorAll(`input[type="hidden"][name="${parameter}"]`).forEach(input => {
                    input.value = selected.dataset.uiTab;
                });
            }
            if (focus) selected.focus();
        };

        const parameter = tabList.dataset.tabParam;
        const queryValue = parameter ? new URLSearchParams(window.location.search).get(parameter) : null;
        const configuredInitial = tabList.dataset.initialTab;
        const activeInitial = tabs.find(tab => tab.classList.contains('active'))?.dataset.uiTab || tabs[0].dataset.uiTab;
        const initial = tabs.some(tab => tab.dataset.uiTab === queryValue)
            ? queryValue
            : (tabs.some(tab => tab.dataset.uiTab === configuredInitial) ? configuredInitial : activeInitial);
        activate(initial, false, false);

        tabs.forEach((tab, index) => {
            tab.addEventListener('click', () => activate(tab.dataset.uiTab));
            tab.addEventListener('keydown', event => {
                if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
                event.preventDefault();
                let next = event.key === 'Home' ? 0 : event.key === 'End' ? tabs.length - 1 : index + (event.key === 'ArrowRight' ? 1 : -1);
                next = (next + tabs.length) % tabs.length;
                activate(tabs[next].dataset.uiTab, true);
            });
        });
    });

    const sidebar = document.getElementById('adminSidebar');
    const toggle = document.getElementById('menuToggle');
    const closeButtons = document.querySelectorAll('[data-close-menu]');
    if (sidebar && toggle) {
        const openMenu = () => {
            sidebar.classList.add('open');
            sidebar.setAttribute('aria-hidden', 'false');
            toggle.setAttribute('aria-expanded', 'true');
            document.body.style.overflow = 'hidden';
        };

        const closeMenu = () => {
            sidebar.classList.remove('open');
            sidebar.setAttribute('aria-hidden', window.innerWidth <= 900 ? 'true' : 'false');
            toggle.setAttribute('aria-expanded', 'false');
            document.body.style.overflow = '';
        };

        sidebar.setAttribute('aria-hidden', window.innerWidth <= 900 ? 'true' : 'false');
        toggle.addEventListener('click', () => sidebar.classList.contains('open') ? closeMenu() : openMenu());
        closeButtons.forEach(button => button.addEventListener('click', closeMenu));
        sidebar.querySelectorAll('nav a').forEach(link => link.addEventListener('click', closeMenu));
        window.addEventListener('resize', () => {
            if (window.innerWidth > 900) closeMenu();
        });
        document.addEventListener('keydown', event => {
            if (event.key === 'Escape') closeMenu();
        });
    }

    document.querySelectorAll('form:not(#composer)').forEach(form => {
        form.addEventListener('submit', event => {
            if (event.defaultPrevented || !form.checkValidity()) return;
            const submitter = event.submitter || form.querySelector('button[type="submit"], input[type="submit"]');
            if (!submitter || submitter.dataset.submitting === 'true') return;
            if (submitter.name) {
                const submittedValue = document.createElement('input');
                submittedValue.type = 'hidden';
                submittedValue.name = submitter.name;
                submittedValue.value = submitter.value;
                form.appendChild(submittedValue);
            }
            submitter.dataset.submitting = 'true';
            submitter.disabled = true;
            if (submitter.tagName === 'BUTTON') {
                submitter.dataset.originalText = submitter.textContent;
                submitter.textContent = submitter.dataset.loadingText || 'Working…';
            }
        });
    });
});
</script>
</body>
</html>
