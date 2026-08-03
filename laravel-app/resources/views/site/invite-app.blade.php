@extends('site.layout')

@section('content')
<section class="section" style="max-inline-size:780px;margin:40px auto;">
    <div class="card" style="padding:28px;border-radius:18px;border:1px solid #e2e8f0;background:#ffffff;box-shadow:0 20px 50px rgba(15,23,42,.08);">
        <p class="eyebrow" style="margin-block-end:8px;">Tenant onboarding</p>
        <h2 style="margin:0 0 12px;font-size:30px;line-height:1.2;">Your invitation is ready</h2>
        <p style="margin:0 0 20px;color:#475569;font-size:15px;line-height:1.7;">
            Use the steps below to complete onboarding in the TenantPro Android app.
            Your invitation code is shown here in case you need to enter it manually.
        </p>

        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;padding:14px 16px;border:1px dashed #c7d2fe;border-radius:12px;background:#f8faff;margin-block-end:18px;">
            <span style="font-size:12px;color:#64748b;text-transform:uppercase;letter-spacing:.08em;font-weight:700;">Invitation code</span>
            <strong style="font-size:28px;letter-spacing:.15em;color:#1e3a8a;">{{ $code }}</strong>
        </div>

        <ol style="margin:0 0 20px 18px;color:#334155;line-height:1.9;font-size:15px;">
            <li>Install TenantPro on your Android phone.</li>
            <li>Sign in using the login details from your invitation email.</li>
            <li>In the app, open Account and select Accept Invitation.</li>
            <li>Enter the code above to link your unit.</li>
        </ol>

        <div style="display:flex;gap:12px;flex-wrap:wrap;">
            <a href="{{ $deepLink }}" class="btn btn-primary" style="min-inline-size:210px;text-align:center;">Open in TenantPro App</a>
            <a href="{{ $downloadUrl }}" class="btn btn-secondary" style="min-inline-size:210px;text-align:center;">Download Android APK</a>
        </div>

        <p style="margin:18px 0 0;color:#64748b;font-size:13px;line-height:1.7;">
            If the app does not open automatically, install the APK first and then return to this page.
            If you forgot your password, use Reset Password on the app login screen.
        </p>
    </div>
</section>

<script>
    (function () {
        var deepLink = "{{ $deepLink }}";
        var opened = false;

        function openApp() {
            if (opened) return;
            opened = true;
            window.location.href = deepLink;
        }

        setTimeout(openApp, 300);
    })();
</script>
@endsection
