@extends('admin.layout')
@section('page-title', 'Deployment Tools')

@section('content')
<div class="page-title">Deployment Tools</div>

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
    <h3 style="margin-bottom:10px;">Run Operation</h3>
    <p style="font-size:13px;color:#64748b;margin-bottom:14px;">Use this when GoDaddy terminal access is unavailable.</p>

    <form method="POST" action="{{ route('admin.deployment-tools.run') }}">
        @csrf

        @if($toolTokenRequired)
            <div class="form-group" style="max-width:420px;">
                <label>Deployment Tool Token</label>
                <input type="password" name="tool_token" required placeholder="Value of DEPLOYMENT_TOOL_TOKEN">
            </div>
        @endif

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:10px;">
            @foreach($availableActions as $action => $label)
                <button type="submit" name="action" value="{{ $action }}" class="btn btn-secondary" style="text-align:left;padding:10px 12px;">{{ $label }}</button>
            @endforeach
        </div>
    </form>
</div>

@if(session('command_output'))
    <div class="card">
        <h3 style="margin-bottom:10px;">Last Command Output</h3>
        <pre style="background:#0f172a;color:#e2e8f0;padding:12px;border-radius:8px;overflow:auto;font-size:12px;line-height:1.5;">{{ session('command_output') }}</pre>
    </div>
@endif
@endsection
