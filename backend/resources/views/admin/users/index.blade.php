@extends('admin.layouts.app')
@section('title', 'Users')
@section('page-title', 'User Management')

@section('content')

{{-- 4 Stat Tiles --}}
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:18px;">

    <div class="jobstation-card" style="padding:18px;">
        <div class="label" style="margin-bottom:6px;">Total users</div>
        <div class="mono" style="font-size:24px;font-weight:600;letter-spacing:-0.6px;">{{ number_format($totalUsers) }}</div>
        <div style="font-size:11.5px;color:var(--fg-3);margin-top:4px;">registered</div>
    </div>

    <div class="jobstation-card" style="padding:18px;">
        <div class="label" style="margin-bottom:6px;">Active</div>
        <div class="mono" style="font-size:24px;font-weight:600;letter-spacing:-0.6px;color:#22C55E;">{{ number_format($activeUsers) }}</div>
        <div style="font-size:11.5px;color:var(--fg-3);margin-top:4px;">
            {{ $totalUsers > 0 ? round(($activeUsers / $totalUsers) * 100, 1) : 0 }}% of total
        </div>
    </div>

    <div class="jobstation-card" style="padding:18px;">
        <div class="label" style="margin-bottom:6px;">Suspended</div>
        <div class="mono" style="font-size:24px;font-weight:600;letter-spacing:-0.6px;color:#EF4444;">{{ number_format($bannedUsers) }}</div>
        <div style="font-size:11.5px;color:var(--fg-3);margin-top:4px;">
            {{ $totalUsers > 0 ? round(($bannedUsers / $totalUsers) * 100, 1) : 0 }}% of total
        </div>
    </div>

    <div class="jobstation-card" style="padding:18px;">
        <div class="label" style="margin-bottom:6px;">KYC pending</div>
        <div class="mono" style="font-size:24px;font-weight:600;letter-spacing:-0.6px;color:#60A5FA;">{{ number_format($kycPending) }}</div>
        <div style="font-size:11.5px;margin-top:4px;">
            <a href="{{ route('admin.users.kyc') }}" style="color:var(--accent);text-decoration:none;">Review →</a>
        </div>
    </div>

</div>

{{-- Table Card --}}
<div class="jobstation-card" style="padding:0;overflow:hidden;">

    {{-- Toolbar --}}
    <div style="padding:14px 18px;border-bottom:1px solid var(--border);display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
        <form method="GET" action="{{ route('admin.users.index') }}" style="display:flex;gap:10px;align-items:center;flex:1;flex-wrap:wrap;">

            <div style="position:relative;flex:1;min-width:200px;max-width:360px;">
                <i data-lucide="search" style="position:absolute;left:11px;top:50%;transform:translateY(-50%);width:13px;height:13px;color:var(--fg-3);pointer-events:none;"></i>
                <input name="search" value="{{ request('search') }}" placeholder="Search by name, email, @handle…"
                       style="padding-left:34px;width:100%;font-size:12.5px;">
            </div>

            <select name="status" style="width:auto;padding:7px 10px;font-size:12px;">
                <option value="">All statuses</option>
                <option value="1" {{ request('status')=='1' ? 'selected' : '' }}>Active</option>
                <option value="0" {{ request('status')=='0' ? 'selected' : '' }}>Suspended</option>
            </select>

            <select name="kyc" style="width:auto;padding:7px 10px;font-size:12px;">
                <option value="">All KYC</option>
                <option value="1" {{ request('kyc')=='1' ? 'selected' : '' }}>Verified</option>
                <option value="2" {{ request('kyc')=='2' ? 'selected' : '' }}>Pending</option>
                <option value="0" {{ request('kyc')=='0' ? 'selected' : '' }}>Unverified</option>
            </select>

            <button type="submit" class="btn btn-sm">Filter</button>
            @if(request()->hasAny(['search','status','kyc']))
            <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-ghost">Clear</a>
            @endif
        </form>

        <a href="{{ route('admin.users.notify.bulk') }}" class="btn btn-sm">
            <i data-lucide="send" style="width:12px;height:12px;"></i> Bulk notify
        </a>
    </div>

    {{-- Table Header --}}
    <div style="display:grid;grid-template-columns:28px 2fr 90px 100px 110px 110px 100px 80px;gap:14px;padding:11px 18px;font-size:10.5px;color:var(--fg-3);text-transform:uppercase;letter-spacing:0.06em;font-weight:500;background:var(--bg-2);border-bottom:1px solid var(--border);">
        <span></span>
        <span>User</span>
        <span>KYC</span>
        <span>Tasks</span>
        <span>Balance</span>
        <span>Joined</span>
        <span>Status</span>
        <span></span>
    </div>

    {{-- Rows --}}
    @forelse($users as $user)
    @php
        $ini  = strtoupper(substr($user->username ?? $user->firstname ?? 'U', 0, 1));
        $clrs = ['#2f54eb','#FF7A59','#22C55E','#60A5FA','#F59E0B'];
        $clr  = $clrs[ord($ini) % count($clrs)];
        $kycLabel = match((int)$user->kyc_status) { 1=>'Verified', 2=>'Pending', default=>'None' };
        $kycColor = match((int)$user->kyc_status) { 1=>'#22C55E', 2=>'#F59E0B', default=>'var(--fg-4)' };
        $statusClass = $user->status == 1 ? 'status-approved' : 'status-rejected';
        $statusLabel = $user->status == 1 ? 'Active' : 'Suspended';
        $subCount = $user->workSubmissions()->count();
    @endphp
    <div style="display:grid;grid-template-columns:28px 2fr 90px 100px 110px 110px 100px 80px;gap:14px;padding:12px 18px;align-items:center;border-bottom:1px solid var(--border);font-size:12.5px;">

        <div style="width:26px;height:26px;border-radius:50%;background:{{ $clr }};display:flex;align-items:center;justify-content:center;color:white;font-size:11px;font-weight:600;flex-shrink:0;">{{ $ini }}</div>

        <div style="min-width:0;">
            <div style="font-weight:500;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $user->fullname }}</div>
            <div style="font-size:10.5px;color:var(--fg-3);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $user->email }}</div>
        </div>

        <span class="mono" style="font-size:11px;color:{{ $kycColor }};">{{ $kycLabel }}</span>

        <span class="mono">{{ number_format($subCount) }}</span>

        <span class="mono" style="color:var(--coin);">{{ coinSymbol() }}{{ number_format($user->coin_balance) }}</span>

        <span style="font-size:11px;color:var(--fg-3);">{{ $user->created_at->format('M j, Y') }}</span>

        <span class="status-pill {{ $statusClass }}" style="width:fit-content;">{{ $statusLabel }}</span>

        <div style="display:flex;gap:6px;justify-content:flex-end;">
            <a href="{{ route('admin.users.show', $user->id) }}" class="btn btn-sm">View</a>
        </div>

    </div>
    @empty
    <div style="padding:48px;text-align:center;color:var(--fg-3);">
        <i data-lucide="users" style="width:32px;height:32px;margin:0 auto 12px;display:block;opacity:0.3;"></i>
        <div style="font-size:13px;">No users found.</div>
    </div>
    @endforelse

</div>

{{-- Pagination --}}
@if($users->hasPages())
<div style="display:flex;justify-content:flex-end;margin-top:16px;font-size:12.5px;">
    {{ $users->withQueryString()->links() }}
</div>
@endif

@endsection
