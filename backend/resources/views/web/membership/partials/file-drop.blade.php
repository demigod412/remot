{{--
    Styled drag-and-drop file field for the membership application form.

    Usage:
        @include('web.membership.partials.file-drop', [
            'name'        => 'resume',
            'label'       => __('CV / Resume'),
            'required'    => true,
            'requiredWhen'=> 'isBusiness',   // optional Alpine expression
            'hint'        => __('Your work history and skills.'),
        ])

    The real <input type="file"> stays in the DOM and is what the form actually posts,
    so this degrades to a native picker with JavaScript off. Dropped files are assigned
    onto that input's .files, so submission is a plain multipart POST with no AJAX.

    ON `required`: passing 'required' => true marks the label AND sets the attribute, so
    the browser blocks submission with an empty field instead of letting the server
    reject it after an upload. For a field that lives inside a conditionally shown
    section, pass 'requiredWhen' with an Alpine expression instead — a hidden field
    carrying a plain `required` attribute cannot be focused to report its error, so the
    browser refuses to submit and says nothing. That failure mode has already cost this
    project a working withdrawal form once.

    Client-side type and size checks are a courtesy. MembershipApplicationRequest
    re-validates both.
--}}
@php
    $required     = $required ?? false;
    $requiredWhen = $requiredWhen ?? null;
    $hint         = $hint ?? null;
    $accept       = config('jobstation.membership.allowed_doc_types', ['pdf', 'doc', 'docx']);
    $maxKb        = (int) config('jobstation.membership.max_doc_size_kb', 5120);
    $acceptAttr   = '.' . implode(',.', $accept);

    // Rendered into a double-quoted x-data attribute, so it must not contain double
    // quotes. json_encode() would, hence building the JS literal by hand.
    $allowedJs = "['" . implode("','", $accept) . "']";
    $maxLabel  = $maxKb >= 1024 ? round($maxKb / 1024, 1) . ' MB' : $maxKb . ' KB';
    $hasError  = $errors->has($name);
@endphp

{{-- Emitted once no matter how many drop zones the page includes. --}}
@once
<style>
.fd-zone {
    border: 1.5px dashed var(--border);
    border-radius: 11px;
    background: transparent;
    transition: border-color .15s, background .15s, box-shadow .15s;
}
.fd-zone[role="button"] {
    padding: 26px 18px;
    text-align: center;
    cursor: pointer;
}
.fd-zone[role="button"]:hover { border-color: var(--accent); }
.fd-zone[role="button"]:focus-visible {
    outline: none;
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(99,102,241,0.18);
}
.fd-zone--drag {
    border-color: var(--accent) !important;
    background: rgba(99,102,241,0.07) !important;
}
.fd-zone--error {
    border-color: #fca5a5 !important;
    background: #fef2f2 !important;
}
.fd-zone--filled {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 13px 14px;
    border-style: solid;
    border-color: rgba(34,197,94,0.35);
    background: rgba(34,197,94,0.05);
}
.fd-icon {
    width: 26px; height: 26px;
    color: var(--muted);
    margin: 0 auto 9px;
    display: block;
    transition: transform .15s, color .15s;
}
.fd-zone--drag .fd-icon { color: var(--accent); transform: translateY(-2px) scale(1.06); }
.fd-primary   { font-size: 13.5px; font-weight: 500; color: var(--text); }
.fd-secondary { font-size: 12.5px; color: var(--muted); margin-top: 4px; }
.fd-link      { color: var(--accent); text-decoration: underline; text-underline-offset: 2px; }
.fd-badge {
    flex-shrink: 0;
    min-width: 42px;
    padding: 5px 7px;
    border-radius: 6px;
    background: var(--accent);
    color: #fff;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: .04em;
    text-align: center;
    font-family: ui-monospace, monospace;
}
.fd-name {
    font-size: 13.5px; font-weight: 500; color: var(--text);
    overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.fd-meta {
    display: flex; align-items: center; gap: 5px;
    font-size: 11.5px; color: var(--muted); margin-top: 3px;
}
.fd-btn {
    border: 1px solid var(--border);
    background: #fff;
    color: var(--text);
    border-radius: 6px;
    padding: 5px 10px;
    font-size: 11.5px;
    cursor: pointer;
}
.fd-btn:hover { border-color: var(--accent); color: var(--accent); }
.fd-btn--danger:hover { border-color: #fca5a5; color: #dc2626; }
</style>
@endonce

<div style="margin-bottom:20px;"
     x-data="{
        fileName: '',
        fileSize: 0,
        ext: '',
        error: '{{ $hasError ? 'server' : '' }}',
        depth: 0,
        maxKb: {{ $maxKb }},
        allowed: {!! $allowedJs !!},

        get dragging() { return this.depth > 0; },
        get hasFile() { return this.fileName !== ''; },

        human(bytes) {
            if (bytes < 1024) { return bytes + ' B'; }
            if (bytes < 1048576) { return Math.round(bytes / 1024) + ' KB'; }
            return (bytes / 1048576).toFixed(1) + ' MB';
        },

        /* Keeps the extension visible on a long filename, which is the part that
           tells someone at a glance whether they picked the right file. */
        shortName() {
            if (this.fileName.length <= 34) { return this.fileName; }
            const dot = this.fileName.lastIndexOf('.');
            const tail = dot > -1 ? this.fileName.slice(dot) : '';
            const head = dot > -1 ? this.fileName.slice(0, dot) : this.fileName;
            return head.slice(0, 22) + '…' + head.slice(-6) + tail;
        },

        reset() {
            this.fileName = '';
            this.fileSize = 0;
            this.ext = '';
        },

        announce() {
            this.$dispatch('membership-file', { field: '{{ $name }}', attached: this.hasFile });
        },

        pick(files) {
            this.error = '';
            this.reset();
            this.depth = 0;

            if (! files || ! files.length) { this.announce(); return; }

            const f = files[0];
            const ext = (f.name.split('.').pop() || '').toLowerCase();

            if (! this.allowed.includes(ext)) {
                this.error = 'That is a .' + ext + ' file. Please use ' + this.allowed.join(', ').toUpperCase() + '.';
                this.$refs.input.value = '';
                this.announce();
                return;
            }

            if ((f.size / 1024) > this.maxKb) {
                this.error = 'That file is ' + this.human(f.size) + '. The limit is {{ $maxLabel }}.';
                this.$refs.input.value = '';
                this.announce();
                return;
            }

            this.fileName = f.name;
            this.fileSize = f.size;
            this.ext = ext;
            this.announce();
        },

        onDrop(e) {
            this.depth = 0;
            const dt = e.dataTransfer;
            if (! dt || ! dt.files || ! dt.files.length) { return; }
            this.$refs.input.files = dt.files;
            this.pick(dt.files);
        },

        clear() {
            this.$refs.input.value = '';
            this.error = '';
            this.reset();
            this.announce();
        }
     }">

    <div style="display:flex; align-items:baseline; justify-content:space-between; gap:10px; margin-bottom:7px;">
        <label for="{{ $name }}" style="font-size:13px; font-weight:500; color:var(--text);">
            {{ $label }}
            @if($required)
                <span style="color:#dc2626;">*</span>
            @else
                <span style="color:var(--muted); font-weight:400;">({{ __('optional') }})</span>
            @endif
        </label>
        <span style="font-size:11.5px; color:var(--muted); white-space:nowrap;">
            {{ strtoupper(implode(' · ', $accept)) }} &middot; {{ $maxLabel }}
        </span>
    </div>

    {{-- ============ EMPTY / DRAGGING ============ --}}
    <div x-show="!hasFile"
         role="button" tabindex="0"
         aria-describedby="{{ $name }}-hint"
         @click="$refs.input.click()"
         @keydown.enter.prevent="$refs.input.click()"
         @keydown.space.prevent="$refs.input.click()"
         @dragenter.prevent="depth++"
         @dragover.prevent
         @dragleave.prevent="depth--"
         @drop.prevent="onDrop($event)"
         class="fd-zone"
         x-bind:class="dragging ? 'fd-zone--drag' : (error ? 'fd-zone--error' : '')">

        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"
             stroke-linecap="round" stroke-linejoin="round" class="fd-icon" aria-hidden="true">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
            <polyline points="17 8 12 3 7 8"/>
            <line x1="12" y1="3" x2="12" y2="15"/>
        </svg>

        <div class="fd-primary" x-text="dragging ? '{{ __('Drop to attach') }}' : '{{ __('Drag a file here') }}'"></div>
        <div class="fd-secondary" x-show="!dragging">
            {{ __('or') }} <span class="fd-link">{{ __('browse your files') }}</span>
        </div>
    </div>

    {{-- ============ SELECTED ============ --}}
    <div x-show="hasFile" x-cloak class="fd-zone fd-zone--filled">
        <span class="fd-badge" x-text="ext.toUpperCase()"></span>

        <div style="flex:1; min-width:0;">
            <div class="fd-name" x-text="shortName()" x-bind:title="fileName"></div>
            <div class="fd-meta">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"
                     stroke-linecap="round" stroke-linejoin="round" style="width:11px;height:11px;color:#16a34a;" aria-hidden="true">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
                <span x-text="human(fileSize)"></span>
                <span>&middot;</span>
                <span>{{ __('ready to upload') }}</span>
            </div>
        </div>

        <div style="display:flex; gap:6px; flex-shrink:0;">
            <button type="button" @click="$refs.input.click()" class="fd-btn">{{ __('Replace') }}</button>
            <button type="button" @click="clear()" class="fd-btn fd-btn--danger">{{ __('Remove') }}</button>
        </div>
    </div>

    {{-- The input the form actually submits. Visually hidden, never disabled. --}}
    <input id="{{ $name }}" name="{{ $name }}" type="file" x-ref="input"
           accept="{{ $acceptAttr }}"
           @if($requiredWhen) x-bind:required="{{ $requiredWhen }}" @elseif($required) required @endif
           @change="pick($event.target.files)"
           style="position:absolute; width:1px; height:1px; opacity:0; overflow:hidden; clip:rect(0 0 0 0); white-space:nowrap;">

    {{-- aria-live so a screen reader announces a rejected file instead of it only
         appearing visually. --}}
    <p x-show="error && error !== 'server'" x-cloak x-text="error" aria-live="polite"
       style="display:flex; align-items:center; gap:5px; font-size:12.5px; color:#dc2626; margin:7px 0 0;"></p>

    @error($name)
        <p aria-live="polite" style="font-size:12.5px; color:#dc2626; margin:7px 0 0;">{{ $message }}</p>
    @enderror

    @if($hint)
        <p id="{{ $name }}-hint" x-show="!error" style="font-size:12px; color:var(--muted); margin:7px 0 0;">{{ $hint }}</p>
    @endif
</div>
