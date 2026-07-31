@extends('admin.layouts.app')
@section('title', 'Notification Events')
@section('page-title', 'Notification Events')

@section('content')

<form method="POST" action="{{ route('admin.notif-events.update') }}">
@csrf
<div style="display:flex;flex-direction:column;gap:16px;max-width:860px;">

    <div class="jobstation-card" style="padding:24px;">
        <div style="margin-bottom:20px;">
            <div style="font-weight:600;font-size:15px;color:var(--fg);">Notification Events</div>
            <div style="font-size:12px;color:var(--fg-3);margin-top:4px;">
                Control which events send email and/or SMS to users. Global email/SMS must be enabled in
                <a href="{{ route('admin.settings.notification') }}" style="color:var(--accent);text-decoration:none;">Notification Settings</a>.
            </div>
        </div>

        {{-- Header --}}
        <div style="display:grid;grid-template-columns:1fr 90px 90px;gap:8px;padding:0 12px 10px;border-bottom:1px solid var(--border);">
            <div style="font-size:11.5px;font-weight:600;color:var(--fg-3);text-transform:uppercase;letter-spacing:.05em;">Event</div>
            <div style="font-size:11.5px;font-weight:600;color:var(--fg-3);text-transform:uppercase;letter-spacing:.05em;text-align:center;">
                <i data-lucide="mail" style="width:12px;height:12px;display:inline;"></i> Email
            </div>
            <div style="font-size:11.5px;font-weight:600;color:var(--fg-3);text-transform:uppercase;letter-spacing:.05em;text-align:center;">
                <i data-lucide="message-square" style="width:12px;height:12px;display:inline;"></i> SMS
            </div>
        </div>

        @php
        $descriptions = [
            'WELCOME'              => 'Sent when a user creates an account',
            'EMAIL_VERIFICATION'   => 'Email or phone OTP for account verification',
            'PASSWORD_RESET'       => 'Password reset code or link',
            'KYC_APPROVED'         => 'Identity verification approved',
            'KYC_REJECTED'         => 'Identity verification rejected',
            'WORK_APPROVED'        => 'Work/task listing approved by admin',
            'WORK_REJECTED'        => 'Work/task listing rejected by admin',
            'SUBMISSION_APPROVED'  => "Worker's task submission approved and coins paid",
            'SUBMISSION_REJECTED'  => "Worker's task submission rejected",
            'JOB_APPROVED'         => 'Job listing approved by admin',
            'JOB_REJECTED'         => 'Job listing rejected by admin',
            'TOPUP_APPROVED'       => 'Coin top-up approved',
            'TOPUP_REJECTED'       => 'Coin top-up rejected',
            'CASHOUT_APPROVED'     => 'Withdrawal approved and sent',
            'CASHOUT_REJECTED'     => 'Withdrawal rejected',
            'REFERRAL_BONUS'       => 'User earned a referral bonus',
            'TICKET_REPLY'         => 'Admin replied to a support ticket',
        ];
        @endphp

        @foreach($groups as $groupName => $acts)

        {{-- Group label --}}
        <div style="padding:14px 12px 6px;font-size:11px;font-weight:600;color:var(--accent);text-transform:uppercase;letter-spacing:.07em;">
            {{ $groupName }}
        </div>

        @foreach($acts as $act)
        @php $t = $templates->get($act); @endphp

        <div style="display:grid;grid-template-columns:1fr 90px 90px;gap:8px;align-items:center;
                    padding:10px 12px;border-radius:8px;transition:background .12s;"
             onmouseover="this.style.background='var(--surface-2)'" onmouseout="this.style.background='transparent'">

            <div>
                <div style="font-size:13.5px;color:var(--fg);font-weight:500;">
                    {{ $t ? $t->name : str_replace('_', ' ', ucwords(strtolower($act), '_')) }}
                </div>
                <div style="font-size:11.5px;color:var(--fg-3);margin-top:2px;">
                    {{ $descriptions[$act] ?? '' }}
                </div>
            </div>

            <div style="text-align:center;">
                @if($t)
                <label class="notif-toggle">
                    <input type="checkbox" name="templates[{{ $t->id }}][email_status]" value="1"
                           {{ $t->email_status ? 'checked' : '' }}>
                    <span class="notif-track"><span class="notif-thumb"></span></span>
                </label>
                @else
                <span style="font-size:11px;color:var(--fg-3);">—</span>
                @endif
            </div>

            <div style="text-align:center;">
                @if($t)
                <label class="notif-toggle">
                    <input type="checkbox" name="templates[{{ $t->id }}][sms_status]" value="1"
                           {{ $t->sms_status ? 'checked' : '' }}>
                    <span class="notif-track"><span class="notif-thumb"></span></span>
                </label>
                @else
                <span style="font-size:11px;color:var(--fg-3);">—</span>
                @endif
            </div>

        </div>
        @endforeach

        @if(!$loop->last)
        <div style="height:1px;background:var(--border);margin:4px 0;"></div>
        @endif

        @endforeach

    </div>

    <div>
        <button type="submit" class="btn-primary" style="padding:9px 20px;font-size:13px;display:inline-flex;align-items:center;gap:7px;">
            <i data-lucide="save" style="width:14px;height:14px;"></i> Save
        </button>
    </div>

</div>
</form>

<style>
.notif-toggle {
    position: relative;
    display: inline-flex;
    cursor: pointer;
}
.notif-toggle input {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
}
.notif-track {
    display: inline-flex;
    align-items: center;
    width: 38px;
    height: 22px;
    background: var(--border);
    border-radius: 999px;
    transition: background .2s;
    position: relative;
}
.notif-thumb {
    position: absolute;
    left: 3px;
    width: 16px;
    height: 16px;
    background: #fff;
    border-radius: 50%;
    transition: transform .2s;
    box-shadow: 0 1px 3px rgba(0,0,0,.25);
}
.notif-toggle input:checked ~ .notif-track {
    background: var(--accent);
}
.notif-toggle input:checked ~ .notif-track .notif-thumb {
    transform: translateX(16px);
}
</style>

@endsection
