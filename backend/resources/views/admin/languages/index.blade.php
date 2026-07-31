@extends('admin.layouts.app')
@section('title', 'Languages')
@section('page-title', 'Languages')

@section('content')

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;">
    <div style="font-size:13px;color:var(--fg-3);">{{ $languages->count() }} languages configured</div>
    <button x-data @click="$dispatch('open-add-lang')" class="btn-primary" style="padding:8px 16px;font-size:13px;display:flex;align-items:center;gap:6px;">
        <i data-lucide="plus" style="width:14px;height:14px;"></i> Add Language
    </button>
</div>

<div class="jobstation-card" style="overflow:hidden;">
    <table style="width:100%;border-collapse:collapse;font-size:13px;">
        <thead>
            <tr style="border-bottom:1px solid var(--border);">
                <th style="padding:10px 20px;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:0.07em;color:var(--fg-3);font-weight:500;">Language</th>
                <th style="padding:10px 20px;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:0.07em;color:var(--fg-3);font-weight:500;">Code</th>
                <th style="padding:10px 20px;text-align:center;font-size:11px;text-transform:uppercase;letter-spacing:0.07em;color:var(--fg-3);font-weight:500;">RTL</th>
                <th style="padding:10px 20px;text-align:center;font-size:11px;text-transform:uppercase;letter-spacing:0.07em;color:var(--fg-3);font-weight:500;">Default</th>
                <th style="padding:10px 20px;text-align:right;font-size:11px;text-transform:uppercase;letter-spacing:0.07em;color:var(--fg-3);font-weight:500;">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($languages as $lang)
            <tr style="border-bottom:1px solid var(--border);"
                onmouseover="this.style.background='var(--surface-2)'"
                onmouseout="this.style.background=''">
                <td style="padding:13px 20px;">
                    <div style="display:flex;align-items:center;gap:10px;">
                        @if($lang->icon)<span style="font-size:20px;line-height:1;">{{ $lang->icon }}</span>@endif
                        <span style="font-weight:500;color:var(--fg);">{{ $lang->name }}</span>
                    </div>
                </td>
                <td style="padding:13px 20px;font-family:ui-monospace,monospace;font-size:12px;color:var(--fg-3);">{{ $lang->code }}</td>
                <td style="padding:13px 20px;text-align:center;font-size:12px;color:var(--fg-3);">{{ $lang->text_align ? 'Yes' : '—' }}</td>
                <td style="padding:13px 20px;text-align:center;">
                    @if($lang->is_default)
                        <span class="badge-success" style="font-size:11px;">Default</span>
                    @endif
                </td>
                <td style="padding:13px 20px;text-align:right;">
                    <div style="display:flex;align-items:center;justify-content:flex-end;gap:4px;">
                        <a href="{{ route('admin.languages.edit', $lang->id) }}"
                           style="padding:6px;border-radius:7px;color:var(--fg-3);display:flex;align-items:center;"
                           title="Edit"
                           onmouseover="this.style.background='var(--surface-2)';this.style.color='var(--fg)'"
                           onmouseout="this.style.background='transparent';this.style.color='var(--fg-3)'">
                            <i data-lucide="edit-3" style="width:15px;height:15px;"></i>
                        </a>
                        @if(!$lang->is_default)
                        <form method="POST" action="{{ route('admin.languages.default', $lang->id) }}">
                            @csrf
                            <button type="submit" title="Set Default"
                                    style="padding:6px;border-radius:7px;background:transparent;border:none;cursor:pointer;color:var(--fg-3);display:flex;align-items:center;"
                                    onmouseover="this.style.background='var(--surface-2)';this.style.color='var(--fg)'"
                                    onmouseout="this.style.background='transparent';this.style.color='var(--fg-3)'">
                                <i data-lucide="star" style="width:15px;height:15px;"></i>
                            </button>
                        </form>
                        <form method="POST" action="{{ route('admin.languages.delete', $lang->id) }}"
                              onsubmit="return confirm('Delete {{ $lang->name }}?')">
                            @csrf @method('DELETE')
                            <button type="submit" title="Delete"
                                    style="padding:6px;border-radius:7px;background:transparent;border:none;cursor:pointer;color:var(--fg-4);display:flex;align-items:center;"
                                    onmouseover="this.style.background='rgba(239,68,68,0.08)';this.style.color='#EF4444'"
                                    onmouseout="this.style.background='transparent';this.style.color='var(--fg-4)'">
                                <i data-lucide="trash-2" style="width:15px;height:15px;"></i>
                            </button>
                        </form>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" style="padding:56px;text-align:center;color:var(--fg-3);">
                    <i data-lucide="globe" style="width:32px;height:32px;display:block;margin:0 auto 12px;opacity:0.3;"></i>
                    No languages configured.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Add Language Modal --}}
<div x-data="{ open: false }" @open-add-lang.window="open = true">
    <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="background:rgba(0,0,0,0.5);" x-transition>
        <div @click.outside="open = false"
             class="jobstation-card" style="width:100%;max-width:380px;padding:24px;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
                <h3 style="font-weight:600;font-size:15px;color:var(--fg);">Add Language</h3>
                <button @click="open = false"
                        style="padding:5px;border-radius:6px;background:transparent;border:none;cursor:pointer;color:var(--fg-3);"
                        onmouseover="this.style.background='var(--surface-2)'"
                        onmouseout="this.style.background='transparent'">
                    <i data-lucide="x" style="width:16px;height:16px;display:block;"></i>
                </button>
            </div>
            <form method="POST" action="{{ route('admin.languages.store') }}">
                @csrf
                <div style="display:flex;flex-direction:column;gap:14px;">
                    <div>
                        <label style="display:block;font-size:12px;color:var(--fg-3);margin-bottom:5px;">Language Name <span style="color:#EF4444;">*</span></label>
                        <input type="text" name="name" value="{{ old('name') }}" placeholder="English" required>
                    </div>
                    <div>
                        <label style="display:block;font-size:12px;color:var(--fg-3);margin-bottom:5px;">Code <span style="color:#EF4444;">*</span></label>
                        <input type="text" name="code" value="{{ old('code') }}" placeholder="en" style="font-family:ui-monospace,monospace;" required>
                    </div>
                    <div>
                        <label style="display:block;font-size:12px;color:var(--fg-3);margin-bottom:5px;">Flag Emoji</label>
                        <input type="text" name="icon" value="{{ old('icon') }}" placeholder="🇺🇸">
                    </div>
                    <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--fg-2);cursor:pointer;">
                        <input type="checkbox" name="text_align" value="1" {{ old('text_align') ? 'checked' : '' }}>
                        RTL (Right to Left)
                    </label>
                    <div style="display:flex;gap:10px;padding-top:4px;">
                        <button type="submit" class="btn-primary" style="flex:1;padding:9px;font-size:13px;">Add Language</button>
                        <button type="button" @click="open = false" class="btn" style="padding:9px 16px;font-size:13px;">Cancel</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@if($errors->any())
<script>document.addEventListener('DOMContentLoaded', () => window.dispatchEvent(new CustomEvent('open-add-lang')));</script>
@endif

@endsection
