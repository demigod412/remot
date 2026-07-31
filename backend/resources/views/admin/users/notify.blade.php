@extends('admin.layouts.app')
@section('title', 'Notify ' . $user->fullname)
@section('page-title', 'Send Notification')

@section('content')

<div style="display:flex;align-items:center;gap:6px;font-size:12.5px;color:var(--fg-3);margin-bottom:20px;">
    <a href="{{ route('admin.users.index') }}" style="color:var(--fg-3);text-decoration:none;transition:.12s;"
       onmouseover="this.style.color='var(--fg)'" onmouseout="this.style.color='var(--fg-3)'">Users</a>
    <i data-lucide="chevron-right" style="width:13px;height:13px;color:var(--fg-4);"></i>
    <a href="{{ route('admin.users.show', $user->id) }}" style="color:var(--fg-3);text-decoration:none;transition:.12s;"
       onmouseover="this.style.color='var(--fg)'" onmouseout="this.style.color='var(--fg-3)'">{{ $user->fullname }}</a>
    <i data-lucide="chevron-right" style="width:13px;height:13px;color:var(--fg-4);"></i>
    <span style="color:var(--fg-2);">Notify</span>
</div>

<div style="max-width:680px;">
    <div class="jobstation-card" style="padding:24px;">

        {{-- Recipient Info --}}
        <div style="display:flex;align-items:center;gap:14px;padding:14px 16px;border-radius:10px;background:var(--surface-2);border:1px solid var(--border);margin-bottom:20px;">
            @php $uInit = strtoupper(substr($user->firstname ?? $user->username, 0, 1)); $uColor = ['#2f54eb','#FF7A59','#22C55E','#60A5FA','#F59E0B'][ord($uInit)%5]; @endphp
            <div style="width:42px;height:42px;border-radius:50%;background:linear-gradient(135deg,{{ $uColor }},{{ ['#2f54eb','#FF7A59','#22C55E','#60A5FA','#F59E0B'][(ord($uInit)+2)%5] }});display:flex;align-items:center;justify-content:center;color:white;font-weight:700;font-size:16px;flex-shrink:0;">
                @if($user->image)
                <img src="{{ fileUrl(config('jobstation.upload_paths.avatars'), $user->image) }}" style="width:100%;height:100%;object-fit:cover;border-radius:50%;" alt="">
                @else
                {{ $uInit }}
                @endif
            </div>
            <div style="flex:1;min-width:0;">
                <div style="font-size:14px;font-weight:600;color:var(--fg);">{{ $user->fullname }}</div>
                <div style="font-size:12.5px;color:var(--fg-3);">{{ $user->email }}</div>
            </div>
            @if($user->email_verified)
            <span style="display:inline-flex;padding:2px 9px;border-radius:999px;font-size:11px;font-weight:500;background:rgba(34,197,94,0.12);color:#22C55E;flex-shrink:0;">Email Verified</span>
            @else
            <span style="display:inline-flex;padding:2px 9px;border-radius:999px;font-size:11px;font-weight:500;background:rgba(245,158,11,0.12);color:#F59E0B;flex-shrink:0;">Unverified</span>
            @endif
        </div>

        <form method="POST" action="{{ route('admin.users.notify.send', $user->id) }}">
            @csrf
            <div style="display:flex;flex-direction:column;gap:16px;">
                <div>
                    <label style="display:block;font-size:12px;color:var(--fg-2);margin-bottom:6px;font-weight:500;">Subject <span style="color:#EF4444;">*</span></label>
                    <input type="text" name="subject" value="{{ old('subject') }}"
                           placeholder="Notification subject…"
                           @error('subject') style="border-color:#EF4444;" @enderror
                           maxlength="150" required>
                    @error('subject') <div style="font-size:12px;color:#EF4444;margin-top:4px;">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label style="display:block;font-size:12px;color:var(--fg-2);margin-bottom:6px;font-weight:500;">Message <span style="color:#EF4444;">*</span></label>
                    <textarea name="message" rows="6"
                              placeholder="Write your message here…"
                              style="resize:vertical;width:100%;font-size:13.5px;line-height:1.6;"
                              @error('message') style="border-color:#EF4444;" @enderror
                              maxlength="2000" required>{{ old('message') }}</textarea>
                    @error('message') <div style="font-size:12px;color:#EF4444;margin-top:4px;">{{ $message }}</div> @enderror
                    <div style="font-size:11.5px;color:var(--fg-4);margin-top:4px;">Max 2000 characters</div>
                </div>

                <div style="display:flex;gap:10px;padding-top:4px;">
                    <button type="submit" class="btn btn-primary" style="padding:9px 22px;">
                        <i data-lucide="send" style="width:14px;height:14px;"></i> Send Notification
                    </button>
                    <a href="{{ route('admin.users.show', $user->id) }}" class="btn" style="padding:9px 18px;">Cancel</a>
                </div>
            </div>
        </form>
    </div>

    @if(!gs()->email_notify)
    <div style="margin-top:14px;display:flex;align-items:flex-start;gap:10px;padding:12px 14px;border-radius:10px;background:rgba(245,158,11,0.08);border:1px solid rgba(245,158,11,0.2);font-size:12.5px;color:#F59E0B;">
        <i data-lucide="alert-triangle" style="width:15px;height:15px;flex-shrink:0;margin-top:1px;"></i>
        <span>Email notifications are disabled. The notification will be logged but not delivered via email.</span>
    </div>
    @endif
</div>

@endsection
