@extends('user.layouts.app')
@section('title', 'Chat — ' . ($other->fullname ?: $other->username))
@section('page-title', 'Message Thread')

@section('content')
@php $authId = auth()->id(); @endphp

{{-- Breadcrumb --}}
<div style="display:flex; align-items:center; gap:6px; font-size:12px; color:var(--fg-4); margin-bottom:20px; flex-wrap:wrap;">
    @if($isEmployer)
        <a href="{{ route('user.jobs.listings.show', $app->listing->id) }}" style="color:var(--fg-4); text-decoration:none;" onmouseover="this.style.color='var(--fg-2)'" onmouseout="this.style.color='var(--fg-4)'">{{ $app->listing->title }}</a>
        <svg width="11" height="11" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M7 4l5 5-5 5"/></svg>
        <a href="{{ route('user.jobs.listings.applications.review', [$app->listing->id, $app->id]) }}" style="color:var(--fg-4); text-decoration:none;" onmouseover="this.style.color='var(--fg-2)'" onmouseout="this.style.color='var(--fg-4)'">Application</a>
    @else
        <a href="{{ route('user.jobs.my-applications') }}" style="color:var(--fg-4); text-decoration:none;" onmouseover="this.style.color='var(--fg-2)'" onmouseout="this.style.color='var(--fg-4)'">My Applications</a>
    @endif
    <svg width="11" height="11" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M7 4l5 5-5 5"/></svg>
    <span style="color:var(--fg-3);">Chat</span>
</div>

<div style="">

    {{-- Header card --}}
    <div class="card" style="padding:16px 20px; margin-bottom:16px; display:flex; align-items:center; gap:14px;">
        {{-- Other party avatar --}}
        @php
            $init = strtoupper(substr($other->firstname ?? $other->username, 0, 1));
            $palette = ['#2f54eb','#FF7A59','#60A5FA','#F59E0B','#EC4899','#8B5CF6','#06B6D4'];
            $c = $palette[ord($init) % count($palette)];
        @endphp
        <div style="width:44px; height:44px; border-radius:50%; background:{{ $c }}; display:flex; align-items:center; justify-content:center; font-size:17px; font-weight:700; color:white; flex-shrink:0;">{{ $init }}</div>
        <div style="flex:1; min-width:0;">
            <div style="font-size:14.5px; font-weight:600; color:var(--fg);">{{ $other->fullname ?: $other->username }}</div>
            <div style="font-size:12px; color:var(--fg-4);">
                {{ $isEmployer ? 'Applicant' : 'Employer' }} · Re: {{ \Illuminate\Support\Str::limit($app->listing->title, 50) }}
            </div>
        </div>
        <a href="{{ $isEmployer ? route('user.jobs.listings.applications.review', [$app->listing->id, $app->id]) : route('user.jobs.show', $app->listing->id) }}"
           style="font-size:12px; color:var(--fg-3); text-decoration:none; white-space:nowrap;" onmouseover="this.style.color='var(--accent)'" onmouseout="this.style.color='var(--fg-3)'">
            View {{ $isEmployer ? 'Application' : 'Job' }} →
        </a>
    </div>

    {{-- Messages thread --}}
    <div class="card" style="padding:0; overflow:hidden; margin-bottom:16px;" id="thread-box">

        @if($messages->isEmpty())
        <div style="padding:60px 24px; text-align:center; color:var(--fg-4); font-size:13.5px;">
            No messages yet. Start the conversation below.
        </div>
        @else
        <div style="padding:20px; display:flex; flex-direction:column; gap:16px; max-height:520px; overflow-y:auto;" id="msg-list">
            @foreach($messages as $msg)
            @php $mine = $msg->sender_id === $authId; @endphp
            <div style="display:flex; {{ $mine ? 'justify-content:flex-end;' : 'justify-content:flex-start;' }}">
                <div style="max-width:78%; display:flex; flex-direction:column; {{ $mine ? 'align-items:flex-end;' : 'align-items:flex-start;' }} gap:3px;">
                    @if(!$mine)
                    <div style="font-size:11.5px; color:var(--fg-4); padding:0 4px;">{{ $msg->sender->fullname ?: $msg->sender->username }}</div>
                    @endif
                    <div style="padding:10px 14px; border-radius:{{ $mine ? '14px 14px 4px 14px' : '14px 14px 14px 4px' }};
                                background:{{ $mine ? 'var(--accent)' : 'var(--surface-2)' }};
                                color:{{ $mine ? 'white' : 'var(--fg-2)' }};
                                font-size:13.5px; line-height:1.55; word-break:break-word;">{{ $msg->body }}</div>
                    <div style="font-size:11px; color:var(--fg-4); padding:0 4px; display:flex; align-items:center; gap:4px;">
                        {{ $msg->created_at->format('M d, H:i') }}
                        @if($mine && $msg->is_read)
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#22C55E" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        @elseif($mine)
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="var(--fg-4)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    {{-- Compose --}}
    <div class="card" style="padding:18px;">
        @if(session('success'))
        <div style="padding:9px 12px; border-radius:8px; background:rgba(34,197,94,0.08); border:1px solid rgba(34,197,94,0.2); color:#22C55E; font-size:13px; margin-bottom:14px;">{{ session('success') }}</div>
        @endif
        <form method="POST" action="{{ route('user.jobs.applications.thread.send', $app->id) }}"
              style="display:flex; gap:10px; align-items:flex-end;">
            @csrf
            <div style="flex:1;">
                <textarea name="body" rows="3"
                          placeholder="Type your message…"
                          style="resize:none; {{ $errors->has('body') ? 'border-color:#EF4444;' : '' }}">{{ old('body') }}</textarea>
                @error('body')<p style="font-size:11.5px; color:#EF4444; margin:4px 0 0;">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="btn btn-primary" style="padding:10px 20px; font-size:13px; flex-shrink:0; height:44px; display:flex; align-items:center; gap:6px;">
                <i data-lucide="send" style="width:14px; height:14px;"></i> Send
            </button>
        </form>
    </div>

</div>

<script>
    // Scroll to bottom of message list on load
    const list = document.getElementById('msg-list');
    if (list) list.scrollTop = list.scrollHeight;
</script>

<style>
textarea:focus { border-color: var(--accent) !important; box-shadow: 0 0 0 3px rgba(47,84,235,0.12); }
</style>
@endsection
