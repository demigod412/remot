@extends('user.layouts.app')
@section('title', 'Review Proof')
@section('page-title', 'Review Proof')

@section('content')
{{-- Breadcrumb --}}
<div style="display:flex; align-items:center; gap:6px; font-size:12px; color:var(--fg-4); margin-bottom:20px; flex-wrap:wrap;">
    <a href="{{ route('user.works.index') }}" style="color:var(--fg-4); text-decoration:none;"
       onmouseover="this.style.color='var(--fg-2)'" onmouseout="this.style.color='var(--fg-4)'">My Works</a>
    <svg width="11" height="11" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M7 4l5 5-5 5"/></svg>
    <a href="{{ route('user.works.submissions', $work->id) }}" style="color:var(--fg-4); text-decoration:none; max-width:180px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"
       onmouseover="this.style.color='var(--fg-2)'" onmouseout="this.style.color='var(--fg-4)'">{{ Str::limit($work->title, 40) }}</a>
    <svg width="11" height="11" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M7 4l5 5-5 5"/></svg>
    <span style="color:var(--fg-3);">Review Proof</span>
</div>

<div style="display:flex; flex-direction:column; gap:14px;">

    {{-- Worker Info --}}
    @php $sl = [0=>'Applied',1=>'Under Review',2=>'Approved',3=>'Rejected']; $sc = [0=>'badge-info',1=>'badge-warning',2=>'badge-success',3=>'badge-danger']; @endphp
    <div class="card" style="padding:20px; display:flex; align-items:center; gap:16px;">
        <div style="width:46px; height:46px; border-radius:50%; background:var(--accent-soft); border:2px solid rgba(47,84,235,0.25); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
            <span style="font-weight:700; color:var(--accent); font-size:18px;">{{ strtoupper(substr($submission->worker->firstname ?? 'U', 0, 1)) }}</span>
        </div>
        <div style="flex:1; min-width:0;">
            <div style="font-size:14px; font-weight:600; color:var(--fg);">{{ $submission->worker->fullname ?? 'Unknown Worker' }}</div>
            <div style="font-size:12.5px; color:var(--fg-3);">Submitted {{ $submission->submitted_at ? $submission->submitted_at->diffForHumans() : 'N/A' }}</div>
        </div>
        <span class="badge {{ $sc[$submission->status] ?? '' }}" style="font-size:11.5px; flex-shrink:0;">{{ $sl[$submission->status] ?? '' }}</span>
    </div>

    {{-- Proof Note --}}
    @if($submission->proof_note)
    <div class="card" style="padding:20px;">
        <div class="label" style="margin-bottom:8px;">Proof Note</div>
        <p style="font-size:13.5px; color:var(--fg-2); line-height:1.65; margin:0; white-space:pre-wrap;">{{ $submission->proof_note }}</p>
    </div>
    @endif

    {{-- Proof Files --}}
    @if($submission->proof_files && count($submission->proof_files))
    <div class="card" style="padding:20px;">
        <div class="label" style="margin-bottom:12px;">Proof Files</div>
        <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:10px;" class="proof-grid">
            @foreach($submission->proof_files as $index => $file)
            @php $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION)); $isImage = in_array($ext, ['jpg','jpeg','png','gif','webp']); @endphp
            @if($isImage)
            <a href="{{ route('secure.workProof', ['submission' => $submission->id, 'index' => $index]) }}" target="_blank"
               style="display:block; border-radius:10px; overflow:hidden; border:1px solid var(--border); transition:border-color .14s;"
               onmouseover="this.style.borderColor='var(--accent)'" onmouseout="this.style.borderColor='var(--border)'">
                <img src="{{ route('secure.workProof', ['submission' => $submission->id, 'index' => $index]) }}"
                     style="width:100%; height:100px; object-fit:cover; display:block;">
            </a>
            @else
            <a href="{{ route('secure.workProof', ['submission' => $submission->id, 'index' => $index]) }}" target="_blank"
               style="display:flex; align-items:center; gap:8px; padding:12px; border-radius:10px; background:var(--surface-2); border:1px solid var(--border); text-decoration:none; transition:border-color .14s;"
               onmouseover="this.style.borderColor='var(--accent)'" onmouseout="this.style.borderColor='var(--border)'">
                <i data-lucide="file" style="width:18px; height:18px; color:var(--accent); flex-shrink:0;"></i>
                <span style="font-size:12px; color:var(--fg-3); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $file }}</span>
            </a>
            @endif
            @endforeach
        </div>
    </div>
    @endif

    {{-- Worker profile link --}}
    <div class="card" style="padding:14px 18px; display:flex; align-items:center; justify-content:space-between;">
        <div style="font-size:13px; color:var(--fg-3);">Worker profile</div>
        <a href="{{ route('user.public-profile', $submission->worker->username) }}"
           style="font-size:12.5px; padding:6px 14px; border-radius:8px; background:var(--surface-2); border:1px solid var(--border); color:var(--fg-2); text-decoration:none; transition:all .14s;"
           onmouseover="this.style.color='var(--accent)'; this.style.borderColor='var(--accent)'" onmouseout="this.style.color='var(--fg-2)'; this.style.borderColor='var(--border)'">
            View {{ '@' . $submission->worker->username }} →
        </a>
    </div>

    {{-- Rating section (visible after approval) --}}
    @if($submission->status === 2)
    @php
        $myRating = \App\Models\Rating::where('rater_id', auth()->id())
            ->where('ratable_id', $work->id)
            ->where('ratable_type', \App\Models\Work::class)
            ->where('ratee_id', $submission->worker_id)
            ->first();
        $counterpartRated = \App\Models\Rating::where('rater_id', $submission->worker_id)
            ->where('ratable_id', $work->id)
            ->where('ratable_type', \App\Models\Work::class)
            ->exists();
    @endphp
    <div class="card" style="padding:20px;" x-data="{ starHover: {{ $myRating ? $myRating->rating : 0 }}, starPick: {{ $myRating ? $myRating->rating : 0 }} }">
        <div style="display:flex; align-items:center; gap:8px; margin-bottom:4px;">
            <i data-lucide="star" style="width:15px; height:15px; color:#F59E0B;"></i>
            <span style="font-size:14px; font-weight:600; color:var(--fg);">Rate this worker</span>
        </div>
        <p style="font-size:12px; color:var(--fg-4); margin:0 0 16px; line-height:1.5;">Visible once the worker also rates you. Fiverr-style blind reveal.</p>

        @if(session('success') && str_contains(session('success'), 'Rating'))
        <div style="padding:10px 14px; border-radius:8px; background:rgba(34,197,94,0.08); border:1px solid rgba(34,197,94,0.2); color:#22C55E; font-size:13px; margin-bottom:14px;">{{ session('success') }}</div>
        @endif

        @if($myRating)
        <div style="padding:14px; border-radius:10px; background:var(--surface-2); border:1px solid var(--border);">
            <p style="font-size:13px; color:var(--fg-3); margin:0 0 8px;">Your rating was submitted.</p>
            <div style="display:flex; gap:2px; margin-bottom:6px;">
                @for($i = 1; $i <= 5; $i++)
                <span style="font-size:20px; line-height:1; color:{{ $i <= $myRating->rating ? '#F59E0B' : 'var(--border)' }};">★</span>
                @endfor
            </div>
            @if($myRating->review)<p style="font-size:13px; color:var(--fg-3); font-style:italic; margin:0 0 6px;">"{{ $myRating->review }}"</p>@endif
            @if(!$counterpartRated)
            <p style="font-size:11.5px; color:var(--fg-4); margin:0;">Waiting for the worker to also rate…</p>
            @else
            <p style="font-size:11.5px; color:#22C55E; margin:0; display:flex; align-items:center; gap:5px;">
                <i data-lucide="check-circle" style="width:12px; height:12px;"></i> Both ratings are now revealed.
            </p>
            @endif
        </div>
        @else
        <form method="POST" action="{{ route('user.ratings.store') }}">
            @csrf
            <input type="hidden" name="ratable_type" value="work">
            <input type="hidden" name="ratable_id" value="{{ $work->id }}">
            <input type="hidden" name="ratee_id" value="{{ $submission->worker_id }}">
            <input type="hidden" name="rating" :value="starPick">
            <div style="display:flex; gap:3px; margin-bottom:12px; align-items:center;">
                @for($i = 1; $i <= 5; $i++)
                <button type="button"
                        @mouseover="starHover = {{ $i }}" @mouseleave="starHover = starPick" @click="starPick = {{ $i }}"
                        style="font-size:28px; line-height:1; background:none; border:none; cursor:pointer; padding:0 2px; transition:transform .1s; outline:none;"
                        :style="(starHover >= {{ $i }} || starPick >= {{ $i }}) ? 'color:#F59E0B; transform:scale(1.15)' : 'color:var(--border)'">★</button>
                @endfor
                <span style="font-size:13px; color:var(--fg-3); margin-left:8px;" x-text="['','Poor','Fair','Good','Great','Excellent'][starPick] || 'Tap a star'"></span>
            </div>
            <textarea name="review" rows="2" placeholder="Short review (optional)…" style="resize:none; margin-bottom:10px;"></textarea>
            <button type="submit" class="btn btn-primary btn-sm" :disabled="starPick === 0"
                    :class="starPick === 0 ? 'opacity-40 cursor-not-allowed' : ''">
                Submit Rating
            </button>
        </form>
        @endif
    </div>
    @endif

    {{-- Actions --}}
    @if($submission->status == 1)
    <div class="card" style="padding:20px;">
        <div style="font-size:13px; font-weight:600; color:var(--fg); margin-bottom:16px;">Review Decision</div>

        <form method="POST" action="{{ route('user.works.submissions.approve', [$work->id, $submission->id]) }}"
              style="margin-bottom:10px;">
            @csrf
            <button type="submit" onclick="return confirm('Approve this proof and pay the worker?')"
                    class="btn btn-primary"
                    style="width:100%; justify-content:center; padding:11px 20px; font-size:13.5px;">
                <i data-lucide="check" style="width:15px; height:15px;"></i>
                Approve — Pay {{ formatCoins($work->coins_per_worker) }}
            </button>
        </form>

        <form method="POST" action="{{ route('user.works.submissions.reject', [$work->id, $submission->id]) }}"
              x-data="{ open: false }">
            @csrf
            <button type="button" @click="open = !open"
                    style="width:100%; padding:10px 20px; border-radius:999px; border:1px solid rgba(239,68,68,0.3); color:#EF4444; background:transparent; font-size:13px; font-weight:500; cursor:pointer; transition:all .14s; font-family:inherit; display:flex; align-items:center; justify-content:center; gap:6px;"
                    onmouseover="this.style.background='rgba(239,68,68,0.08)'" onmouseout="this.style.background='transparent'">
                <i data-lucide="x" style="width:14px; height:14px;"></i> Reject Proof
            </button>
            <div x-show="open" x-transition style="margin-top:12px; display:flex; flex-direction:column; gap:8px;">
                <textarea name="rejection_reason" rows="3"
                          placeholder="Explain why this proof is rejected..." required></textarea>
                <button type="submit"
                        style="padding:9px 20px; border-radius:999px; border:1px solid rgba(239,68,68,0.3); color:#EF4444; background:rgba(239,68,68,0.06); font-size:13px; font-weight:500; cursor:pointer; transition:background .14s; font-family:inherit;"
                        onmouseover="this.style.background='rgba(239,68,68,0.12)'" onmouseout="this.style.background='rgba(239,68,68,0.06)'">
                    Confirm Rejection
                </button>
            </div>
        </form>
    </div>
    @endif

</div>

<style>
@media (max-width: 600px) { .proof-grid { grid-template-columns: repeat(2,1fr) !important; } }
</style>
@endsection
