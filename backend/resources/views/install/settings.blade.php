@php($step = 5)
@extends('install.layout')
@section('title', 'Final Setup')
@section('content')
    <div class="stepno">Step 5 of 5</div>
    <h1>Site &amp; admin account</h1>
    <p class="lead">
        We'll run the database migrations, seed the starter data, and create your administrator login.
        This can take up to a minute — please don't refresh.
    </p>

    <form method="POST" action="{{ route('install.settings.save') }}">
        @csrf
        <div class="grp-title">Site</div>
        <div class="row">
            <div>
                <label for="app_name">Site name</label>
                <input type="text" id="app_name" name="app_name" value="{{ old('app_name', $appName) }}">
            </div>
            <div>
                <label for="app_url">Site URL</label>
                <input type="text" id="app_url" name="app_url" value="{{ old('app_url', $appUrl) }}">
            </div>
        </div>

        <div class="grp-title">Administrator</div>
        <div class="row">
            <div>
                <label for="admin_name">Full name</label>
                <input type="text" id="admin_name" name="admin_name" value="{{ old('admin_name') }}" placeholder="Jane Doe">
            </div>
            <div>
                <label for="admin_username">Username</label>
                <input type="text" id="admin_username" name="admin_username" value="{{ old('admin_username', 'admin') }}">
            </div>
        </div>

        <label for="admin_email">Email</label>
        <input type="email" id="admin_email" name="admin_email" value="{{ old('admin_email') }}" placeholder="you@yourdomain.com">

        <div class="row">
            <div>
                <label for="admin_password">Password</label>
                <input type="password" id="admin_password" name="admin_password" placeholder="min 8 characters">
            </div>
            <div>
                <label for="admin_password_confirmation">Confirm password</label>
                <input type="password" id="admin_password_confirmation" name="admin_password_confirmation">
            </div>
        </div>

        <div class="grp-title">Demo data</div>
        <label style="display:flex;align-items:flex-start;gap:10px;font-weight:400;cursor:pointer;margin:4px 0 10px;">
            <input type="checkbox" name="seed_demo" value="1" {{ old('seed_demo') ? 'checked' : '' }} style="width:18px;height:18px;margin-top:2px;flex:none;">
            <span style="font-size:13px;line-height:1.5;color:#475569;">
                <strong>Import demo / sample data</strong> — demo users, jobs &amp; submissions so your site
                looks like the live preview. Leave unchecked for a clean production install
                (demo content is hard to fully remove later).
            </span>
        </label>

        <button type="submit" class="btn" onclick="this.innerHTML='Installing…';this.style.opacity=.7;this.form.submit();">
            Install Job Station →
        </button>
    </form>
@endsection
