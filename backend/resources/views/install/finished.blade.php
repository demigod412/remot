@php($step = 6)
@extends('install.layout')
@section('title', 'Done')
@section('content')
    <div style="text-align:center">
        <div style="width:72px;height:72px;border-radius:50%;margin:4px auto 18px;display:flex;align-items:center;justify-content:center;background:rgba(34,197,94,.14)">
            <svg width="38" height="38" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
        </div>
        <div class="stepno" style="color:#22c55e">Installation complete</div>
        <h1>Job Station is ready 🎉</h1>
        <p class="lead" style="text-align:center">
            Your marketplace has been installed successfully. You can now sign in to the admin
            dashboard and start configuring your site, payment channels, and content.
        </p>
    </div>

    <div class="alert info">
        <b>Admin login:</b> {{ $adminEmail }}<br>
        Use the password you just set.
    </div>

    @if(!empty($demoSeeded))
        <div class="alert info">
            <b>Demo data imported.</b> Sample users sign in with password <code>Demo@1234</code>
            (e.g. <code>worker@demo.com</code>, <code>employer@demo.com</code>). Delete them before going live.
        </div>
    @endif

    @if(!empty($demoWarning))
        <div class="alert" style="background:rgba(245,158,11,.12);border-color:#f59e0b">
            {{ $demoWarning }}
        </div>
    @endif

    <a href="{{ rtrim($appUrl,'/') }}/admin" class="btn">Go to admin dashboard →</a>
    <a href="{{ rtrim($appUrl,'/') }}" class="btn ghost">Visit site</a>

    <div class="alert info" style="margin-top:22px">
        <b>Note:</b> <code>APP_DEBUG</code> has been set to <code>false</code> and the installer is now
        locked automatically (via <code>storage/installed</code>). To re-run setup, delete that file.
    </div>
@endsection
