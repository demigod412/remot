@extends('admin.layouts.app')
@section('title', 'Bulk Notification')
@section('page-title', 'Bulk Notification')

@section('content')

<div style="display:flex;align-items:center;gap:6px;font-size:12.5px;color:var(--fg-3);margin-bottom:20px;">
    <a href="{{ route('admin.users.index') }}" style="color:var(--fg-3);text-decoration:none;transition:.12s;"
       onmouseover="this.style.color='var(--fg)'" onmouseout="this.style.color='var(--fg-3)'">Users</a>
    <i data-lucide="chevron-right" style="width:13px;height:13px;color:var(--fg-4);"></i>
    <span style="color:var(--fg-2);">Bulk Notify</span>
</div>

<div style="max-width:680px;">
    <div class="jobstation-card" style="padding:24px;">

        <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;">
            <div style="padding:10px;border-radius:12px;background:rgba(47,84,235,0.1);">
                <i data-lucide="send" style="width:20px;height:20px;color:var(--accent);display:block;"></i>
            </div>
            <div>
                <div style="font-size:15px;font-weight:600;color:var(--fg);">Send Bulk Notification</div>
                <div style="font-size:13px;color:var(--fg-3);">Compose and send a message to multiple users at once.</div>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.users.notify.bulk.send') }}"
              onsubmit="return confirm('Send this notification to the selected users?')">
            @csrf
            <div style="display:flex;flex-direction:column;gap:16px;">

                {{-- Target --}}
                <div>
                    <label style="display:block;font-size:12px;color:var(--fg-2);margin-bottom:10px;font-weight:500;">Target Audience <span style="color:#EF4444;">*</span></label>
                    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;" class="bulk-target-grid">
                        @foreach([
                            ['all',      'All Users',      'users',        'Everyone registered'],
                            ['active',   'Active Users',   'user-check',   'Status: active'],
                            ['verified', 'Verified Users', 'shield-check', 'Active + email verified'],
                        ] as [$val, $label, $icon, $desc])
                        <label style="cursor:pointer;">
                            <input type="radio" name="target" value="{{ $val }}"
                                   style="display:none;"
                                   id="target_{{ $val }}"
                                   {{ old('target', 'verified') == $val ? 'checked' : '' }}
                                   onchange="document.querySelectorAll('.target-card').forEach(c=>c.style.borderColor='var(--border)');this.nextElementSibling.style.borderColor='var(--accent)'">
                            <div class="target-card"
                                 style="padding:14px;border-radius:10px;border:2px solid {{ old('target','verified')==$val ? 'var(--accent)' : 'var(--border)' }};background:{{ old('target','verified')==$val ? 'rgba(47,84,235,0.05)' : 'transparent' }};cursor:pointer;transition:.12s;"
                                 onclick="document.getElementById('target_{{ $val }}').click()">
                                <i data-lucide="{{ $icon }}" style="width:18px;height:18px;color:{{ old('target','verified')==$val ? 'var(--accent)' : 'var(--fg-3)' }};display:block;margin-bottom:8px;"></i>
                                <div style="font-size:13px;font-weight:500;color:var(--fg);">{{ $label }}</div>
                                <div style="font-size:11.5px;color:var(--fg-3);margin-top:2px;">{{ $desc }}</div>
                            </div>
                        </label>
                        @endforeach
                    </div>
                    @error('target') <div style="font-size:12px;color:#EF4444;margin-top:4px;">{{ $message }}</div> @enderror
                </div>

                {{-- Reach hint --}}
                <div style="display:flex;align-items:center;gap:8px;padding:10px 14px;border-radius:8px;background:var(--surface-2);font-size:12.5px;color:var(--fg-3);">
                    <i data-lucide="info" style="width:14px;height:14px;flex-shrink:0;color:#60A5FA;"></i>
                    <span>Up to <strong style="color:var(--fg);">{{ number_format($totalUsers) }}</strong> verified active users will receive this notification.</span>
                </div>

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
                    <textarea name="message" rows="7"
                              placeholder="Write your message here…"
                              style="resize:vertical;width:100%;font-size:13.5px;line-height:1.6;"
                              @error('message') style="border-color:#EF4444;" @enderror
                              maxlength="2000" required>{{ old('message') }}</textarea>
                    @error('message') <div style="font-size:12px;color:#EF4444;margin-top:4px;">{{ $message }}</div> @enderror
                    <div style="font-size:11.5px;color:var(--fg-4);margin-top:4px;">Max 2000 characters</div>
                </div>

                <div style="display:flex;gap:10px;padding-top:4px;">
                    <button type="submit" class="btn btn-primary" style="padding:9px 22px;">
                        <i data-lucide="send" style="width:14px;height:14px;"></i> Send to All
                    </button>
                    <a href="{{ route('admin.users.index') }}" class="btn" style="padding:9px 18px;">Cancel</a>
                </div>
            </div>
        </form>
    </div>

    @if(!gs()->email_notify)
    <div style="margin-top:14px;display:flex;align-items:flex-start;gap:10px;padding:12px 14px;border-radius:10px;background:rgba(245,158,11,0.08);border:1px solid rgba(245,158,11,0.2);font-size:12.5px;color:#F59E0B;">
        <i data-lucide="alert-triangle" style="width:15px;height:15px;flex-shrink:0;margin-top:1px;"></i>
        <span>Email notifications are disabled. Enable in <a href="{{ route('admin.settings.notification') }}" style="color:#F59E0B;text-decoration:underline;">Settings → Notification</a>.</span>
    </div>
    @endif
</div>

<style>
@media (max-width: 600px) { .bulk-target-grid { grid-template-columns: 1fr !important; } }
</style>

@endsection
