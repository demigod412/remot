@extends('user.layouts.app')
@section('title', 'New Support Ticket')
@section('page-title', 'New Ticket')

@section('content')

{{-- Back --}}
<a href="{{ route('user.helpdesk.index') }}"
   style="display:inline-flex; align-items:center; gap:6px; font-size:12.5px; color:var(--fg-3); text-decoration:none; margin-bottom:18px; transition:color .14s;"
   onmouseover="this.style.color='var(--fg)'" onmouseout="this.style.color='var(--fg-3)'">
    <i data-lucide="arrow-left" style="width:14px; height:14px;"></i> Back to tickets
</a>

<div style="display:grid; grid-template-columns:1fr 260px; gap:20px; align-items:start;" class="helpdesk-create-grid">

    {{-- Form card --}}
    <div class="jobstation-card" style="padding:28px;">
        <div style="font-size:15px; font-weight:600; color:var(--fg); margin-bottom:4px;">Open a support ticket</div>
        <div style="font-size:12.5px; color:var(--fg-3); margin-bottom:24px;">Describe your issue and our team will respond promptly.</div>

        <form method="POST" action="{{ route('user.helpdesk.store') }}" enctype="multipart/form-data">
            @csrf
            <div style="display:flex; flex-direction:column; gap:18px;">

                <div>
                    <label style="display:block; font-size:12px; font-weight:500; color:var(--fg-2); margin-bottom:6px;">
                        Subject <span style="color:var(--danger);">*</span>
                    </label>
                    <input type="text" name="subject" value="{{ old('subject') }}"
                           placeholder="Brief summary of your issue"
                           style="{{ $errors->has('subject') ? 'border-color:var(--danger);' : '' }}"
                           required>
                    @error('subject')
                    <div style="font-size:12px; color:var(--danger); margin-top:4px;">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <label style="display:block; font-size:12px; font-weight:500; color:var(--fg-2); margin-bottom:6px;">
                        Priority <span style="color:var(--danger);">*</span>
                    </label>
                    <select name="priority" style="{{ $errors->has('priority') ? 'border-color:var(--danger);' : '' }}" required>
                        <option value="1" {{ old('priority') == 1 ? 'selected' : '' }}>Low — general question or feedback</option>
                        <option value="2" {{ old('priority', 2) == 2 ? 'selected' : '' }}>Medium — something is not working</option>
                        <option value="3" {{ old('priority') == 3 ? 'selected' : '' }}>High — urgent, affecting my account</option>
                    </select>
                    @error('priority')
                    <div style="font-size:12px; color:var(--danger); margin-top:4px;">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <label style="display:block; font-size:12px; font-weight:500; color:var(--fg-2); margin-bottom:6px;">
                        Message <span style="color:var(--danger);">*</span>
                    </label>
                    <textarea name="message" rows="6"
                              placeholder="Describe your issue in detail. Include steps to reproduce if applicable."
                              style="resize:vertical; {{ $errors->has('message') ? 'border-color:var(--danger);' : '' }}"
                              required>{{ old('message') }}</textarea>
                    <div style="font-size:11.5px; color:var(--fg-4); margin-top:4px;">Minimum 20 characters</div>
                    @error('message')
                    <div style="font-size:12px; color:var(--danger); margin-top:4px;">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <label style="display:block; font-size:12px; font-weight:500; color:var(--fg-2); margin-bottom:6px;">
                        Attachments <span style="font-weight:400; color:var(--fg-4);">(up to 3 files — images, PDF, zip)</span>
                    </label>
                    <div style="border:2px dashed var(--border-strong); border-radius:10px; padding:16px; background:var(--surface-2);">
                        <input type="file" name="attachments[]" multiple accept="image/*,application/pdf,.zip,.txt"
                               style="font-size:13px; color:var(--fg-3); width:100%; cursor:pointer; background:transparent; border:none; box-shadow:none; padding:0;">
                    </div>
                    @error('attachments')
                    <div style="font-size:12px; color:var(--danger); margin-top:4px;">{{ $message }}</div>
                    @enderror
                </div>

                <div style="display:flex; gap:10px; padding-top:4px;">
                    <button type="submit" class="btn btn-primary" style="font-size:13px; padding:10px 22px; gap:6px;">
                        <i data-lucide="send" style="width:13px; height:13px;"></i> Submit ticket
                    </button>
                    <a href="{{ route('user.helpdesk.index') }}" class="btn" style="font-size:13px; padding:10px 18px; text-decoration:none;">Cancel</a>
                </div>

            </div>
        </form>
    </div>

    {{-- Sidebar --}}
    <div style="display:flex; flex-direction:column; gap:12px;">

        {{-- Response times --}}
        <div class="jobstation-card" style="padding:18px;">
            <div style="font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.07em; color:var(--fg-3); margin-bottom:12px; display:flex; align-items:center; gap:6px;">
                <i data-lucide="clock" style="width:13px; height:13px; color:var(--accent);"></i>
                Response times
            </div>
            @foreach([['High','#EF4444','< 4 hours'],['Medium','#F59E0B','< 24 hours'],['Low','#22C55E','< 48 hours']] as [$p,$c,$t])
            <div style="display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid var(--border); {{ $loop->last ? 'border:none;' : '' }}">
                <div style="display:flex; align-items:center; gap:8px;">
                    <span style="width:8px; height:8px; border-radius:50%; background:{{ $c }}; display:inline-block; flex-shrink:0;"></span>
                    <span style="font-size:12.5px; color:var(--fg-2);">{{ $p }}</span>
                </div>
                <span style="font-size:12px; font-weight:600; color:var(--fg); font-family:ui-monospace,monospace;">{{ $t }}</span>
            </div>
            @endforeach
        </div>

        {{-- Tips --}}
        <div class="jobstation-card" style="padding:18px;">
            <div style="font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.07em; color:var(--fg-3); margin-bottom:12px; display:flex; align-items:center; gap:6px;">
                <i data-lucide="lightbulb" style="width:13px; height:13px; color:#F59E0B;"></i>
                Tips for faster help
            </div>
            <div style="display:flex; flex-direction:column; gap:10px;">
                @foreach([['target','Be specific about the page or feature'],['image','Attach screenshots when possible'],['hash','Include any transaction or submission IDs']] as [$icon,$text])
                <div style="display:flex; align-items:flex-start; gap:8px;">
                    <i data-lucide="{{ $icon }}" style="width:13px; height:13px; color:var(--accent); flex-shrink:0; margin-top:2px;"></i>
                    <span style="font-size:12px; color:var(--fg-3); line-height:1.5;">{{ $text }}</span>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Safe note --}}
        <div class="jobstation-card" style="padding:16px; display:flex; align-items:flex-start; gap:12px;">
            <div style="width:32px; height:32px; border-radius:8px; background:var(--accent-soft); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <i data-lucide="shield-check" style="width:15px; height:15px; color:var(--accent);"></i>
            </div>
            <div>
                <div style="font-size:12.5px; font-weight:600; color:var(--fg); margin-bottom:2px;">Safe & private</div>
                <div style="font-size:11.5px; color:var(--fg-3); line-height:1.5;">Only visible to you and our support team.</div>
            </div>
        </div>

    </div>
</div>

<style>
@media (max-width: 860px) { .helpdesk-create-grid { grid-template-columns: 1fr !important; } }
</style>

@endsection
