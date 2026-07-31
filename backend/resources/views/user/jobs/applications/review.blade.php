@extends('user.layouts.app')
@section('title', 'Review Application')
@section('page-title', 'Review Application')

@section('content')
{{-- Breadcrumb --}}
<div style="display:flex; align-items:center; gap:6px; font-size:12px; color:var(--fg-4); margin-bottom:20px; flex-wrap:wrap;">
    <a href="{{ route('user.jobs.listings.index') }}" style="color:var(--fg-4); text-decoration:none;"
       onmouseover="this.style.color='var(--fg-2)'" onmouseout="this.style.color='var(--fg-4)'">My Listings</a>
    <svg width="11" height="11" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M7 4l5 5-5 5"/></svg>
    <a href="{{ route('user.jobs.listings.show', $listing->id) }}" style="color:var(--fg-4); text-decoration:none; max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"
       onmouseover="this.style.color='var(--fg-2)'" onmouseout="this.style.color='var(--fg-4)'">{{ $listing->title }}</a>
    <svg width="11" height="11" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M7 4l5 5-5 5"/></svg>
    <span style="color:var(--fg-3);">Application</span>
</div>

<div style="display:grid; grid-template-columns:1fr 280px; gap:20px;" class="app-review-grid">

    {{-- Left: Application Content --}}
    <div style="display:flex; flex-direction:column; gap:14px;">

        {{-- Applicant Info --}}
        <div class="card" style="padding:24px;">
            <div style="display:flex; align-items:flex-start; gap:16px; margin-bottom:16px; flex-wrap:wrap;">
                @if($app->applicant->avatar ?? $app->applicant->image ?? null)
                <img src="{{ fileUrl(config('jobstation.upload_paths.avatars'), $app->applicant->avatar ?? $app->applicant->image) }}"
                     style="width:60px; height:60px; border-radius:50%; object-fit:cover; flex-shrink:0; border:2px solid var(--border);" alt="">
                @else
                <div style="width:60px; height:60px; border-radius:50%; background:var(--accent-soft); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                    <i data-lucide="user" style="width:26px; height:26px; color:var(--accent);"></i>
                </div>
                @endif
                <div style="flex:1; min-width:0;">
                    <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-bottom:4px;">
                        <h3 style="font-size:17px; font-weight:600; color:var(--fg); margin:0;">{{ $app->applicant->fullname }}</h3>
                        @include('user.partials.rating-stars', ['user' => $app->applicant])
                    </div>
                    <p style="font-size:13px; color:var(--fg-3); margin:0 0 6px;">{{ $app->applicant->email }}</p>
                    @if($app->applicant->kyc_status === 1)
                    <span style="display:inline-flex; align-items:center; gap:4px; font-size:12px; color:#22C55E;">
                        <i data-lucide="shield-check" style="width:12px; height:12px;"></i> KYC Verified
                    </span>
                    @endif
                </div>
                @php
                    $appColors = [0=>'badge-warning',1=>'badge-info',2=>'badge badge-purple',3=>'badge-success',4=>'badge-danger'];
                    $appLabels = [0=>'Pending',1=>'Reviewed',2=>'Shortlisted',3=>'Accepted',4=>'Rejected'];
                @endphp
                <span class="{{ $appColors[$app->status] ?? 'badge' }}" style="font-size:11.5px; flex-shrink:0;">
                    {{ $appLabels[$app->status] ?? 'Unknown' }}
                </span>
            </div>

            @if($app->expected_salary)
            <div style="display:flex; align-items:center; gap:8px; font-size:13px; color:var(--fg-3); margin-bottom:12px;">
                <i data-lucide="dollar-sign" style="width:14px; height:14px; color:var(--fg-4);"></i>
                Expected: {{ $app->expected_salary_currency ?? '' }} {{ number_format($app->expected_salary) }}
            </div>
            @endif

            @if($app->portfolio_url)
            <div style="margin-bottom:12px;">
                <a href="{{ $app->portfolio_url }}" target="_blank" rel="noopener"
                   style="display:inline-flex; align-items:center; gap:6px; font-size:13px; color:var(--accent); text-decoration:none;"
                   onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">
                    <i data-lucide="external-link" style="width:13px; height:13px;"></i> View Portfolio
                </a>
            </div>
            @endif

            @if($app->resume)
            <div>
                <a href="{{ route('secure.resume', $app->id) }}" target="_blank"
                   style="display:inline-flex; align-items:center; gap:6px; font-size:12.5px; padding:7px 14px; border-radius:8px; background:var(--surface-2); border:1px solid var(--border); color:var(--fg-2); text-decoration:none; transition:all .14s;"
                   onmouseover="this.style.color='var(--accent)'; this.style.borderColor='var(--accent)'" onmouseout="this.style.color='var(--fg-2)'; this.style.borderColor='var(--border)'">
                    <i data-lucide="file-text" style="width:13px; height:13px;"></i> Download Resume
                </a>
            </div>
            @endif
        </div>

        {{-- Cover Letter --}}
        <div class="card" style="padding:24px;">
            <div style="font-size:13px; font-weight:600; color:var(--fg); margin-bottom:12px;">Cover Letter</div>
            <div style="font-size:13.5px; color:var(--fg-2); line-height:1.7; white-space:pre-wrap;">{{ $app->cover_letter }}</div>
        </div>

        @if($app->employer_note)
        <div class="card" style="padding:24px;">
            <div style="font-size:13px; font-weight:600; color:var(--fg); margin-bottom:10px;">Your Previous Note</div>
            <div style="font-size:13.5px; color:var(--fg-2); line-height:1.65;">{{ $app->employer_note }}</div>
        </div>
        @endif
    </div>

    {{-- Right: Actions --}}
    <div style="display:flex; flex-direction:column; gap:14px;">
        <div class="card" style="padding:20px;">
            <div style="font-size:13px; font-weight:600; color:var(--fg); margin-bottom:16px;">Update Status</div>
            <form method="POST" action="{{ route('user.jobs.listings.applications.status', [$listing->id, $app->id]) }}"
                  style="display:flex; flex-direction:column; gap:14px;">
                @csrf
                <div>
                    <label style="display:block; font-size:11.5px; color:var(--fg-3); margin-bottom:7px; font-weight:500;">Status</label>
                    <select name="status">
                        @foreach([1=>'Reviewed',2=>'Shortlisted',3=>'Accepted',4=>'Rejected'] as $val => $label)
                        <option value="{{ $val }}" {{ $app->status == $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="display:block; font-size:11.5px; color:var(--fg-3); margin-bottom:7px; font-weight:500;">
                        Note to Applicant <span style="font-size:11px; color:var(--fg-4); font-weight:400;">(optional)</span>
                    </label>
                    <textarea name="employer_note" rows="4" placeholder="Feedback or next steps…">{{ $app->employer_note }}</textarea>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center; font-size:13px; padding:10px 20px;">Update</button>
            </form>
        </div>

        {{-- Message applicant --}}
        <a href="{{ route('user.jobs.applications.thread', $app->id) }}"
           style="display:flex; align-items:center; gap:8px; padding:12px 16px; border-radius:10px; background:rgba(96,165,250,0.07); color:#60A5FA; text-decoration:none; font-size:13px; font-weight:500; border:1px solid rgba(96,165,250,0.2); transition:background .15s;"
           onmouseover="this.style.background='rgba(96,165,250,0.14)'" onmouseout="this.style.background='rgba(96,165,250,0.07)'">
            <i data-lucide="message-circle" style="width:15px; height:15px; flex-shrink:0;"></i>
            Message Applicant
        </a>

        {{-- Offer Contract shortcut --}}
        <a href="{{ route('user.contracts.create', ['worker' => $app->applicant->username, 'title' => 'Contract: ' . $listing->title]) }}"
           style="display:flex; align-items:center; gap:8px; padding:12px 16px; border-radius:10px; background:rgba(47,84,235,0.08); color:var(--accent); text-decoration:none; font-size:13px; font-weight:500; border:1px solid rgba(47,84,235,0.2); transition:background .15s;"
           onmouseover="this.style.background='rgba(47,84,235,0.16)'" onmouseout="this.style.background='rgba(47,84,235,0.08)'">
            <i data-lucide="file-signature" style="width:15px; height:15px; flex-shrink:0;"></i>
            Offer Contract
        </a>

        <div class="card" style="padding:18px;">
            <div style="display:flex; flex-direction:column; gap:8px;">
                <div style="display:flex; justify-content:space-between; font-size:12.5px;">
                    <span style="color:var(--fg-3);">Applied</span>
                    <span style="color:var(--fg-2);">{{ $app->created_at->format('M d, Y') }}</span>
                </div>
                @if($app->reviewed_at)
                <div style="display:flex; justify-content:space-between; font-size:12.5px;">
                    <span style="color:var(--fg-3);">Reviewed</span>
                    <span style="color:var(--fg-2);">{{ $app->reviewed_at->format('M d, Y') }}</span>
                </div>
                @endif
            </div>
        </div>

        <a href="{{ route('user.jobs.listings.show', $listing->id) }}"
           style="display:inline-flex; align-items:center; gap:8px; font-size:13px; color:var(--fg-3); text-decoration:none; padding:10px 0;"
           onmouseover="this.style.color='var(--fg)'" onmouseout="this.style.color='var(--fg-3)'">
            <i data-lucide="arrow-left" style="width:14px; height:14px;"></i> Back to Listing
        </a>
    </div>

</div>

<style>
@media (max-width: 860px) { .app-review-grid { grid-template-columns: 1fr !important; } }
</style>
@endsection
