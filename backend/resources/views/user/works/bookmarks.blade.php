@extends('user.layouts.app')
@section('title', __('Saved Works'))
@section('page-title', __('Saved Works'))

@section('content')

@if($bookmarks->isEmpty())
<div class="card" style="padding:64px 24px; text-align:center;">
    <div style="width:52px; height:52px; border-radius:14px; background:rgba(47,84,235,0.08); display:flex; align-items:center; justify-content:center; margin:0 auto 14px;">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
    </div>
    <div style="font-size:15px; font-weight:600; color:var(--fg); margin-bottom:6px;">{{ __('No saved works yet') }}</div>
    <p style="font-size:13px; color:var(--fg-3); margin:0 0 20px;">{{ __('Browse tasks and click the bookmark icon to save them here.') }}</p>
    <a href="{{ route('user.browse.works') }}" class="btn btn-primary" style="font-size:13px;">{{ __('Browse Works') }}</a>
</div>
@else

<div style="display:flex; flex-direction:column; gap:8px;">
@foreach($bookmarks as $bookmark)
@php $work = $bookmark->work; @endphp
@if(!$work) @continue @endif
@php $remaining = $work->slots_remaining ?? 0; @endphp
<div class="saved-work-card card" style="padding:14px 18px; display:flex; align-items:center; gap:16px; cursor:pointer; transition:box-shadow .14s, transform .14s;"
     onclick="window.location='{{ route('user.browse.works.show', $work->slug) }}'"
     onmouseover="this.style.transform='translateY(-1px)';this.style.boxShadow='0 4px 16px rgba(0,0,0,0.08)'"
     onmouseout="this.style.transform='';this.style.boxShadow=''">

    {{-- Icon --}}
    <div style="width:36px; height:36px; border-radius:9px; background:rgba(255,122,89,0.1); color:var(--urgent); display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:16px;">
        ⚡
    </div>

    {{-- Content --}}
    <div style="flex:1; min-width:0;">
        <div style="display:flex; align-items:center; gap:8px; margin-bottom:4px; flex-wrap:wrap;">
            @if($work->is_featured)
            <span style="font-size:10px; font-weight:600; padding:2px 7px; border-radius:20px; background:rgba(245,158,11,0.1); color:#F59E0B; border:1px solid rgba(245,158,11,0.2);">★ Featured</span>
            @endif
            @if($work->work_status !== 1 || $work->approval_status !== 1)
            <span style="font-size:10px; font-weight:600; padding:2px 7px; border-radius:20px; background:var(--surface-2); color:var(--fg-4); border:1px solid var(--border);">Unavailable</span>
            @endif
            <div style="font-size:14px; font-weight:500; color:var(--fg); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:400px;">{{ $work->title }}</div>
        </div>
        <div style="display:flex; gap:10px; font-size:11.5px; color:var(--fg-3); align-items:center; flex-wrap:wrap;">
            @if($work->category)
            <span>{{ $work->category->name }}</span>
            <span style="color:var(--border-strong);">·</span>
            @endif
            @if($work->time_limit)
            <span>~{{ $work->time_limit }} min</span>
            <span style="color:var(--border-strong);">·</span>
            @endif
            <span style="color:{{ $remaining < 5 ? 'var(--urgent)' : 'var(--fg-3)' }};">{{ $remaining }} spots left</span>
        </div>
    </div>

    {{-- Reward --}}
    <span class="mono" style="font-size:15px; font-weight:600; color:#E6C400; flex-shrink:0;">{{ formatCoins($work->coins_per_worker) }}</span>

    {{-- Remove bookmark --}}
    <div x-data="{ removing: false }" @click.stop>
        <button type="button"
            @click="if(removing) return; removing = true; fetch('{{ route('user.works.bookmark', $work->id) }}', { method:'POST', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'} }).then(() => { $el.closest('.saved-work-card').remove(); })"
            :disabled="removing"
            title="{{ __('Remove from saved') }}"
            style="display:flex; align-items:center; justify-content:center; width:32px; height:32px; border-radius:8px; border:1px solid rgba(47,84,235,0.2); background:rgba(47,84,235,0.08); color:var(--accent); cursor:pointer; transition:all .15s;"
            onmouseover="this.style.background='rgba(239,68,68,0.08)';this.style.borderColor='rgba(239,68,68,0.2)';this.style.color='#EF4444'"
            onmouseout="this.style.background='rgba(47,84,235,0.08)';this.style.borderColor='rgba(47,84,235,0.2)';this.style.color='var(--accent)'">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
        </button>
    </div>

    {{-- CTA --}}
    @if($work->work_status === 1 && $work->approval_status === 1 && $remaining > 0)
    <span style="font-size:12px; font-weight:500; padding:6px 14px; border-radius:7px; background:var(--accent); color:white; flex-shrink:0; white-space:nowrap;">{{ __('Start') }} →</span>
    @elseif($remaining <= 0)
    <span style="font-size:12px; padding:6px 12px; border-radius:7px; background:var(--surface-2); color:var(--fg-4); border:1px solid var(--border); flex-shrink:0;">{{ __('Full') }}</span>
    @endif
</div>
@endforeach
</div>

<div style="margin-top:20px;">{{ $bookmarks->links() }}</div>

@endif
@endsection
