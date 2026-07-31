@extends('user.layouts.app')
@section('title', __('Saved Jobs'))
@section('page-title', __('Saved Jobs'))

@section('content')

@if($bookmarks->isEmpty())
<div class="card" style="padding:64px 24px; text-align:center;">
    <div style="width:52px; height:52px; border-radius:14px; background:rgba(47,84,235,0.08); display:flex; align-items:center; justify-content:center; margin:0 auto 14px;">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
    </div>
    <div style="font-size:15px; font-weight:600; color:var(--fg); margin-bottom:6px;">{{ __('No saved jobs yet') }}</div>
    <p style="font-size:13px; color:var(--fg-3); margin:0 0 20px;">{{ __('Browse jobs and click the bookmark icon to save them here.') }}</p>
    <a href="{{ route('user.jobs.browse') }}" class="btn btn-primary" style="font-size:13px;">{{ __('Browse Jobs') }}</a>
</div>
@else

<div style="display:flex; flex-direction:column; gap:10px;">
@foreach($bookmarks as $bookmark)
@php $listing = $bookmark->jobListing; @endphp
@if(!$listing) @continue @endif
<div class="card saved-card" style="padding:18px; transition:border-color .15s;">
    <div style="display:flex; align-items:flex-start; gap:14px;">
        {{-- Thumbnail --}}
        @if($listing->cover_image)
        <img src="{{ fileUrl(config('jobstation.upload_paths.work_cover'), $listing->cover_image) }}"
             alt="{{ $listing->title }}" style="width:52px; height:52px; border-radius:10px; object-fit:cover; flex-shrink:0;">
        @else
        <div style="width:52px; height:52px; border-radius:10px; background:rgba(47,84,235,0.08); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" style="opacity:.5;"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>
        </div>
        @endif

        <div style="flex:1; min-width:0;">
            <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:5px; flex-wrap:wrap;">
                <h3 style="font-size:14.5px; font-weight:600; color:var(--fg); margin:0;">{{ $listing->title }}</h3>
                <div style="display:flex; align-items:center; gap:8px; flex-shrink:0;">
                    @if($listing->isClosed)
                    <span style="font-size:11px; padding:2px 8px; border-radius:999px; background:var(--surface-2); color:var(--fg-4); border:1px solid var(--border);">Closed</span>
                    @endif
                    {{-- Remove bookmark --}}
                    <div x-data="{ removing: false }">
                        <button type="button" @click="
                            if(removing) return;
                            removing = true;
                            fetch('{{ route('user.jobs.bookmark', $listing->id) }}', {
                                method:'POST',
                                headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}
                            }).then(() => { $el.closest('.saved-card').remove(); });"
                            title="{{ __('Remove from saved') }}"
                            style="display:flex; align-items:center; justify-content:center; width:32px; height:32px; border-radius:8px; border:1px solid var(--border); background:var(--surface-2); cursor:pointer; color:var(--accent); transition:all .15s;"
                            :disabled="removing">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
                        </button>
                    </div>
                    @if(!$listing->isClosed)
                    <a href="{{ route('user.jobs.show', $listing->id) }}" class="btn btn-primary" style="font-size:12px; padding:5px 14px;">{{ __('View') }}</a>
                    @else
                    <a href="{{ route('user.jobs.show', $listing->id) }}" class="btn" style="font-size:12px; padding:5px 14px;">{{ __('View') }}</a>
                    @endif
                </div>
            </div>

            <div style="font-size:12px; color:var(--fg-3); margin-bottom:10px; display:flex; align-items:center; gap:5px;">
                {{ $listing->employer->fullname ?? $listing->employer->username ?? 'Employer' }}
                @if(($listing->employer->kyc_status ?? 0) === 1)
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#22C55E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                @endif
            </div>

            <div style="display:flex; flex-wrap:wrap; gap:6px; margin-bottom:10px;">
                @if($listing->category)
                <span style="font-size:11px; padding:2px 8px; border-radius:6px; background:var(--surface-2); color:var(--fg-3);">{{ $listing->category->name }}</span>
                @endif
                <span style="font-size:11px; padding:2px 8px; border-radius:6px; background:var(--surface-2); color:var(--fg-3);">{{ $listing->locationTypeLabel }}</span>
                <span style="font-size:11px; padding:2px 8px; border-radius:6px; background:var(--surface-2); color:var(--fg-3);">{{ $listing->employmentTypeLabel }}</span>
                @if($listing->salaryRange !== 'Undisclosed')
                <span style="font-size:11px; padding:2px 8px; border-radius:6px; background:rgba(47,84,235,0.08); color:var(--accent); font-weight:500;">{{ $listing->salaryRange }}</span>
                @endif
                @if($listing->closes_at && !$listing->closes_at->isPast())
                <span style="font-size:11px; padding:2px 8px; border-radius:6px; background:var(--surface-2); color:var(--fg-3);">Closes {{ $listing->closes_at->format('M d') }}</span>
                @endif
            </div>

            <p style="font-size:12.5px; color:var(--fg-3); margin:0; line-height:1.5; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;">
                {{ Str::limit(strip_tags($listing->description), 160) }}
            </p>
        </div>
    </div>
</div>
@endforeach
</div>

<div style="margin-top:20px;">{{ $bookmarks->links() }}</div>

@endif

<style>
.saved-card:hover { border-color: rgba(47,84,235,0.25) !important; }
</style>
@endsection
