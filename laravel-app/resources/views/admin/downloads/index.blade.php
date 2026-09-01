@extends('admin.layout')
@section('page-title', 'App Releases')

@section('content')
<style>
    .release-head { display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap;margin-bottom:18px; }
    .release-head h2 { font-size:20px;font-weight:800;margin:0 0 4px; }
    .release-head p { color:var(--muted);font-size:13px;margin:0; }
    .release-current { display:flex;align-items:center;gap:16px;flex-wrap:wrap; }
    .release-badge { display:inline-flex;align-items:center;gap:6px;padding:4px 10px;border-radius:999px;font-size:11px;font-weight:800;letter-spacing:.03em;text-transform:uppercase; }
    .badge-current { background:rgba(34,197,94,.14);color:#86efac;border:1px solid rgba(34,197,94,.3); }
    .badge-archived { background:rgba(148,163,184,.12);color:#94a3b8;border:1px solid rgba(148,163,184,.24); }
    .badge-beta { background:rgba(245,158,11,.14);color:#fcd34d;border:1px solid rgba(245,158,11,.3); }
    .badge-required { background:rgba(239,68,68,.14);color:#fca5a5;border:1px solid rgba(239,68,68,.3); }
    .release-notes { color:var(--muted);font-size:12px;margin-top:4px;max-width:340px;white-space:pre-line; }
    .release-actions { display:flex;gap:6px;flex-wrap:wrap;justify-content:flex-end; }
    .btn-sm { padding:6px 10px;font-size:12px; }
    .upload-grid { display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px;margin-bottom:14px; }
    .upload-grid label { display:block;font-size:12px;font-weight:700;color:#cbd5e1;margin-bottom:6px; }
    .upload-grid input, .upload-grid select, .upload-grid textarea { width:100%;box-sizing:border-box; }
    .upload-toggles { display:flex;gap:10px;flex-wrap:wrap;margin-bottom:14px; }
    .upload-toggle { display:flex;align-items:center;gap:9px;padding:11px 13px;background:rgba(15,23,42,.7);border:1px solid rgba(148,163,184,.18);border-radius:11px;font-size:13px;color:#e2e8f0;flex:1;min-width:220px; }
    .upload-toggle input { width:16px;height:16px;flex:0 0 16px;accent-color:#60a5fa; }
    .link-box { font-family:ui-monospace,Menlo,Consolas,monospace;font-size:12px;background:rgba(2,6,23,.6);border:1px solid rgba(148,163,184,.18);border-radius:8px;padding:9px 11px;word-break:break-all;color:#bfdbfe; }
    .empty-state { text-align:center;padding:34px 18px;color:var(--muted); }
</style>

<div class="release-head">
    <div>
        <h2>TenantPro app releases</h2>
        <p>Publish Android builds, keep older versions downloadable, and alert tenants when a new version lands.</p>
    </div>
    @if($current)
        <div class="release-current">
            <div>
                <span class="release-badge badge-current">Live · v{{ $current->version_name }}</span>
                <p style="margin:6px 0 0;font-size:12px;color:var(--muted);">{{ $current->size_mb }} MB · {{ $current->download_count }} downloads</p>
            </div>
            <a class="btn btn-primary" href="{{ route('admin.downloads.apk.download') }}">Download current build</a>
        </div>
    @endif
</div>

<div class="card" style="margin-bottom:16px;">
    <h3 style="font-size:15px;font-weight:800;margin:0 0 4px;">Public download link</h3>
    <p style="color:var(--muted);font-size:13px;margin:0 0 10px;">Always serves whichever release is marked current. Share freely — no login required.</p>
    <div class="link-box">{{ route('downloads.apk.public') }}</div>
    <p style="color:var(--muted);font-size:12px;margin:10px 0 6px;">Version check endpoint used by the Android app:</p>
    <div class="link-box">{{ route('downloads.apk.latest-version') }}</div>
</div>

@if($canManage)
<div class="card" style="margin-bottom:16px;">
    <h3 style="font-size:15px;font-weight:800;margin:0 0 4px;">Upload a new release</h3>
    <p style="color:var(--muted);font-size:13px;margin:0 0 14px;">Build with <code>./gradlew assembleRelease</code>, then upload the APK here. Version code must be higher than the previous release.</p>

    @if($errors->any())
        <div class="alert-error" style="margin-bottom:14px;">
            <ul style="margin:0;padding-left:18px;">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.downloads.releases.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="upload-grid">
            <div>
                <label for="apk">APK file</label>
                <input id="apk" type="file" name="apk" accept=".apk,application/vnd.android.package-archive" required>
            </div>
            <div>
                <label for="version_name">Version name</label>
                <input id="version_name" name="version_name" value="{{ old('version_name') }}" placeholder="e.g. 1.4.0" required>
            </div>
            <div>
                <label for="version_code">Version code</label>
                <input id="version_code" type="number" min="1" name="version_code" value="{{ old('version_code', ($current->version_code ?? 0) + 1) }}" required>
            </div>
            <div>
                <label for="channel">Channel</label>
                <select id="channel" name="channel">
                    <option value="PRODUCTION" @selected(old('channel') === 'PRODUCTION')>Production</option>
                    <option value="BETA" @selected(old('channel') === 'BETA')>Beta</option>
                </select>
            </div>
            <div style="grid-column:1 / -1;">
                <label for="release_notes">Release notes</label>
                <textarea id="release_notes" name="release_notes" rows="3" placeholder="What changed in this version?">{{ old('release_notes') }}</textarea>
            </div>
        </div>

        <div class="upload-toggles">
            <label class="upload-toggle" for="is_current">
                <input id="is_current" type="checkbox" name="is_current" value="1" {{ old('is_current', true) ? 'checked' : '' }}>
                <span>Make this the current public download</span>
            </label>
            <label class="upload-toggle" for="notify_users">
                <input id="notify_users" type="checkbox" name="notify_users" value="1" {{ old('notify_users', true) ? 'checked' : '' }}>
                <span>Notify tenants that an update is available</span>
            </label>
            <label class="upload-toggle" for="is_mandatory">
                <input id="is_mandatory" type="checkbox" name="is_mandatory" value="1" {{ old('is_mandatory') ? 'checked' : '' }}>
                <span>Mark as a required update</span>
            </label>
        </div>

        <button type="submit" class="btn btn-primary" data-loading-text="Uploading…">Publish release</button>
    </form>
</div>
@endif

<div class="card">
    <h3 style="font-size:15px;font-weight:800;margin:0 0 12px;">All versions</h3>

    @if($releases->isEmpty())
        <div class="empty-state">
            <p style="margin:0 0 6px;font-weight:700;color:#e2e8f0;">No releases published yet</p>
            <p style="margin:0;font-size:13px;">{{ $canManage ? 'Upload your first APK above to make it downloadable.' : 'A super administrator needs to publish the first build.' }}</p>
        </div>
    @else
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Version</th>
                        <th>Status</th>
                        <th>Size</th>
                        <th>Downloads</th>
                        <th>Published</th>
                        <th>Notified</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($releases as $release)
                        <tr>
                            <td>
                                <strong>v{{ $release->version_name }}</strong>
                                <div style="font-size:12px;color:var(--muted);">Code {{ $release->version_code }}</div>
                                @if($release->release_notes)
                                    <div class="release-notes">{{ Str::limit($release->release_notes, 140) }}</div>
                                @endif
                            </td>
                            <td>
                                <span class="release-badge {{ $release->is_current ? 'badge-current' : 'badge-archived' }}">{{ $release->is_current ? 'Current' : 'Archived' }}</span>
                                @if($release->channel === 'BETA')<span class="release-badge badge-beta">Beta</span>@endif
                                @if($release->is_mandatory)<span class="release-badge badge-required">Required</span>@endif
                                @unless($release->exists())<span class="release-badge badge-required">File missing</span>@endunless
                            </td>
                            <td>{{ $release->size_mb }} MB</td>
                            <td>{{ $release->download_count }}</td>
                            <td>
                                {{ $release->created_at?->format('d M Y') }}
                                <div style="font-size:12px;color:var(--muted);">{{ $release->uploader?->name ?? 'System' }}</div>
                            </td>
                            <td>
                                @if($release->notified_at)
                                    <span style="color:#86efac;font-size:12px;">{{ $release->notified_at->diffForHumans() }}</span>
                                @else
                                    <span style="color:var(--muted);font-size:12px;">Not sent</span>
                                @endif
                            </td>
                            <td>
                                <div class="release-actions">
                                    <a class="btn btn-secondary btn-sm" href="{{ route('admin.downloads.release.download', $release) }}">Download</a>
                                    @if($canManage)
                                        @unless($release->is_current)
                                            <form method="POST" action="{{ route('admin.downloads.releases.current', $release) }}">
                                                @csrf @method('PATCH')
                                                <button type="submit" class="btn btn-secondary btn-sm">Make current</button>
                                            </form>
                                        @endunless
                                        <form method="POST" action="{{ route('admin.downloads.releases.notify', $release) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-secondary btn-sm" data-loading-text="Sending…">{{ $release->notified_at ? 'Resend alert' : 'Notify tenants' }}</button>
                                        </form>
                                        @unless($release->is_current)
                                            <form method="POST" action="{{ route('admin.downloads.releases.destroy', $release) }}" onsubmit="return confirm('Delete release v{{ $release->version_name }}? The APK file will be removed from the server.');">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                            </form>
                                        @endunless
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<div class="card" style="margin-top:16px;">
    <h3 style="font-size:15px;font-weight:800;margin:0 0 10px;">Installation guide</h3>
    <ol style="font-size:13px;color:var(--muted);line-height:1.8;padding-left:20px;margin:0;">
        <li>Download the APK on the Android device, or transfer it there.</li>
        <li>Open the file and allow installs from this source when prompted.</li>
        <li>Tap install, then sign in with your TenantPro credentials.</li>
        <li>Existing users can install straight over the old version — data is preserved.</li>
    </ol>
</div>
@endsection
