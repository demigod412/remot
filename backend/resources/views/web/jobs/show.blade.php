@extends('web.layouts.app')

@section('title', $job->title . ' — ' . (gs()->site_name ?? config('app.name')))
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags($job->description), 155))

@if($job->cover_image)
@section('og_image', fileUrl(config('jobstation.upload_paths.work_cover'), $job->cover_image))
@endif

@php
    $empMap = ['full_time' => 'FULL_TIME', 'part_time' => 'PART_TIME', 'contract' => 'CONTRACTOR', 'freelance' => 'CONTRACTOR'];
    $jobLd  = array_filter([
        '@context'           => 'https://schema.org/',
        '@type'              => 'JobPosting',
        'title'              => $job->title,
        'description'        => $job->description,
        'datePosted'         => optional($job->created_at)->toDateString(),
        'validThrough'       => optional($job->closes_at)->toAtomString(),
        'employmentType'     => $empMap[$job->employment_type] ?? null,
        'hiringOrganization' => [
            '@type' => 'Organization',
            'name'  => $job->employer->fullname ?? (gs()->site_name ?? config('app.name')),
        ],
        'jobLocationType'    => (int) $job->location_type === 1 ? 'TELECOMMUTE' : null,
        'jobLocation'        => ((int) $job->location_type !== 1 && $job->location) ? [
            '@type'   => 'Place',
            'address' => ['@type' => 'PostalAddress', 'addressLocality' => $job->location],
        ] : null,
        'baseSalary'         => ($job->salary_visible && $job->salary_min) ? [
            '@type'    => 'MonetaryAmount',
            'currency' => $job->salary_currency ?: 'USD',
            'value'    => [
                '@type'    => 'QuantitativeValue',
                'minValue' => (float) $job->salary_min,
                'maxValue' => (float) ($job->salary_max ?: $job->salary_min),
                'unitText' => 'MONTH',
            ],
        ] : null,
    ], fn ($v) => $v !== null);
@endphp

@push('head')
<script type="application/ld+json">
{!! json_encode($jobLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}
</script>
@endpush

@section('content')

<div style="max-width:1200px; margin:0 auto; padding:28px 40px 80px;">

    {{-- Breadcrumb --}}
    <div style="font-size:12.5px; color:var(--muted); margin-bottom:20px; display:flex; gap:6px; align-items:center; flex-wrap:wrap;">
        <a href="{{ route('works.index') }}" style="color:var(--muted); text-decoration:none;">Find work</a>
        <svg width="11" height="11" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M7 4l5 5-5 5"/></svg>
        <a href="{{ route('works.index', ['tab' => 'hiring']) }}" style="color:var(--muted); text-decoration:none;">Hiring jobs</a>
        <svg width="11" height="11" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M7 4l5 5-5 5"/></svg>
        <span>{{ Str::limit($job->title, 40) }}</span>
    </div>

    <div style="display:grid; grid-template-columns:1fr 340px; gap:40px; align-items:start;" class="detail-grid">

        {{-- ── LEFT COLUMN ────────────────────────────────────────── --}}
        <div>
            {{-- Chips --}}
            <div style="display:flex; align-items:center; gap:10px; margin-bottom:18px; flex-wrap:wrap;">
                <span class="chip chip-hiring" style="font-size:10.5px; text-transform:uppercase; font-weight:600;">💼 Hiring</span>
                @if($job->category)
                <span class="chip" style="font-size:11px;">{{ $job->category->name }}</span>
                @endif
                @if($job->employment_type)
                <span class="chip" style="font-size:11px;">{{ $job->getEmploymentTypeLabelAttribute() }}</span>
                @endif
                @if($job->location_type)
                <span class="chip" style="font-size:11px;">{{ $job->getLocationTypeLabelAttribute() }}</span>
                @endif
            </div>

            {{-- Title --}}
            <h1 style="font-size:clamp(26px,3vw,40px); font-weight:600; letter-spacing:-1px; line-height:1.15; margin:0 0 16px; color:var(--text);">
                {{ $job->title }}
            </h1>

            {{-- Meta --}}
            <div style="display:flex; gap:18px; align-items:center; margin-bottom:32px; font-size:13px; color:var(--muted); flex-wrap:wrap;">
                @if($job->employer)
                <div style="display:flex; align-items:center; gap:8px;">
                    <div style="width:24px; height:24px; border-radius:50%; background:linear-gradient(135deg,#60A5FA,#2f54eb); display:flex; align-items:center; justify-content:center; color:white; font-weight:600; font-size:10px; flex-shrink:0;">
                        {{ strtoupper(substr($job->employer->name ?? $job->employer->username ?? '?', 0, 1)) }}
                    </div>
                    <span style="font-weight:500; color:var(--text);">{{ $job->employer->name ?? $job->employer->username }}</span>
                </div>
                <span style="color:#D4D4D8;">·</span>
                @endif
                @if($job->location)
                <span>📍 {{ $job->location }}</span>
                <span style="color:#D4D4D8;">·</span>
                @endif
                <span>Posted {{ $job->created_at->diffForHumans() }}</span>
                @if($job->closes_at)
                <span style="color:#D4D4D8;">·</span>
                <span style="color:{{ $job->closes_at->isPast() ? 'var(--urgent)' : 'var(--muted)' }};">
                    {{ $job->closes_at->isPast() ? 'Closed' : 'Closes ' . $job->closes_at->diffForHumans() }}
                </span>
                @endif
            </div>

            {{-- Description --}}
            <div class="card" style="padding:24px; margin-bottom:24px;">
                <div style="font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.07em; color:var(--muted); margin-bottom:14px;">About the role</div>
                <div style="font-size:14.5px; color:var(--muted); line-height:1.65;">
                    {!! richBody($job->description) !!}
                </div>
            </div>

            {{-- Requirements --}}
            @if($job->requirements)
            <div class="card" style="padding:24px; margin-bottom:24px;">
                <div style="font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.07em; color:var(--muted); margin-bottom:14px;">Requirements</div>
                <div style="display:flex; flex-direction:column; gap:10px; font-size:13.5px;">
                    @foreach(explode("\n", $job->requirements) as $req)
                    @if(trim($req))
                    <div style="display:flex; gap:10px; align-items:flex-start;">
                        <svg width="14" height="14" viewBox="0 0 18 18" fill="none" stroke="#22C55E" stroke-width="1.8" stroke-linecap="round" style="flex-shrink:0; margin-top:2px;"><path d="M3 9l4 4 8-8"/></svg>
                        <span style="color:var(--muted);">{{ trim($req) }}</span>
                    </div>
                    @endif
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Benefits --}}
            @if($job->benefits)
            <div class="card" style="padding:24px; margin-bottom:24px;">
                <div style="font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.07em; color:var(--muted); margin-bottom:14px;">Benefits</div>
                <div style="display:flex; flex-direction:column; gap:10px; font-size:13.5px;">
                    @foreach(explode("\n", $job->benefits) as $benefit)
                    @if(trim($benefit))
                    <div style="display:flex; gap:10px; align-items:flex-start;">
                        <svg width="14" height="14" viewBox="0 0 18 18" fill="none" stroke="#60A5FA" stroke-width="1.8" stroke-linecap="round" style="flex-shrink:0; margin-top:2px;"><path d="M9 1L2 4v5c0 4 3 7 7 8 4-1 7-4 7-8V4L9 1z"/></svg>
                        <span style="color:var(--muted);">{{ trim($benefit) }}</span>
                    </div>
                    @endif
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Skills --}}
            @if($job->skills->isNotEmpty())
            <div class="card" style="padding:24px; margin-bottom:24px;">
                <div style="font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.07em; color:var(--muted); margin-bottom:14px;">Skills</div>
                <div style="display:flex; gap:8px; flex-wrap:wrap;">
                    @foreach($job->skills as $skill)
                    <span class="chip" style="font-size:12px;">{{ $skill->name }}</span>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Similar jobs --}}
            @if($similar->isNotEmpty())
            <div style="margin-top:32px;">
                <div style="font-size:13px; font-weight:600; color:var(--text); margin-bottom:14px;">Similar positions</div>
                <div style="display:flex; flex-direction:column; gap:8px;">
                    @foreach($similar->take(3) as $s)
                    <a href="{{ route('jobs.show', $s->slug) }}" style="text-decoration:none; display:block;">
                        <div class="card" style="padding:14px 18px; display:flex; align-items:center; gap:14px; transition:transform .14s;" onmouseover="this.style.transform='translateY(-1px)'" onmouseout="this.style.transform=''">
                            <div style="width:28px; height:28px; border-radius:7px; background:rgba(96,165,250,0.12); color:#60A5FA; display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:12px;">💼</div>
                            <div style="flex:1; font-size:13.5px; font-weight:500; color:var(--text);">{{ Str::limit($s->title, 60) }}</div>
                            @if($s->salary_min || $s->salary_max)
                            <span class="mono" style="font-size:13px; font-weight:600; color:var(--text); flex-shrink:0;">{{ coinSymbol() }}{{ number_format($s->salary_min) }}{{ $s->salary_max ? '–'.number_format($s->salary_max) : '+' }}</span>
                            @endif
                        </div>
                    </a>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        {{-- ── RIGHT SIDEBAR ───────────────────────────────────────── --}}
        <aside style="position:sticky; top:80px;">
            {{-- Salary card --}}
            <div class="card" style="padding:24px; margin-bottom:16px;">
                <div style="text-align:center; padding-bottom:22px; border-bottom:1px solid var(--border); margin-bottom:22px;">
                    <div style="font-size:11.5px; color:var(--muted); margin-bottom:8px; text-transform:uppercase; letter-spacing:0.08em;">Salary</div>
                    @if($job->salary_visible && ($job->salary_min || $job->salary_max))
                    <div class="mono" style="font-size:32px; font-weight:600; letter-spacing:-1px; line-height:1; color:var(--text);">
                        {{ coinSymbol() }}{{ number_format($job->salary_min) }}{{ $job->salary_max ? '–'.number_format($job->salary_max) : '+' }}
                    </div>
                    <div style="font-size:12px; color:var(--muted); margin-top:6px;">{{ $job->salary_currency ?? 'TZS' }} / month</div>
                    @else
                    <div style="font-size:15px; font-weight:500; color:var(--muted);">Undisclosed</div>
                    @endif
                </div>

                <div style="display:flex; flex-direction:column; gap:14px; margin-bottom:24px;">
                    @if($job->employment_type)
                    <div style="display:flex; justify-content:space-between; align-items:center; font-size:13px;">
                        <span style="color:var(--muted);">Type</span>
                        <span style="font-weight:500; color:var(--text);">{{ $job->getEmploymentTypeLabelAttribute() }}</span>
                    </div>
                    @endif
                    @if($job->location_type)
                    <div style="display:flex; justify-content:space-between; align-items:center; font-size:13px;">
                        <span style="color:var(--muted);">Location</span>
                        <span style="font-weight:500; color:var(--text);">{{ $job->getLocationTypeLabelAttribute() }}</span>
                    </div>
                    @endif
                    @if($job->location)
                    <div style="display:flex; justify-content:space-between; align-items:center; font-size:13px;">
                        <span style="color:var(--muted);">City</span>
                        <span style="font-weight:500; color:var(--text);">{{ $job->location }}</span>
                    </div>
                    @endif
                    @if($job->closes_at && !$job->closes_at->isPast())
                    <div style="display:flex; justify-content:space-between; align-items:center; font-size:13px;">
                        <span style="color:var(--muted);">Deadline</span>
                        <span style="font-weight:500; color:var(--text);">{{ $job->closes_at->format('M j, Y') }}</span>
                    </div>
                    @endif
                </div>

                @if($job->requires_kyc)
                <div style="display:flex; align-items:flex-start; gap:10px; padding:12px 14px; background:rgba(245,158,11,0.07); border:1px solid rgba(245,158,11,0.25); border-radius:10px; margin-bottom:16px;">
                    <span style="font-size:15px; flex-shrink:0; margin-top:1px;">🔒</span>
                    <div>
                        <div style="font-size:12.5px; font-weight:600; color:#b45309; margin-bottom:2px;">KYC Verification Required</div>
                        <div style="font-size:11.5px; color:#92400e; line-height:1.5;">Identity verification needed before applying.</div>
                    </div>
                </div>
                @endif

                @auth
                <a href="{{ route('user.jobs.show', $job->id) }}" class="btn btn-primary" style="width:100%; justify-content:center; padding:12px; font-size:14px; display:flex; gap:8px; align-items:center; text-decoration:none;">
                    💼 Apply now
                </a>
                @else
                <a href="{{ route('user.login') }}" class="btn btn-primary" style="width:100%; justify-content:center; padding:12px; font-size:14px; display:flex; gap:8px; align-items:center;">
                    💼 Sign in to apply
                </a>
                <div style="text-align:center; font-size:12px; color:var(--muted); margin-top:10px;">
                    <a href="{{ route('user.register') }}" style="color:var(--accent);">Create account</a> — it's free
                </div>
                @endauth
            </div>
        </aside>
    </div>
</div>

<style>
@media (max-width:1024px) {
    .detail-grid { grid-template-columns: 1fr !important; }
}
@media (max-width:640px) {
    .detail-grid { padding: 0 20px 60px !important; }
}
</style>

@endsection
