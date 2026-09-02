@extends('admin.layout')
@section('page-title', 'App Releases')

@section('content')
<style>
    .release-notes { color:var(--muted);font-size:12px;margin-top:4px;max-width:340px;white-space:pre-line; }
    .release-actions { display:flex;gap:6px;flex-wrap:wrap;justify-content:flex-end; }
    .release-actions .btn { min-height:32px;padding:6px 10px;font-size:12px; }
    .release-toggles { display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:10px;margin-bottom:16px; }
    .link-box { font-family:ui-monospace,Menlo,Consolas,monospace;font-size:12px;background:rgba(2,6,23,.6);border:1px solid rgba(148,163,184,.18);border-radius:8px;padding:9px 11px;word-break:break-all;color:#bfdbfe; }
    .empty-state { text-align:center;padding:34px 18px;color:var(--muted); }
</style>

<div class="admin-page-header">
    <div>
        <h2>Starmax Tenant Services app releases</h2>
        <p>Publish Android builds, keep older versions downloadable, and alert tenants when a new version lands.</p>
    </div>
    @if($current)
        <div class="admin-actions">
            <div style="text-align:right;">
                <span class="badge badge-green">Live · v{{ $current->version_name }}</span>
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
    <p style="color:var(--muted);font-size:13px;margin:0 0 16px;">
        Build with <code>./gradlew assembleRelease</code>, then upload the APK here.
        @if($latest)
            Fields are prefilled from <strong>v{{ $latest->version_name }} ({{ $latest->version_code }})</strong> — adjust what changed.
        @else
            Version code must increase with every release.
        @endif
    </p>

    @if($errors->any())
        <div class="alert-error" style="margin-bottom:14px;">
            <ul style="margin:0;padding-left:18px;">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.downloads.releases.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="form-grid">
            <div class="field">
                <label for="apk">APK file</label>
                <input id="apk" type="file" name="apk" accept=".apk,application/vnd.android.package-archive" required>
                <small>Release or debug build, up to 200 MB.</small>
            </div>
            <div class="field">
                <label for="version_name">Version name</label>
                <input id="version_name" name="version_name" value="{{ old('version_name', $latest?->nextVersionName()) }}" placeholder="e.g. 1.4.0" required>
                <small>Must match <code>versionName</code> in build.gradle.kts.@if($latest) Previous: v{{ $latest->version_name }}.@endif</small>
            </div>
            <div class="field">
                <label for="version_code">Version code</label>
                <input id="version_code" type="number" min="1" name="version_code" value="{{ old('version_code', ($latest->version_code ?? 0) + 1) }}" required>
                <small>Must match <code>versionCode</code> and be unique.@if($latest) Previous: {{ $latest->version_code }}.@endif</small>
            </div>
            <div class="field">
                <label for="channel">Channel</label>
                @php($selectedChannel = old('channel', $latest->channel ?? 'PRODUCTION'))
                <select id="channel" name="channel">
                    <option value="PRODUCTION" @selected($selectedChannel === 'PRODUCTION')>Production</option>
                    <option value="BETA" @selected($selectedChannel === 'BETA')>Beta</option>
                </select>
                <small>Beta builds stay downloadable but are labelled for testers.</small>
            </div>
            <div class="field field-wide">
                <label for="release_notes">Release notes</label>
                <textarea id="release_notes" name="release_notes" rows="3" placeholder="What changed in this version?">{{ old('release_notes', $latest?->release_notes) }}</textarea>
                <small>Shown to tenants in the update notification.@if($latest?->release_notes) Carried over from the previous release — replace with this version's changes.@endif</small>
            </div>
        </div>

        <div class="release-toggles">
            <label class="check-row" for="is_current">
                <input id="is_current" type="checkbox" name="is_current" value="1" {{ old('is_current', true) ? 'checked' : '' }}>
                <span>Make this the current public download</span>
            </label>
            <label class="check-row" for="notify_users">
                <input id="notify_users" type="checkbox" name="notify_users" value="1" {{ old('notify_users', true) ? 'checked' : '' }}>
                <span>Notify tenants that an update is available</span>
            </label>
            <label class="check-row" for="is_mandatory">
                <input id="is_mandatory" type="checkbox" name="is_mandatory" value="1" {{ old('is_mandatory', $latest->is_mandatory ?? false) ? 'checked' : '' }}>
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
                                <span class="badge {{ $release->is_current ? 'badge-green' : 'badge-gray' }}">{{ $release->is_current ? 'Current' : 'Archived' }}</span>
                                @if($release->channel === 'BETA')<span class="badge badge-yellow">Beta</span>@endif
                                @if($release->is_mandatory)<span class="badge badge-red">Required</span>@endif
                                @unless($release->exists())<span class="badge badge-red">File missing</span>@endunless
                            </td>
                            <td>{{ $release->size_mb }} MB</td>
                            <td>{{ $release->download_count }}</td>
                            <td>
                                {{ $release->created_at?->format('d M Y') }}
                                <div style="font-size:12px;color:var(--muted);">{{ $release->uploader?->name ?? 'System' }}</div>
                            </td>
                            <td>
                                @if($release->notified_at)
                                    <span class="badge badge-blue">{{ $release->notified_at->diffForHumans() }}</span>
                                @else
                                    <span style="color:var(--muted);font-size:12px;">Not sent</span>
                                @endif
                            </td>
                            <td>
                                <div class="release-actions">
                                    <a class="btn btn-secondary" href="{{ route('admin.downloads.release.download', $release) }}">Download</a>
                                    @if($canManage)
                                        @unless($release->is_current)
                                            <form method="POST" action="{{ route('admin.downloads.releases.current', $release) }}">
                                                @csrf @method('PATCH')
                                                <button type="submit" class="btn btn-secondary">Make current</button>
                                            </form>
                                        @endunless
                                        <form method="POST" action="{{ route('admin.downloads.releases.notify', $release) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-secondary" data-loading-text="Sending…">{{ $release->notified_at ? 'Resend alert' : 'Notify tenants' }}</button>
                                        </form>
                                        @unless($release->is_current)
                                            <form method="POST" action="{{ route('admin.downloads.releases.destroy', $release) }}" onsubmit="return confirm('Delete release v{{ $release->version_name }}? The APK file will be removed from the server.');">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-danger">Delete</button>
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
        <li>Tap install, then sign in with your Starmax Tenant Services credentials.</li>
        <li>Existing users can install straight over the old version — data is preserved.</li>
    </ol>
</div>
@endsection
