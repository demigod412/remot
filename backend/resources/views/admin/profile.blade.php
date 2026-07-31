@extends('admin.layouts.app')
@section('title', 'Profile')
@section('page-title', 'Admin Profile')

@section('content')

<div style="max-width:520px;">
<div class="jobstation-card" style="padding:24px;">

    <div style="display:flex;align-items:center;gap:16px;margin-bottom:24px;padding-bottom:20px;border-bottom:1px solid var(--border);">
        <div style="width:52px;height:52px;border-radius:50%;background:rgba(47,84,235,0.12);border:2px solid rgba(47,84,235,0.3);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <span style="font-size:20px;font-weight:700;color:var(--accent);">{{ strtoupper(substr($admin->name, 0, 1)) }}</span>
        </div>
        <div>
            <div style="font-size:17px;font-weight:600;color:var(--fg);">{{ $admin->name }}</div>
            <div style="font-size:13px;color:var(--fg-3);">{{ $admin->email }}</div>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.profile.update') }}">
        @csrf @method('PUT')

        @if(session('success'))
        <div style="padding:10px 14px;background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.25);border-radius:8px;font-size:13px;color:#22C55E;margin-bottom:16px;">{{ session('success') }}</div>
        @endif
        @if($errors->any())
        <div style="padding:10px 14px;background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.25);border-radius:8px;font-size:13px;color:#EF4444;margin-bottom:16px;">{{ $errors->first() }}</div>
        @endif

        <div style="display:flex;flex-direction:column;gap:16px;">
            <div>
                <label style="display:block;font-size:12px;color:var(--fg-2);margin-bottom:6px;font-weight:500;">Display Name <span style="color:#EF4444;">*</span></label>
                <input type="text" name="name" value="{{ old('name', $admin->name) }}"
                       @error('name') style="border-color:#EF4444;" @enderror required>
            </div>
            <div>
                <label style="display:block;font-size:12px;color:var(--fg-2);margin-bottom:6px;font-weight:500;">Username <span style="color:#EF4444;">*</span></label>
                <input type="text" name="username" value="{{ old('username', $admin->username) }}"
                       @error('username') style="border-color:#EF4444;" @enderror required>
            </div>

            <div style="border-top:1px solid var(--border);padding-top:16px;">
                <div style="font-size:13px;color:var(--fg-2);margin-bottom:12px;font-weight:500;">Change Password <span style="font-size:11.5px;color:var(--fg-3);font-weight:400;">(leave blank to keep current)</span></div>
                <div style="display:flex;flex-direction:column;gap:12px;">
                    <div>
                        <label style="display:block;font-size:12px;color:var(--fg-2);margin-bottom:6px;font-weight:500;">New Password</label>
                        <input type="password" name="password" autocomplete="new-password"
                               @error('password') style="border-color:#EF4444;" @enderror>
                    </div>
                    <div>
                        <label style="display:block;font-size:12px;color:var(--fg-2);margin-bottom:6px;font-weight:500;">Confirm Password</label>
                        <input type="password" name="password_confirmation" autocomplete="new-password">
                    </div>
                </div>
            </div>

            <div style="padding-top:4px;">
                <button type="submit" class="btn btn-primary" style="padding:9px 22px;">
                    <i data-lucide="save" style="width:14px;height:14px;"></i> Save Changes
                </button>
            </div>
        </div>
    </form>
</div>
</div>

@endsection
