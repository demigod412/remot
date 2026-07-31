<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Install Job Station — @yield('title', 'Setup')</title>
    <link rel="icon" href="/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root{
            --bg:#f4f6fc; --bg2:#ffffff; --card:#ffffff; --card2:#f7f9fd;
            --border:#e4e8f2; --fg:#111a3a; --fg2:#5d6b8f; --fg3:#8a93b2;
            --primary:#2f54eb; --primary2:#5570e8; --primary-soft:rgba(47,84,235,.10);
            --ok:#22c55e; --bad:#ef4444; --warn:#f59e0b;
        }
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:'Poppins',system-ui,sans-serif;background:
            radial-gradient(1100px 600px at 50% -10%, #eaf0ff 0%, var(--bg) 60%);
            color:var(--fg);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:32px 16px}
        .wrap{width:100%;max-width:680px}
        .brand{display:flex;align-items:center;gap:12px;justify-content:center;margin-bottom:26px}
        .brand .mark{width:46px;height:46px;border-radius:13px;background:linear-gradient(160deg,#3b6bff,#1a2a66);
            display:flex;align-items:center;justify-content:center;box-shadow:0 8px 24px rgba(47,84,235,.35)}
        .brand .name{font-size:21px;font-weight:300;letter-spacing:-.4px}
        .brand .name b{font-weight:700}
        .steps{display:flex;gap:6px;margin:0 auto 22px;max-width:560px}
        .steps .s{flex:1;height:4px;border-radius:4px;background:var(--border)}
        .steps .s.done{background:var(--primary2)}
        .steps .s.cur{background:var(--primary)}
        .card{background:var(--card);border:1px solid var(--border);
            border-radius:20px;padding:34px;box-shadow:0 20px 50px rgba(20,30,80,.08)}
        .stepno{font-size:12px;font-weight:600;letter-spacing:.14em;text-transform:uppercase;color:var(--primary2)}
        h1{font-size:25px;font-weight:700;margin:6px 0 8px;letter-spacing:-.5px}
        p.lead{color:var(--fg2);font-size:14.5px;line-height:1.6;margin-bottom:22px}
        label{display:block;font-size:13px;font-weight:500;color:var(--fg2);margin:14px 0 6px}
        input{width:100%;background:#fff;border:1px solid var(--border);border-radius:11px;
            color:var(--fg);padding:13px 14px;font-family:inherit;font-size:14.5px;transition:.15s}
        input:focus{outline:none;border-color:var(--primary);box-shadow:0 0 0 3px var(--primary-soft)}
        input::placeholder{color:var(--fg3)}
        .row{display:flex;gap:12px}.row>div{flex:1}
        .btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;width:100%;margin-top:24px;
            background:linear-gradient(135deg,#3b6bff,#2f54eb);color:#fff;border:none;border-radius:12px;
            padding:14px;font-family:inherit;font-size:15px;font-weight:600;cursor:pointer;transition:.15s;text-decoration:none}
        .btn:hover{transform:translateY(-1px);box-shadow:0 10px 26px rgba(47,84,235,.4)}
        .btn.ghost{background:transparent;border:1px solid var(--border);color:var(--fg2);box-shadow:none}
        .check{display:flex;align-items:center;justify-content:space-between;padding:11px 14px;border:1px solid var(--border);
            border-radius:11px;margin-bottom:8px;font-size:14px;background:var(--card2)}
        .pill{font-size:11px;font-weight:600;padding:3px 10px;border-radius:20px}
        .pill.ok{color:var(--ok);background:rgba(34,197,94,.12)}
        .pill.bad{color:var(--bad);background:rgba(239,68,68,.12)}
        .grp-title{font-size:12px;font-weight:600;letter-spacing:.1em;text-transform:uppercase;color:var(--fg3);margin:18px 0 10px}
        .alert{border-radius:12px;padding:13px 15px;font-size:13.5px;margin-bottom:18px;line-height:1.5}
        .alert.err{background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.25);color:#b91c1c}
        .alert.ok{background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.25);color:#15803d}
        .alert.info{background:var(--primary-soft);border:1px solid rgba(47,84,235,.25);color:#1e3a8a}
        .muted{color:var(--fg3);font-size:12.5px;margin-top:14px;text-align:center}
        .err-text{color:#dc2626;font-size:12.5px;margin-top:6px}
    </style>
</head>
<body>
<div class="wrap">
    <div class="brand">
        <span class="mark">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none">
                <rect x="3" y="8" width="18" height="12" rx="2.5" fill="#fff"/>
                <path d="M8 8V6.5A2 2 0 0 1 10 4.5h4A2 2 0 0 1 16 6.5V8" stroke="#fff" stroke-width="2" fill="none" stroke-linecap="round"/>
                <rect x="3" y="12.2" width="18" height="1.9" fill="#1a2a66"/>
                <rect x="10.8" y="11.4" width="2.4" height="3.4" rx=".8" fill="#5570e8"/>
            </svg>
        </span>
        <span class="name">Job <b>Station</b></span>
    </div>

    @php $cur = (int) ($step ?? 1); @endphp
    <div class="steps">
        @for($i = 1; $i <= 5; $i++)
            <span class="s {{ $i < $cur ? 'done' : ($i === $cur ? 'cur' : '') }}"></span>
        @endfor
    </div>

    <div class="card">
        @if(session('status'))
            <div class="alert ok">{{ session('status') }}</div>
        @endif
        @if($errors->any())
            <div class="alert err">{{ $errors->first() }}</div>
        @endif
        @yield('content')
    </div>

    <p class="muted">Job Station v{{ config('jobstation.version', '1.0.0') }} · CodeCanyon Edition</p>
</div>
</body>
</html>
