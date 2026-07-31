@extends('admin.layouts.app')
@section('title', 'Edit Section')
@section('page-title', 'Edit Content Section')

@section('content')

<div style="display:flex;align-items:center;gap:6px;font-size:12.5px;color:var(--fg-3);margin-bottom:20px;">
    <a href="{{ route('admin.content.index') }}" style="color:var(--fg-3);text-decoration:none;transition:.12s;"
       onmouseover="this.style.color='var(--fg)'" onmouseout="this.style.color='var(--fg-3)'">Content Sections</a>
    <i data-lucide="chevron-right" style="width:13px;height:13px;color:var(--fg-4);"></i>
    <span style="color:var(--fg-2);text-transform:capitalize;">{{ str_replace('_', ' ', $section->section_key) }}</span>
</div>

<div style="display:grid;grid-template-columns:1fr 260px;gap:24px;align-items:start;" class="content-edit-grid">

    {{-- Editor --}}
    <div class="jobstation-card" style="padding:24px;"
         x-data="{
             raw: {{ json_encode(json_encode($section->section_data, JSON_PRETTY_PRINT)) }},
             valid: true,
             validate() { try { JSON.parse(this.raw); this.valid = true; } catch(e) { this.valid = false; } }
         }">

        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
            <div style="font-size:14px;font-weight:600;color:var(--fg);text-transform:capitalize;">{{ str_replace('_', ' ', $section->section_key) }}</div>
            <span style="font-size:12px;font-weight:500;"
                  :style="valid ? 'color:#22C55E' : 'color:#EF4444'"
                  x-text="valid ? '✓ Valid JSON' : '✗ Invalid JSON'"></span>
        </div>

        <form method="POST" action="{{ route('admin.content.update', $section->id) }}">
            @csrf @method('PUT')

            @if(session('success'))
            <div style="padding:10px 14px;background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.25);border-radius:8px;font-size:13px;color:#22C55E;margin-bottom:16px;">{{ session('success') }}</div>
            @endif

            <div style="margin-bottom:14px;">
                <textarea name="section_data" rows="22" x-model="raw" @input="validate()"
                          style="font-family:ui-monospace,monospace;font-size:12px;resize:vertical;width:100%;line-height:1.6;"
                          :style="!valid ? 'border-color:#EF4444' : ''"
                          spellcheck="false"></textarea>
                @error('section_data') <div style="font-size:12px;color:#EF4444;margin-top:4px;">{{ $message }}</div> @enderror
                <div style="font-size:11.5px;color:var(--fg-4);margin-top:4px;">Edit the JSON directly. All keys depend on how this section is used in the view.</div>
            </div>

            <div style="display:flex;gap:10px;">
                <button type="submit" class="btn btn-primary" style="padding:9px 22px;"
                        :disabled="!valid" :style="!valid ? 'opacity:.45;cursor:not-allowed;' : ''">
                    <i data-lucide="save" style="width:14px;height:14px;"></i> Save Changes
                </button>
                <a href="{{ route('admin.content.index') }}" class="btn" style="padding:9px 18px;">Cancel</a>
            </div>
        </form>
    </div>

    {{-- Sidebar --}}
    <div style="display:flex;flex-direction:column;gap:14px;">
        <div class="jobstation-card" style="padding:18px;">
            <div style="font-size:11px;font-weight:600;color:var(--fg-4);text-transform:uppercase;letter-spacing:.06em;margin-bottom:12px;">Section Info</div>
            <div style="display:flex;flex-direction:column;gap:8px;font-size:13px;">
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <span style="color:var(--fg-3);">Key</span>
                    <span style="font-family:ui-monospace,monospace;font-size:11.5px;color:var(--accent);">{{ $section->section_key }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <span style="color:var(--fg-3);">Last updated</span>
                    <span style="font-size:12px;color:var(--fg-2);">{{ $section->updated_at->diffForHumans() }}</span>
                </div>
            </div>
        </div>

        <div class="jobstation-card" style="padding:18px;">
            <div style="font-size:11px;font-weight:600;color:var(--fg-4);text-transform:uppercase;letter-spacing:.06em;margin-bottom:12px;">Tips</div>
            <div style="display:flex;flex-direction:column;gap:10px;">
                <div style="display:flex;align-items:flex-start;gap:8px;font-size:12.5px;color:var(--fg-3);">
                    <i data-lucide="info" style="width:13px;height:13px;flex-shrink:0;margin-top:2px;color:#60A5FA;"></i>
                    Edit raw JSON to change content.
                </div>
                <div style="display:flex;align-items:flex-start;gap:8px;font-size:12.5px;color:var(--fg-3);">
                    <i data-lucide="alert-triangle" style="width:13px;height:13px;flex-shrink:0;margin-top:2px;color:#F59E0B;"></i>
                    Save is disabled if JSON is invalid.
                </div>
                <div style="display:flex;align-items:flex-start;gap:8px;font-size:12.5px;color:var(--fg-3);">
                    <i data-lucide="eye" style="width:13px;height:13px;flex-shrink:0;margin-top:2px;color:#22C55E;"></i>
                    Changes appear on the public site immediately.
                </div>
            </div>
        </div>
    </div>

</div>

<style>
@media (max-width: 860px) { .content-edit-grid { grid-template-columns: 1fr !important; } }
</style>

@endsection
