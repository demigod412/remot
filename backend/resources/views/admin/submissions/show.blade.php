@extends('admin.layouts.app')
@section('title', 'Review Submission')
@section('page-title', 'Proof Review')

@section('content')

{{-- Breadcrumb --}}
<div style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--fg-3);margin-bottom:20px;">
    <a href="{{ route('admin.submissions.index') }}" style="color:var(--fg-3);text-decoration:none;"
       onmouseover="this.style.color='var(--fg)'" onmouseout="this.style.color='var(--fg-3)'">Submissions</a>
    <i data-lucide="chevron-right" style="width:12px;height:12px;"></i>
    <span class="mono" style="color:var(--fg-2);">SB-{{ str_pad($submission->id, 5, '0', STR_PAD_LEFT) }}</span>
</div>

<div class="jobstation-card" style="padding:0;overflow:hidden;">

    {{-- Header --}}
    <div style="padding:20px 24px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:flex-start;">
        <div>
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
                <span class="mono" style="font-size:11px;color:var(--fg-3);">SB-{{ str_pad($submission->id, 5, '0', STR_PAD_LEFT) }}</span>
                <span class="chip chip-instant" style="font-size:10px;padding:2px 7px;">
                    <i data-lucide="bolt" style="width:10px;height:10px;"></i> INSTANT
                </span>
                @php
                    $statusMap = [0=>'status-draft',1=>'status-pending',2=>'status-approved',3=>'status-rejected'];
                    $statusLbl = [0=>'Applied',1=>'Under Review',2=>'Approved',3=>'Rejected'];
                @endphp
                <span class="status-pill {{ $statusMap[$submission->status] ?? 'status-draft' }}">
                    {{ $statusLbl[$submission->status] ?? 'Unknown' }}
                </span>
            </div>
            <h2 style="font-size:18px;font-weight:600;margin:0 0 8px;line-height:1.3;">
                {{ $submission->work?->title ?? 'Deleted Work' }}
            </h2>
            <div style="display:flex;align-items:center;gap:10px;font-size:12px;color:var(--fg-3);">
                <span>Submitted {{ ($submission->submitted_at ?? $submission->created_at)->diffForHumans() }}</span>
                @if($submission->deadline_at)
                <span>·</span>
                <span>Deadline: <span style="color:{{ $submission->deadline_at->isPast() ? 'var(--urgent)' : 'var(--fg-2)' }};">{{ $submission->deadline_at->format('M d, H:i') }}</span></span>
                @endif
            </div>
        </div>
        @if($submission->work)
        <div style="text-align:right;flex-shrink:0;">
            <div class="mono" style="font-size:22px;font-weight:600;color:var(--coin);letter-spacing:-0.5px;">{{ coinSymbol() }}{{ number_format($submission->work->coins_per_worker) }}</div>
            <div style="font-size:11px;color:var(--fg-3);">reward</div>
        </div>
        @endif
    </div>

    {{-- Worker + Client cards --}}
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;padding:20px 24px;border-bottom:1px solid var(--border);">

        {{-- Worker --}}
        @php
            $wIni = strtoupper(substr($submission->worker?->username ?? 'U', 0, 1));
            $clrs = ['#2f54eb','#FF7A59','#22C55E','#60A5FA','#F59E0B'];
            $wClr = $clrs[ord($wIni) % count($clrs)];
            $wSubCount = $submission->worker?->workSubmissions()->count() ?? 0;
            $wApproved = $submission->worker?->workSubmissions()->where('status', 2)->count() ?? 0;
            $wRate     = $wSubCount > 0 ? round(($wApproved / $wSubCount) * 100, 1) : 0;
        @endphp
        <div class="jobstation-card" style="padding:14px;background:var(--surface-2);">
            <div class="label" style="margin-bottom:8px;">Worker</div>
            <div style="display:flex;align-items:center;gap:10px;">
                <div style="width:34px;height:34px;border-radius:50%;background:{{ $wClr }};display:flex;align-items:center;justify-content:center;color:white;font-size:13px;font-weight:600;flex-shrink:0;">{{ $wIni }}</div>
                <div style="flex:1;min-width:0;">
                    <div style="font-size:13px;font-weight:500;">{{ $submission->worker?->fullname ?? 'Unknown' }} · {{ '@' . ($submission->worker?->username ?? '') }}</div>
                    <div style="font-size:11px;color:var(--fg-3);">{{ number_format($wSubCount) }} tasks · {{ $wRate }}% approval</div>
                </div>
                @if($submission->worker)
                <a href="{{ route('admin.users.show', $submission->worker_id) }}" class="btn btn-sm">View</a>
                @endif
            </div>
        </div>

        {{-- Client / Poster --}}
        @php
            $pName = $submission->work?->poster_type == 1 ? 'Admin' : ($submission->workPoster?->username ?? 'Unknown');
            $pIni  = strtoupper(substr($pName, 0, 1));
            $pClr  = $clrs[ord($pIni) % count($clrs)];
        @endphp
        <div class="jobstation-card" style="padding:14px;background:var(--surface-2);">
            <div class="label" style="margin-bottom:8px;">Client</div>
            <div style="display:flex;align-items:center;gap:10px;">
                <div style="width:34px;height:34px;border-radius:50%;background:{{ $pClr }};display:flex;align-items:center;justify-content:center;color:white;font-size:13px;font-weight:600;flex-shrink:0;">{{ $pIni }}</div>
                <div style="flex:1;min-width:0;">
                    <div style="font-size:13px;font-weight:500;">{{ $pName }}</div>
                    <div style="font-size:11px;color:var(--fg-3);">
                        {{ $submission->work?->category?->name ?? '—' }}
                        @if($submission->work) · {{ coinSymbol() }}{{ number_format($submission->work->coins_per_worker) }} reward @endif
                    </div>
                </div>
                @if($submission->work)
                <a href="{{ route('admin.works.show', $submission->work_id) }}" class="btn btn-sm">View</a>
                @endif
            </div>
        </div>

    </div>

    {{-- Proof Files --}}
    <div style="padding:20px 24px;border-bottom:1px solid var(--border);">
        <div class="label" style="margin-bottom:12px;">
            Submitted proof
            @if($submission->proof_files) ({{ count($submission->proof_files) }} files) @endif
        </div>

        @if($submission->proof_files && count($submission->proof_files))
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;">
            @foreach($submission->proof_files as $index => $file)
            @php $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION)); @endphp
            @if(in_array($ext, ['jpg','jpeg','png','gif','webp']))
            <a href="{{ route('secure.workProof', ['submission' => $submission->id, 'index' => $index]) }}" target="_blank"
               style="display:block;border-radius:10px;overflow:hidden;border:1px solid var(--border);">
                <img src="{{ route('secure.workProof', ['submission' => $submission->id, 'index' => $index]) }}"
                     style="width:100%;height:160px;object-fit:cover;display:block;" alt="">
            </a>
            @else
            <a href="{{ route('secure.workProof', ['submission' => $submission->id, 'index' => $index]) }}" target="_blank"
               style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:160px;border-radius:10px;border:1px solid var(--border);background:var(--surface-2);text-decoration:none;gap:8px;"
               onmouseover="this.style.background='var(--surface-3)'" onmouseout="this.style.background='var(--surface-2)'">
                <i data-lucide="file" style="width:28px;height:28px;color:var(--fg-3);display:block;"></i>
                <span style="font-size:11px;color:var(--fg-3);text-transform:uppercase;font-weight:500;">{{ $ext }}</span>
            </a>
            @endif
            @endforeach
        </div>
        @else
        <div style="padding:32px;text-align:center;border:1px dashed var(--border);border-radius:10px;color:var(--fg-3);font-size:13px;">
            <i data-lucide="paperclip" style="width:24px;height:24px;margin:0 auto 8px;display:block;opacity:0.3;"></i>
            No file attachments submitted.
        </div>
        @endif
    </div>

    {{-- Worker Notes --}}
    @if($submission->proof_note)
    <div style="padding:20px 24px;border-bottom:1px solid var(--border);">
        <div class="label" style="margin-bottom:10px;">Worker's notes</div>
        <div class="jobstation-card" style="padding:14px;background:var(--surface-2);font-size:13px;color:var(--fg-2);line-height:1.6;">
            {{ $submission->proof_note }}
        </div>
    </div>
    @endif

    {{-- Already rejected message --}}
    @if($submission->status == 3 && $submission->rejection_reason)
    <div style="padding:16px 24px;border-bottom:1px solid var(--border);">
        <div style="display:flex;align-items:flex-start;gap:12px;padding:14px 16px;border-radius:10px;background:rgba(239,68,68,0.06);border:1px solid rgba(239,68,68,0.2);">
            <i data-lucide="alert-circle" style="width:16px;height:16px;color:#EF4444;flex-shrink:0;margin-top:1px;"></i>
            <div>
                <div style="font-size:12.5px;font-weight:500;color:#EF4444;margin-bottom:3px;">Rejected</div>
                <div style="font-size:13px;color:var(--fg-2);">{{ $submission->rejection_reason }}</div>
            </div>
        </div>
    </div>
    @endif

    {{-- Sticky Action Bar --}}
    @if(in_array($submission->status, [0, 1]))
    <div x-data="{ rejectOpen: false }"
         style="position:sticky;bottom:0;background:var(--bg);padding:16px 24px;border-top:1px solid var(--border);display:flex;flex-direction:column;gap:10px;">
        <div style="display:flex;gap:10px;align-items:center;">
            <button @click="rejectOpen = !rejectOpen"
                    class="btn btn-danger" style="flex-shrink:0;">
                <i data-lucide="x" style="width:12px;height:12px;"></i> Reject
            </button>
            <span style="flex:1"></span>
            <form method="POST" action="{{ route('admin.submissions.approve', $submission->id) }}">
                @csrf
                <button type="submit"
                        onclick="return confirm('Approve this submission and credit {{ coinSymbol() }}{{ number_format($submission->work?->coins_per_worker ?? 0) }} to the worker?')"
                        style="padding:9px 18px;border-radius:8px;background:#22C55E;border:1px solid #22C55E;color:white;font-size:13px;font-weight:500;cursor:pointer;display:flex;align-items:center;gap:6px;">
                    <i data-lucide="check" style="width:13px;height:13px;"></i>
                    Approve · Pay {{ coinSymbol() }}{{ number_format($submission->work?->coins_per_worker ?? 0) }}
                </button>
            </form>
        </div>

        {{-- Reject form (collapsible) --}}
        <div x-show="rejectOpen" x-cloak x-transition style="padding:14px;border-radius:10px;background:rgba(239,68,68,0.06);border:1px solid rgba(239,68,68,0.2);">
            <form method="POST" action="{{ route('admin.submissions.reject', $submission->id) }}" style="display:flex;gap:10px;align-items:flex-start;">
                @csrf
                <textarea name="rejection_reason" rows="2" placeholder="Rejection reason (required)…"
                          style="flex:1;font-size:12.5px;resize:none;" required></textarea>
                <button type="submit"
                        style="padding:9px 16px;border-radius:8px;background:#EF4444;border:none;color:white;font-size:13px;font-weight:500;cursor:pointer;flex-shrink:0;white-space:nowrap;">
                    Confirm reject
                </button>
            </form>
        </div>
    </div>
    @endif

</div>

@endsection
