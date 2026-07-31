@extends('admin.layouts.app')
@section('title', 'Plugins')
@section('page-title', 'Extension Manager')

@section('content')

<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px;">
    @forelse($plugins as $plugin)
    <div class="jobstation-card" style="padding:20px;" x-data="{ editOpen: false }">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:12px;">
            <div style="flex:1;min-width:0;padding-right:12px;">
                <div style="font-weight:600;font-size:14px;color:var(--fg);">{{ $plugin->name }}</div>
                <div style="font-size:12.5px;color:var(--fg-3);margin-top:3px;line-height:1.5;">{{ $plugin->description }}</div>
            </div>
            @if($plugin->status)
                <span class="badge-success" style="font-size:11px;flex-shrink:0;">Active</span>
            @else
                <span class="badge-default" style="font-size:11px;flex-shrink:0;">Inactive</span>
            @endif
        </div>

        @if($plugin->support)
        <div style="font-size:12px;color:var(--fg-4);margin-bottom:12px;">
            Support: <a href="{{ $plugin->support }}" target="_blank" style="color:var(--accent);text-decoration:none;">Documentation ↗</a>
        </div>
        @endif

        <div style="display:flex;gap:8px;padding-top:14px;border-top:1px solid var(--border);">
            <form method="POST" action="{{ route('admin.plugins.toggle', $plugin->id) }}" style="flex:1;">
                @csrf
                <button type="submit"
                        style="width:100%;padding:7px;font-size:12.5px;border-radius:8px;cursor:pointer;font-family:inherit;font-weight:500;border:1px solid {{ $plugin->status ? 'rgba(239,68,68,0.25)' : 'transparent' }};background:{{ $plugin->status ? 'transparent' : '#22C55E' }};color:{{ $plugin->status ? '#EF4444' : '#fff' }};transition:all .14s;"
                        onmouseover="this.style.background='{{ $plugin->status ? 'rgba(239,68,68,0.08)' : '#16a34a' }}'"
                        onmouseout="this.style.background='{{ $plugin->status ? 'transparent' : '#22C55E' }}'">
                    {{ $plugin->status ? 'Deactivate' : 'Activate' }}
                </button>
            </form>
            <button @click="editOpen = !editOpen" class="btn" style="padding:7px 12px;font-size:12.5px;display:flex;align-items:center;gap:5px;">
                <i data-lucide="settings-2" style="width:13px;height:13px;"></i> Configure
            </button>
        </div>

        <div x-show="editOpen" x-cloak x-transition style="margin-top:12px;">
            <form method="POST" action="{{ route('admin.plugins.update', $plugin->id) }}">
                @csrf @method('PUT')

                @php $shortcodes = $plugin->shortcode ?? []; @endphp
                @foreach($shortcodes as $key => $val)
                    <label style="display:block;font-size:11.5px;color:var(--fg-3);margin:0 0 4px;">
                        {{ ucwords(str_replace('_', ' ', $key)) }}
                    </label>
                    <input type="text" name="shortcode[{{ $key }}]" value="{{ $val }}" placeholder="{{ $key }}"
                           style="width:100%;font-size:12px;margin-bottom:10px;">
                @endforeach

                @if($plugin->script !== null)
                    <details style="margin-bottom:8px;">
                        <summary style="font-size:11.5px;color:var(--fg-4);cursor:pointer;">Embed script (advanced)</summary>
                        <textarea name="script" rows="5"
                                  style="font-family:ui-monospace,monospace;font-size:11px;resize:vertical;margin-top:6px;">{{ $plugin->script }}</textarea>
                    </details>
                @endif

                <button type="submit" class="btn-primary" style="width:100%;padding:7px;font-size:13px;">Save</button>
            </form>
        </div>
    </div>
    @empty
    <div class="jobstation-card" style="padding:56px;text-align:center;color:var(--fg-3);grid-column:1/-1;">
        <i data-lucide="puzzle" style="width:36px;height:36px;display:block;margin:0 auto 12px;opacity:0.3;"></i>
        <div style="font-size:14px;">No plugins installed. Run the seeder to populate defaults.</div>
    </div>
    @endforelse
</div>

@endsection
