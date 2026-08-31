@extends('admin.layout')
@section('page-title', 'Deployment Tools')

@section('content')
<div class="admin-page-header">
    <div><h2>Deployment tools</h2><p>Restricted production operations for super administrators. Every submitted action is recorded in the audit log.</p></div>
</div>

@if(!empty($isPublicTestingTool))
    <div class="alert-error">
        Testing mode: no admin login is required, but a valid DEPLOYMENT_TOOL_TOKEN is required before commands run.
    </div>
@endif

<div class="card" style="margin-bottom:16px;">
    <h3 style="margin-bottom:10px;">Server Status</h3>
    <table>
        <tbody>
            <tr><th style="width:260px;">PHP Version</th><td>{{ $status['php_version'] }}</td></tr>
            <tr><th>Laravel Version</th><td>{{ $status['laravel_version'] }}</td></tr>
            <tr><th>Environment</th><td>{{ $status['environment'] }}</td></tr>
            <tr><th>APP_URL</th><td>{{ $status['app_url'] }}</td></tr>
            <tr><th>.env file</th><td>{!! $status['env_file_exists'] ? '<span class="badge badge-green">Present</span>' : '<span class="badge badge-red">Missing</span>' !!}</td></tr>
            <tr><th>APP_KEY</th><td>{!! $status['app_key_set'] ? '<span class="badge badge-green">Set</span>' : '<span class="badge badge-red">Missing</span>' !!}</td></tr>
            <tr><th>vendor/autoload.php</th><td>{!! $status['vendor_autoload'] ? '<span class="badge badge-green">Present</span>' : '<span class="badge badge-red">Missing</span>' !!}</td></tr>
            <tr><th>storage writable</th><td>{!! $status['storage_writable'] ? '<span class="badge badge-green">Yes</span>' : '<span class="badge badge-red">No</span>' !!}</td></tr>
            <tr><th>bootstrap/cache writable</th><td>{!! $status['bootstrap_cache_writable'] ? '<span class="badge badge-green">Yes</span>' : '<span class="badge badge-red">No</span>' !!}</td></tr>
        </tbody>
    </table>
</div>

<div class="card" style="margin-bottom:16px;">
    <h3 style="margin-bottom:10px;">Run an operation</h3>
    <p style="font-size:13px;color:#94a3b8;margin-bottom:14px;">Select one operation, review its command, and confirm intentionally. Destructive database and key operations are unavailable in production.</p>

    <form method="POST" action="{{ $formAction ?? route('admin.deployment-tools.run') }}">
        @csrf

        @if($toolTokenRequired)
            <div class="form-group" style="max-width:420px;">
                <label>Deployment Tool Token</label>
                <input type="password" name="tool_token" required placeholder="Value of DEPLOYMENT_TOOL_TOKEN">
            </div>
        @endif

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:10px;margin-bottom:14px;">
            @foreach($availableActions as $action => $label)
                <label style="display:flex;gap:10px;align-items:flex-start;border:1px solid rgba(255,255,255,.62);border-radius:8px;padding:10px 12px;cursor:pointer;background:transparent;color:#e2e8f0;">
                    <input type="radio" name="action" value="{{ $action }}" @checked($loop->first) style="margin-top:3px;">
                    <span>
                        <strong style="display:block;color:#f8fafc;">{{ $label }}</strong>
                        <span style="display:block;color:#94a3b8;font-size:12px;">{{ $commandHints[$action] ?? $action }}</span>
                    </span>
                </label>
            @endforeach
        </div>

        <label style="display:flex;align-items:flex-start;gap:10px;margin-bottom:14px;color:#cbd5e1;font-size:13px;">
            <input type="checkbox" name="confirm_operation" value="1" required style="margin-top:3px;">
            <span>I have reviewed the selected command and understand that it changes the production server.</span>
        </label>
        <button type="submit" class="btn btn-primary" data-loading-text="Running operation…">Run selected operation</button>
    </form>
</div>

@if(session('command_output'))
    <div class="card">
        <h3 style="margin-bottom:10px;">Last Command Output</h3>
        <pre style="background:#0f172a;color:#e2e8f0;padding:12px;border-radius:8px;overflow:auto;font-size:12px;line-height:1.5;">{{ session('command_output') }}</pre>
    </div>
@endif
@endsection
