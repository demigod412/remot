<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin') — {{ gs()->site_name ?? 'Job Station' }}</title>
    <script>(function(){if(localStorage.getItem('jobstation-theme')==='dark')document.documentElement.classList.add('dark');})()</script>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        html, body { height: 100%; margin: 0; }
        body {
            background: var(--bg);
            color: var(--fg);
            font-family: 'Poppins', system-ui, sans-serif;
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
        }

        /* ── LEFT: form panel ── */
        .adm-form-panel {
            display: flex;
            flex-direction: column;
            padding: 56px;
            background: var(--bg);
            position: relative;
        }
        .adm-form-inner {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            max-width: 420px;
            width: 100%;
            margin: 0 auto;
        }

        /* ── RIGHT: brand panel ── */
        .adm-brand-panel {
            position: relative;
            overflow: hidden;
            background: linear-gradient(160deg, #0B1020 0%, #111827 60%, #1E1B4B 100%);
            color: white;
            padding: 56px;
            display: flex;
            flex-direction: column;
        }
        .adm-brand-panel::before {
            content: '';
            position: absolute;
            inset: 0;
            background: repeating-linear-gradient(-45deg, rgba(255,255,255,0.02) 0 2px, transparent 2px 22px);
            pointer-events: none;
        }

        /* ── Inputs ── */
        .adm-field { margin-bottom: 18px; }
        .adm-label {
            display: block;
            font-size: 12.5px;
            font-weight: 500;
            color: var(--fg-2);
            margin-bottom: 8px;
        }
        .adm-input {
            width: 100%;
            padding: 11px 14px;
            background: var(--surface-2);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--fg);
            font-size: 14px;
            font-family: 'Poppins', sans-serif;
            outline: none;
            transition: border-color .15s, box-shadow .15s;
        }
        .adm-input:focus {
            border-color: #2f54eb;
            box-shadow: 0 0 0 3px rgba(47,84,235,0.15);
        }
        .adm-input::placeholder { color: var(--fg-4); }
        .adm-input.error { border-color: rgba(239,68,68,0.6); }
        .adm-error { font-size: 11.5px; color: #EF4444; margin-top: 6px; }

        /* ── Button ── */
        .adm-btn {
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            background: #2f54eb;
            border: none;
            color: #000;
            font-size: 14px;
            font-weight: 600;
            font-family: 'Poppins', sans-serif;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: opacity .15s;
        }
        .adm-btn:hover { opacity: 0.88; }

        /* ── Divider ── */
        .adm-divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 22px 0;
            font-size: 11.5px;
            color: var(--fg-4);
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }
        .adm-divider::before, .adm-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
        }

        /* ── Flash ── */
        .adm-flash-error {
            margin-bottom: 20px;
            padding: 12px 14px;
            border-radius: 9px;
            background: rgba(239,68,68,0.1);
            border: 1px solid rgba(239,68,68,0.25);
            color: #EF4444;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .adm-flash-success {
            margin-bottom: 20px;
            padding: 12px 14px;
            border-radius: 9px;
            background: rgba(34,197,94,0.1);
            border: 1px solid rgba(34,197,94,0.25);
            color: #22C55E;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        @media (max-width: 900px) {
            body { grid-template-columns: 1fr; }
            .adm-brand-panel { display: none; }
            .adm-form-panel { padding: 40px 28px; }
        }
    </style>
</head>
<body>

{{-- ── LEFT: Form Panel ── --}}
<div class="adm-form-panel">

    {{-- Header row --}}
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:auto;padding-bottom:20px;">
        <a href="{{ route('admin.login') }}" style="display:inline-flex;align-items:center;gap:8px;text-decoration:none;">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                <rect x="3" y="8" width="18" height="12" rx="2.5" stroke="#2f54eb" stroke-width="2" fill="none"/><path d="M8 8V6.5A2 2 0 0 1 10 4.5h4A2 2 0 0 1 16 6.5V8" stroke="#2f54eb" stroke-width="2" fill="none" stroke-linecap="round"/><path d="M3 13.3h18" stroke="#2f54eb" stroke-width="2"/>
            </svg>
            <span style="font-size:15px;font-weight:600;color:var(--fg);letter-spacing:-0.3px;">{{ gs()->site_name ?? 'Job Station' }}</span>
        </a>
        <div style="display:flex;align-items:center;gap:6px;font-size:11.5px;color:var(--fg-3);padding:5px 10px;border:1px solid var(--border);border-radius:999px;">
            <span style="width:6px;height:6px;border-radius:3px;background:#22C55E;display:inline-block;"></span>
            All systems operational
        </div>
    </div>

    <div class="adm-form-inner">

        {{-- Flash messages --}}
        @if(session('error'))
        <div class="adm-flash-error">
            <i data-lucide="alert-circle" style="width:14px;height:14px;flex-shrink:0;"></i>
            {{ session('error') }}
        </div>
        @endif
        @if(session('success'))
        <div class="adm-flash-success">
            <i data-lucide="check-circle" style="width:14px;height:14px;flex-shrink:0;"></i>
            {{ session('success') }}
        </div>
        @endif
        @if($errors->any())
        <div class="adm-flash-error">
            <i data-lucide="alert-circle" style="width:14px;height:14px;flex-shrink:0;"></i>
            {{ $errors->first() }}
        </div>
        @endif

        {{-- Title --}}
        <h1 style="font-size:32px;font-weight:600;letter-spacing:-1px;margin:0 0 10px;color:var(--fg);">@yield('heading', 'Admin console access.')</h1>
        <p style="font-size:14.5px;color:var(--fg-3);line-height:1.55;margin:0 0 36px;">@yield('subheading', 'Staff only. All sign-in attempts are logged and attributed to your identity.')</p>

        @yield('content')

    </div>

    {{-- Footer --}}
    <div style="font-size:11.5px;color:var(--fg-4);padding-top:20px;margin-top:auto;">
        Locked out? <a href="{{ route('contact') }}" style="color:var(--fg-3);text-decoration:none;">Contact support</a>
    </div>
</div>

{{-- ── RIGHT: Brand Panel ── --}}
<div class="adm-brand-panel">
    <div style="display:flex;align-items:center;gap:10px;position:relative;">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
            <rect x="3" y="8" width="18" height="12" rx="2.5" stroke="white" stroke-width="2" fill="none"/><path d="M8 8V6.5A2 2 0 0 1 10 4.5h4A2 2 0 0 1 16 6.5V8" stroke="white" stroke-width="2" fill="none" stroke-linecap="round"/><path d="M3 13.3h18" stroke="white" stroke-width="2"/>
        </svg>
        <span style="padding:3px 8px;border-radius:6px;background:rgba(239,68,68,0.15);color:#FCA5A5;font-size:10.5px;font-weight:600;letter-spacing:0.08em;text-transform:uppercase;">Staff</span>
    </div>

    <div style="flex:1;display:flex;flex-direction:column;justify-content:center;position:relative;">
        <div style="font-size:11.5px;color:rgba(255,255,255,0.5);letter-spacing:0.12em;text-transform:uppercase;margin-bottom:20px;">Admin console</div>
        <h2 style="font-size:44px;font-weight:600;letter-spacing:-1.5px;line-height:1.05;margin:0 0 24px;color:white;">
            Secure access<br>to the Job Station core.
        </h2>
        <p style="font-size:15px;color:rgba(255,255,255,0.65);line-height:1.55;max-width:380px;margin:0;">
            All actions inside the admin console are logged, attributed, and reviewed. Sign in with your staff credentials.
        </p>

        {{-- Stat tiles --}}
        @php
            $pendingKyc = \App\Models\User::where('kyc_status', 2)->count();
            $activeUsers = \App\Models\User::where('status', 1)->count();
        @endphp
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-top:48px;max-width:440px;">
            @foreach([
                [number_format($activeUsers), 'active users'],
                [$pendingKyc, 'pending KYC'],
                ['OK', 'all systems'],
            ] as [$v, $l])
            <div style="padding:14px 16px;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);border-radius:10px;">
                <div style="font-size:20px;font-weight:600;color:white;letter-spacing:-0.5px;font-family:ui-monospace,monospace;">{{ $v }}</div>
                <div style="font-size:11px;color:rgba(255,255,255,0.55);margin-top:2px;">{{ $l }}</div>
            </div>
            @endforeach
        </div>
    </div>

    <div style="font-size:11.5px;color:rgba(255,255,255,0.4);position:relative;display:flex;justify-content:space-between;">
        <span>Internal use only · All sessions audited</span>
        <span style="font-family:ui-monospace,monospace;">SOC 2</span>
    </div>
</div>

<script>
    document.addEventListener('alpine:initialized', () => { if (window.lucide) lucide.createIcons(); });
    document.addEventListener('DOMContentLoaded', () => { if (window.lucide) lucide.createIcons(); });
</script>
</body>
</html>
