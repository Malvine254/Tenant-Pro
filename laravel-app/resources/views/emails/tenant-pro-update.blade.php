<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $subjectLine }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f7fb;font-family:Arial,Helvetica,sans-serif;color:#172033;">
    <span style="display:none!important;visibility:hidden;opacity:0;color:transparent;height:0;width:0;overflow:hidden;">
        {{ $preheader }}
    </span>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f4f7fb;margin:0;padding:32px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:620px;background:#ffffff;border-radius:22px;overflow:hidden;box-shadow:0 20px 60px rgba(15,23,42,.10);">
                    <tr>
                        <td style="background:linear-gradient(135deg,#1a2744,#2f80ed);padding:30px 34px;color:#ffffff;">
                            <div style="font-size:14px;letter-spacing:.14em;text-transform:uppercase;opacity:.82;">Starmax TenantPro</div>
                            <h1 style="margin:12px 0 0;font-size:28px;line-height:1.18;font-weight:800;">{{ $title }}</h1>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:32px 34px 14px;">
                            @foreach($introLines as $line)
                                <p style="margin:0 0 14px;font-size:16px;line-height:1.65;color:#334155;">{{ $line }}</p>
                            @endforeach
                        </td>
                    </tr>

                    @if(!empty($details))
                        <tr>
                            <td style="padding:0 34px 22px;">
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;">
                                    @foreach($details as $label => $value)
                                        <tr>
                                            <td style="width:42%;padding:13px 16px;background:#f8fafc;border-bottom:1px solid #e2e8f0;font-size:13px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.04em;">{{ $label }}</td>
                                            <td style="padding:13px 16px;border-bottom:1px solid #e2e8f0;font-size:15px;color:#0f172a;font-weight:600;">{{ $value }}</td>
                                        </tr>
                                    @endforeach
                                </table>
                            </td>
                        </tr>
                    @endif

                    @if($actionLabel && $actionUrl)
                        <tr>
                            <td style="padding:0 34px 30px;">
                                <a href="{{ $actionUrl }}" style="display:inline-block;background:#2f80ed;color:#ffffff;text-decoration:none;font-size:15px;font-weight:800;padding:14px 22px;border-radius:999px;">{{ $actionLabel }}</a>
                            </td>
                        </tr>
                    @endif

                    <tr>
                        <td style="padding:22px 34px;background:#f8fafc;border-top:1px solid #e2e8f0;">
                            <p style="margin:0;font-size:13px;line-height:1.6;color:#64748b;">
                                {{ $footerText ?: 'This is an automated TenantPro update from Starmax Ltd.' }}
                            </p>
                            <p style="margin:12px 0 0;font-size:12px;color:#94a3b8;">
                                © {{ date('Y') }} Starmax Ltd. Please do not reply to this automated email.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
