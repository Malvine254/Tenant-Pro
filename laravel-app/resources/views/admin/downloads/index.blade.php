@extends('admin.layout')
@section('page-title', 'Download App')

@section('content')
<div style="max-width:700px;">
    <h2 style="font-size:20px;font-weight:600;margin-bottom:20px;">Download TenantPro App</h2>

    <div class="card" style="margin-bottom:20px;">
        <h3 style="font-size:15px;font-weight:700;margin-bottom:10px;">Android App (APK)</h3>
        <p style="color:#64748b;font-size:14px;margin-bottom:12px;">
            Download the TenantPro mobile application for Android devices. This app allows tenants to view invoices, make payments, and manage maintenance requests on the go.
        </p>

        @if($apkExists)
            <div style="background:#dcfce7;border:1px solid #86efac;border-radius:9px;padding:12px 14px;margin-bottom:14px;">
                <p style="font-size:13px;color:#166534;">
                    ✓ App is ready for download
                    @if($apkSize)
                        <br><span style="font-size:12px;color:#15803d;margin-top:4px;display:block;">
                            File size: {{ number_format($apkSize / 1024 / 1024, 2) }} MB
                        </span>
                    @endif
                </p>
            </div>

            <div style="display:flex;gap:10px;flex-wrap:wrap;">
                <a href="{{ route('admin.downloads.apk.download') }}" class="btn btn-primary" style="text-decoration:none;">
                    📱 Download APK (Admin)
                </a>
                <a href="{{ route('downloads.apk.public') }}" class="btn btn-secondary" style="text-decoration:none;" target="_blank">
                    🔗 Public Link
                </a>
            </div>

            <div style="background:#f8fafc;border:1px solid #dbe4ef;border-radius:9px;padding:12px 14px;margin-top:14px;">
                <p style="font-size:12px;color:#64748b;margin-bottom:6px;font-weight:600;">Public Download Link:</p>
                <p style="font-size:13px;word-break:break-all;color:#475569;font-family:monospace;background:#fff;padding:8px;border-radius:6px;border:1px solid #e2e8f0;">
                    {{ route('downloads.apk.public') }}
                </p>
                <p style="font-size:12px;color:#64748b;margin-top:8px;">
                    Share this link with users who want to download the app without needing to log in.
                </p>
            </div>
        @else
            <div style="background:#fee2e2;border:1px solid #fca5a5;border-radius:9px;padding:12px 14px;margin-bottom:14px;">
                <p style="font-size:13px;color:#991b1b;">
                    ⚠ APK file not found
                </p>
                <p style="font-size:12px;color:#7f1d1d;margin-top:6px;">
                    The APK file is not yet available. Please build the Android app first using Gradle.
                </p>
            </div>

            <div style="background:#f1f5f9;border:1px solid #cbd5e1;border-radius:9px;padding:12px 14px;">
                <p style="font-size:12px;color:#475569;">
                    <strong>Build instructions:</strong><br>
                    1. Navigate to the tenant-app directory<br>
                    2. Run: <code style="background:#fff;padding:2px 6px;border-radius:4px;font-size:11px;">./gradlew build</code><br>
                    3. The APK will be generated at: <code style="background:#fff;padding:2px 6px;border-radius:4px;font-size:11px;">tenant-app/app/build/outputs/apk/debug/app-debug.apk</code>
                </p>
            </div>
        @endif
    </div>

    <div class="card">
        <h3 style="font-size:15px;font-weight:700;margin-bottom:10px;">Installation Guide</h3>
        <ol style="font-size:13px;color:#64748b;line-height:1.8;padding-left:20px;">
            <li>Click the download button to get the APK file</li>
            <li>Transfer the file to your Android device</li>
            <li>Open a file manager on your device and locate the APK</li>
            <li>Tap the file to install (you may need to enable "Unknown Sources" in settings)</li>
            <li>Open the app and log in with your tenant credentials</li>
        </ol>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const downloadButtons = document.querySelectorAll('a[href*="/download/apk"]');
        downloadButtons.forEach(btn => {
            if (btn.getAttribute('href') === '{{ route('downloads.apk.public') }}') {
                btn.addEventListener('click', function(e) {
                    if (!e.target.hasAttribute('target')) {
                        e.preventDefault();
                        const link = this.href;
                        const a = document.createElement('a');
                        a.href = link;
                        a.download = 'TenantPro-App.apk';
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                    }
                });
            }
        });
    });
</script>
@endsection
