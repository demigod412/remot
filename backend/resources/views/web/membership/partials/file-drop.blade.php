{{--
    Styled drag-and-drop file field for the membership application form.

    Usage:
        @include('web.membership.partials.file-drop', [
            'name'     => 'resume',
            'label'    => __('CV / Resume'),
            'required' => true,
            'hint'     => __('Your work history and skills.'),
        ])

    The real <input type="file"> is kept in the DOM and is what the form actually
    posts, so this degrades to a normal file picker if JavaScript is off. Dropped
    files are assigned onto that input's .files so a plain multipart POST carries
    them without any AJAX.

    Client-side type and size checks are a courtesy to the applicant, not a
    control. MembershipApplicationRequest re-validates both server-side.
--}}
@php
    $required   = $required ?? false;
    $hint       = $hint ?? null;
    $accept     = config('jobstation.membership.allowed_doc_types', ['pdf', 'doc', 'docx']);
    $maxKb      = (int) config('jobstation.membership.max_doc_size_kb', 5120);
    $acceptAttr = '.' . implode(',.', $accept);

    // Rendered into a double-quoted x-data attribute, so this must not contain
    // double quotes. json_encode() would, hence building the JS literal by hand.
    $allowedJs  = "['" . implode("','", $accept) . "']";
    $maxLabel   = $maxKb >= 1024 ? round($maxKb / 1024, 1) . ' MB' : $maxKb . ' KB';
@endphp

<div style="margin-bottom:18px;"
     x-data="{
        fileName: '',
        fileSize: 0,
        error: '',
        dragging: false,
        maxKb: {{ $maxKb }},
        allowed: {!! $allowedJs !!},
        human(bytes) {
            if (bytes < 1024) { return bytes + ' B'; }
            if (bytes < 1048576) { return Math.round(bytes / 1024) + ' KB'; }
            return (bytes / 1048576).toFixed(1) + ' MB';
        },
        reset() {
            this.fileName = '';
            this.fileSize = 0;
        },
        pick(files) {
            this.error = '';
            this.reset();
            if (! files || ! files.length) { return; }
            const f = files[0];
            const ext = (f.name.split('.').pop() || '').toLowerCase();
            if (! this.allowed.includes(ext)) {
                this.error = 'That is a .' + ext + ' file. Please use ' + this.allowed.join(', ').toUpperCase() + '.';
                this.$refs.input.value = '';
                return;
            }
            if ((f.size / 1024) > this.maxKb) {
                this.error = 'That file is ' + this.human(f.size) + '. The limit is {{ $maxLabel }}.';
                this.$refs.input.value = '';
                return;
            }
            this.fileName = f.name;
            this.fileSize = f.size;
        },
        onDrop(e) {
            this.dragging = false;
            const dt = e.dataTransfer;
            if (! dt || ! dt.files || ! dt.files.length) { return; }
            this.$refs.input.files = dt.files;
            this.pick(dt.files);
        },
        clear() {
            this.$refs.input.value = '';
            this.error = '';
            this.reset();
        }
     }">

    <label for="{{ $name }}" style="display:block; font-size:13px; font-weight:500; margin-bottom:6px; color:var(--text);">
        {{ $label }}
        @if($required)<span style="color:#dc2626;">*</span>@endif
    </label>

    {{-- Drop zone. Clicking anywhere in it opens the native picker. --}}
    <div role="button" tabindex="0"
         @click="$refs.input.click()"
         @keydown.enter.prevent="$refs.input.click()"
         @keydown.space.prevent="$refs.input.click()"
         @dragover.prevent="dragging = true"
         @dragleave.prevent="dragging = false"
         @drop.prevent="onDrop($event)"
         x-bind:style="dragging
            ? 'border-color:var(--accent); background:rgba(99,102,241,0.06);'
            : (error ? 'border-color:#fca5a5; background:#fef2f2;' : '')"
         style="border:1.5px dashed {{ $errors->has($name) ? '#fca5a5' : 'var(--border)' }}; border-radius:10px; padding:18px; text-align:center; cursor:pointer; background:{{ $errors->has($name) ? '#fef2f2' : 'transparent' }}; transition:border-color .15s, background .15s;">

        {{-- Empty state --}}
        <template x-if="! fileName">
            <div>
                <div style="font-size:22px; line-height:1; margin-bottom:8px; opacity:.5;">&#8679;</div>
                <div style="font-size:13.5px; color:var(--text); font-weight:500;">
                    {{ __('Drop your file here, or click to browse') }}
                </div>
                <div style="font-size:12px; color:var(--muted); margin-top:5px;">
                    {{ strtoupper(implode(', ', $accept)) }} &middot; {{ __('max') }} {{ $maxLabel }}
                </div>
            </div>
        </template>

        {{-- Selected state --}}
        <template x-if="fileName">
            <div style="display:flex; align-items:center; gap:10px; text-align:left;">
                <div style="flex-shrink:0; width:34px; height:34px; border-radius:7px; background:rgba(34,197,94,0.12); color:#16a34a; display:flex; align-items:center; justify-content:center; font-size:15px;">&#10003;</div>
                <div style="flex:1; min-width:0;">
                    <div x-text="fileName" style="font-size:13.5px; font-weight:500; color:var(--text); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"></div>
                    <div x-text="human(fileSize)" style="font-size:12px; color:var(--muted); margin-top:2px;"></div>
                </div>
                <button type="button" @click.stop="clear()"
                        style="flex-shrink:0; border:1px solid var(--border); background:transparent; color:var(--muted); border-radius:6px; padding:5px 10px; font-size:12px; cursor:pointer;">
                    {{ __('Remove') }}
                </button>
            </div>
        </template>
    </div>

    {{-- The input the form actually submits. Visually hidden, not disabled. --}}
    <input id="{{ $name }}" name="{{ $name }}" type="file" x-ref="input"
           accept="{{ $acceptAttr }}"
           @change="pick($event.target.files)"
           style="position:absolute; width:1px; height:1px; opacity:0; overflow:hidden; clip:rect(0 0 0 0); white-space:nowrap;">

    {{-- Client-side feedback, shown before submit. --}}
    <p x-show="error" x-cloak x-text="error"
       style="font-size:12.5px; color:#dc2626; margin:6px 0 0;"></p>

    {{-- Server-side error for this field. --}}
    @error($name)
        <p style="font-size:12.5px; color:#dc2626; margin:6px 0 0;">{{ $message }}</p>
    @enderror

    @if($hint)
        <p x-show="! error" style="font-size:12px; color:var(--muted); margin:6px 0 0;">{{ $hint }}</p>
    @endif
</div>
