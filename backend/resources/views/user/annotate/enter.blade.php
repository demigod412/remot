@extends('user.layouts.app')

@section('title', 'Open a task')
@section('page-title', 'Open a task')

@section('content')
{{-- One field, nothing else. This screen is reached by someone who already knows
     what they are doing and just wants in; anything more is friction. --}}
<div style="max-width:440px;margin:40px auto;">

    @if (session('error'))
        <div style="display:flex;align-items:flex-start;gap:10px;padding:13px 15px;border-radius:10px;background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.28);margin-bottom:20px;">
            <i data-lucide="alert-circle" style="width:16px;height:16px;color:#EF4444;flex-shrink:0;margin-top:1px;"></i>
            <span style="font-size:13px;color:var(--fg-2);line-height:1.55;">{{ session('error') }}</span>
        </div>
    @endif

    <div class="jobstation-card" style="padding:28px;">
        <h1 style="font-size:20px;font-weight:600;margin:0 0 6px;color:var(--fg);letter-spacing:-0.3px;">
            Enter your annotate code
        </h1>
        <p style="font-size:13px;color:var(--fg-3);line-height:1.6;margin:0 0 22px;">
            You were given this when your application was approved. It looks like
            <span class="mono" style="color:var(--fg-2);">AN-4F9QZK2M</span>.
        </p>

        <form method="POST" action="{{ route('user.annotate.open') }}"
              x-data="{ sending: false }" @submit="sending = true">
            @csrf

            <input type="text" name="annotate_code" required autofocus
                   value="{{ old('annotate_code') }}"
                   autocomplete="off" autocapitalize="characters" spellcheck="false"
                   placeholder="AN-XXXXXXXX"
                   {{-- Uppercased as they type: the codes are uppercase, and being
                        told your correct code is wrong because of case is maddening. --}}
                   oninput="this.value = this.value.toUpperCase()"
                   style="width:100%;padding:14px 16px;font-size:18px;font-family:ui-monospace,monospace;letter-spacing:1.5px;text-align:center;border:1.5px solid {{ $errors->has('annotate_code') ? '#EF4444' : 'var(--border)' }};border-radius:10px;">

            @error('annotate_code')
                <p aria-live="polite" style="font-size:12.5px;color:#EF4444;margin:9px 0 0;">{{ $message }}</p>
            @enderror

            <button type="submit" class="btn btn-primary" x-bind:disabled="sending"
                    x-bind:class="sending ? 'is-busy' : ''"
                    style="width:100%;margin-top:16px;padding:13px;font-size:14.5px;justify-content:center;">
                <span x-show="!sending">Open task</span>
                <span x-show="sending" x-cloak>Opening&hellip;</span>
            </button>
        </form>
    </div>

    <p style="text-align:center;font-size:12.5px;color:var(--fg-3);margin:18px 0 0;line-height:1.6;">
        Lost your code? It is on the task in
        <a href="{{ route('user.tasks.index') }}" style="color:var(--accent);">My Tasks</a>.
    </p>
</div>
@endsection
