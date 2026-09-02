<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Starmax Tenant Services Sandbox Payment Test</title>
    <style>
        :root {
            --bg: #07111f;
            --bg-soft: #0f172a;
            --panel: #101827;
            --panel-2: #111827;
            --line: rgba(148,163,184,.18);
            --text: #f8fafc;
            --muted: #94a3b8;
            --primary: #60a5fa;
            --primary-strong: #2563eb;
            --success: #34d399;
            --danger: #f87171;
            --shadow: 0 18px 42px rgba(2,6,23,.35);
        }
        * { box-sizing:border-box; }
        body {
            margin:0;
            min-height:100vh;
            display:grid;
            place-items:center;
            background: radial-gradient(circle at top, rgba(96,165,250,.15), transparent 28%), var(--bg);
            color:var(--text);
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }
        .shell {
            width:min(100%, 920px);
            padding: 28px 18px;
        }
        .card {
            background: linear-gradient(180deg, rgba(17,24,39,.96), rgba(15,23,42,.96));
            border: 1px solid var(--line);
            border-radius: 22px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }
        .header {
            padding: 22px 24px;
            border-bottom: 1px solid var(--line);
            background: rgba(15,23,42,.7);
        }
        .header h1 {
            margin:0;
            font-size: clamp(1.5rem, 2vw, 2.2rem);
            letter-spacing: -.04em;
        }
        .header p {
            margin-top: 8px;
            color: var(--muted);
            line-height: 1.6;
        }
        .body {
            padding: 22px 24px 24px;
        }
        .grid {
            display:grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
        }
        .field {
            display:flex;
            flex-direction:column;
            gap:8px;
        }
        .field label {
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .07em;
            text-transform: uppercase;
            color: #dbeafe;
        }
        .field input, .field select {
            width:100%;
            min-height: 46px;
            border-radius: 12px;
            border: 1px solid rgba(148,163,184,.22);
            background: rgba(15,23,42,.82);
            color: var(--text);
            padding: 11px 12px;
            font-size: 15px;
        }
        .field input:focus, .field select:focus {
            outline:none;
            border-color: rgba(96,165,250,.55);
            box-shadow: 0 0 0 3px rgba(96,165,250,.12);
        }
        .actions {
            margin-top: 18px;
            display:flex;
            justify-content:flex-end;
        }
        .btn {
            border: none;
            cursor: pointer;
            border-radius: 12px;
            background: linear-gradient(180deg, var(--primary), var(--primary-strong));
            color: white;
            min-height: 44px;
            padding: 0 18px;
            font-weight: 800;
            box-shadow: 0 12px 20px rgba(37,99,235,.28);
        }
        .notice {
            margin-bottom: 16px;
            background: rgba(52,211,153,.12);
            color: #bbf7d0;
            border: 1px solid rgba(52,211,153,.24);
            border-radius: 12px;
            padding: 12px 14px;
            font-size: 14px;
            line-height: 1.5;
        }
        .error {
            margin-bottom: 16px;
            background: rgba(239,68,68,.12);
            color: #fecaca;
            border: 1px solid rgba(239,68,68,.3);
            border-radius: 12px;
            padding: 12px 14px;
            font-size: 14px;
            line-height: 1.5;
        }
        .meta {
            margin-top: 18px;
            color: var(--muted);
            font-size: 13px;
            display:flex;
            flex-wrap:wrap;
            gap: 10px 18px;
        }
        @media (max-width: 640px) {
            .body, .header { padding-left:16px; padding-right:16px; }
            .actions { justify-content:stretch; }
            .btn { width:100%; }
        }
    </style>
</head>
<body>
    <div class="shell">
        <div class="card">
            <div class="header">
                <h1>Starmax Tenant Services sandbox payment test</h1>
                <p>Use this form to simulate a customer payment in Daraja sandbox mode without going through the admin panel.</p>
            </div>

            <div class="body">
                @if (session('success'))
                    <div class="notice">{{ session('success') }}</div>
                @endif

                @if ($errors->any())
                    <div class="error">{{ $errors->first() }}</div>
                @endif

                <form method="POST" action="{{ route('public.mpesa.sandbox-test.store') }}">
                    @csrf

                    <div class="grid">
                        <div class="field">
                            <label for="payment_type">Payment type</label>
                            <select id="payment_type" name="payment_type">
                                <option value="PAYBILL" {{ old('payment_type', 'PAYBILL') === 'PAYBILL' ? 'selected' : '' }}>PayBill</option>
                                <option value="TILL" {{ old('payment_type') === 'TILL' ? 'selected' : '' }}>Till</option>
                            </select>
                        </div>

                        <div class="field">
                            <label for="short_code">PayBill / Till number</label>
                            <input id="short_code" name="short_code" value="{{ old('short_code', '174379') }}" required>
                        </div>

                        <div class="field">
                            <label for="phone_number">Customer phone number</label>
                            <input id="phone_number" name="phone_number" value="{{ old('phone_number', '254712345678') }}" required>
                        </div>

                        <div class="field">
                            <label for="amount">Amount</label>
                            <input id="amount" name="amount" type="number" min="1" step="0.01" value="{{ old('amount', 500) }}" required>
                        </div>

                        <div class="field" style="grid-column: 1 / -1;">
                            <label for="account_reference">Account reference</label>
                            <input id="account_reference" name="account_reference" value="{{ old('account_reference', 'Starmax Tenant Services Sandbox') }}">
                        </div>
                    </div>

                    <div class="actions">
                        <button class="btn" type="submit">Simulate payment</button>
                    </div>
                </form>

                <div class="meta">
                    <span>Mode: {{ strtoupper($environment) }}</span>
                    <span>Simulation: {{ $simulate ? 'Enabled' : 'Disabled' }}</span>
                    <span>Use for testing only</span>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
