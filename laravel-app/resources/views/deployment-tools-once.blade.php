<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Token Deployment Tools</title>
    <style>
        body { font-family: Arial, sans-serif; background:#0f172a; color:#e2e8f0; margin:0; padding:20px; }
        .wrap { max-width:900px; margin:0 auto; }
        .card { background:#111827; border:1px solid #334155; border-radius:12px; padding:18px; margin-bottom:14px; }
        h1 { margin:0 0 10px; font-size:24px; }
        h2 { margin:0 0 10px; font-size:18px; }
        table { width:100%; border-collapse:collapse; font-size:14px; }
        th, td { border-bottom:1px solid #1f2937; text-align:left; padding:8px; }
        th { color:#94a3b8; width:280px; }
        .ok { color:#22c55e; font-weight:700; }
        .bad { color:#f87171; font-weight:700; }
        .warn { color:#f59e0b; font-weight:700; }
        .btn { background:#22c55e; border:none; color:#052e16; padding:12px 16px; border-radius:8px; font-weight:700; cursor:pointer; }
        .btn-secondary { background:#334155; color:#e2e8f0; text-align:left; width:100%; }
        .action-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:10px; }
        .hint { display:block; color:#93c5fd; font-size:12px; font-weight:400; margin-top:4px; }
        .note { color:#93c5fd; font-size:13px; }
        .error { background:#7f1d1d; border:1px solid #dc2626; color:#fecaca; padding:10px; border-radius:8px; margin-bottom:12px; }
        .success { background:#14532d; border:1px solid #22c55e; color:#bbf7d0; padding:10px; border-radius:8px; margin-bottom:12px; }
        pre { white-space:pre-wrap; background:#020617; border:1px solid #1e293b; padding:12px; border-radius:8px; font-size:12px; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <h1>Token Deployment Tools</h1>
        <p class="note">This page runs deployment commands whenever the URL token matches the current server .env token.</p>
    </div>

    @if(session('error'))
        <div class="error">{{ session('error') }}</div>
    @endif

    @if(session('success'))
        <div class="success">{{ session('success') }}</div>
    @endif

    <div class="card">
        <h2>Access Check</h2>
        <table>
            <tr><th>Token source</th><td>{{ $tokenSource }}</td></tr>
            <tr><th>Token configured in .env</th><td>{!! $isConfigured ? '<span class="ok">Yes</span>' : '<span class="bad">No</span>' !!}</td></tr>
            <tr><th>Token valid in URL</th><td>{!! $isValidToken ? '<span class="ok">Yes</span>' : '<span class="bad">No</span>' !!}</td></tr>
        </table>
    </div>

    <div class="card">
        <h2>Server Status</h2>
        <table>
            <tr><th>PHP Version</th><td>{{ $status['php_version'] }}</td></tr>
            <tr><th>Laravel Version</th><td>{{ $status['laravel_version'] }}</td></tr>
            <tr><th>Environment</th><td>{{ $status['environment'] }}</td></tr>
            <tr><th>APP_URL</th><td>{{ $status['app_url'] }}</td></tr>
            <tr><th>APP_KEY set</th><td>{!! $status['app_key_set'] ? '<span class="ok">Yes</span>' : '<span class="bad">No</span>' !!}</td></tr>
            <tr><th>vendor/autoload.php</th><td>{!! $status['vendor_autoload'] ? '<span class="ok">Present</span>' : '<span class="bad">Missing</span>' !!}</td></tr>
            <tr><th>storage writable</th><td>{!! $status['storage_writable'] ? '<span class="ok">Yes</span>' : '<span class="bad">No</span>' !!}</td></tr>
            <tr><th>bootstrap/cache writable</th><td>{!! $status['bootstrap_cache_writable'] ? '<span class="ok">Yes</span>' : '<span class="bad">No</span>' !!}</td></tr>
        </table>
    </div>

    <div class="card">
        <h2>Run Full Deployment Sequence</h2>
        <p class="note">Sequence: vendor check, APP_KEY generation (if missing), storage link, cache clear, migrate, seed, config cache, route cache.</p>

        @if($isConfigured && $isValidToken)
            <form method="POST" action="{{ route('deployment-tools.once.run') }}">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <button type="submit" class="btn">Run Full Deployment</button>
            </form>
        @else
            <p class="bad">Cannot run: token missing/invalid or not configured.</p>
        @endif
    </div>

    <div class="card">
        <h2>Maintenance Commands</h2>
        <p class="note">These commands can run whenever the URL token is valid.</p>

        @if($isConfigured && $isValidToken)
            <form method="POST" action="{{ route('deployment-tools.once.run') }}">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <div class="action-grid">
                    @foreach($maintenanceActions as $action => $label)
                        <button type="submit" name="action" value="{{ $action }}" class="btn btn-secondary">
                            {{ $label }}
                            <span class="hint">{{ $commandHints[$action] ?? $action }}</span>
                        </button>
                    @endforeach
                </div>
            </form>
        @else
            <p class="bad">Cannot run: token missing/invalid or not configured.</p>
        @endif
    </div>

    @if(session('command_output'))
        <div class="card">
            <h2>Last Output</h2>
            <pre>{{ session('command_output') }}</pre>
        </div>
    @endif
</div>
</body>
</html>
