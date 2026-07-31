@extends('admin.layouts.app')
@section('title', 'Edit Page — ' . $page->name)
@section('page-title', 'Pages')

@section('content')

{{-- Breadcrumb --}}
<div style="display:flex;align-items:center;gap:6px;font-size:12.5px;color:var(--fg-3);margin-bottom:20px;">
    <a href="{{ route('admin.pages.index') }}" style="color:var(--fg-3);text-decoration:none;transition:.12s;"
       onmouseover="this.style.color='var(--fg)'" onmouseout="this.style.color='var(--fg-3)'">Pages</a>
    <i data-lucide="chevron-right" style="width:13px;height:13px;color:var(--fg-4);"></i>
    <span style="color:var(--fg-2);">{{ $page->name }}</span>
</div>

@if(session('success'))
<div style="padding:10px 14px;background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.25);border-radius:8px;font-size:13px;color:#22C55E;margin-bottom:16px;display:flex;align-items:center;gap:8px;">
    <i data-lucide="check-circle" style="width:14px;height:14px;flex-shrink:0;"></i>
    {{ session('success') }}
</div>
@endif
@if($errors->any())
<div style="padding:10px 14px;background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.25);border-radius:8px;font-size:13px;color:#EF4444;margin-bottom:16px;">
    {{ $errors->first() }}
</div>
@endif

<div style="display:grid;grid-template-columns:1fr 280px;gap:20px;align-items:start;max-width:1100px;" class="page-edit-grid">

    {{-- ── Editor ── --}}
    <div>
    <form method="POST" action="{{ route('admin.pages.update', $page->id) }}" id="page-form">
    @csrf @method('PUT')

    {{-- Page name --}}
    <div class="jobstation-card" style="padding:20px;margin-bottom:16px;">
        <label style="display:block;font-size:11.5px;color:var(--fg-3);font-weight:500;text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px;">Page Title</label>
        <input type="text" name="name" value="{{ old('name', $page->name) }}" required
               style="font-size:16px;font-weight:600;width:100%;padding:10px 14px;">
        <div style="font-size:11.5px;color:var(--fg-4);margin-top:6px;">
            Public URL: <span style="font-family:ui-monospace,monospace;color:var(--fg-3);">/{{ $page->slug }}</span>
        </div>
    </div>

    {{-- Sections editor --}}
    @php
        $secs = old('secs') ? (is_array(old('secs')) ? old('secs') : []) : ($page->secs ?? [['heading' => '', 'content' => '']]);
        if (empty($secs)) $secs = [['heading' => '', 'content' => '']];
    @endphp

    <div id="sections-wrap">
        @foreach($secs as $i => $sec)
        <div class="section-block jobstation-card" style="padding:20px;margin-bottom:12px;" data-idx="{{ $i }}">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;">
                <div style="width:22px;height:22px;border-radius:6px;background:var(--surface-2);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <span style="font-size:11px;font-weight:700;color:var(--fg-3);">{{ $i + 1 }}</span>
                </div>
                <span style="font-size:12px;font-weight:600;color:var(--fg-2);flex:1;">Section {{ $i + 1 }}</span>
                <button type="button" onclick="removeSection(this)"
                        style="width:26px;height:26px;border-radius:6px;background:rgba(239,68,68,0.06);border:1px solid rgba(239,68,68,0.18);color:#EF4444;cursor:pointer;display:flex;align-items:center;justify-content:center;"
                        title="Remove section">
                    <svg width="11" height="11" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="10" y1="2" x2="2" y2="10"/><line x1="2" y1="2" x2="10" y2="10"/></svg>
                </button>
            </div>

            <div style="margin-bottom:12px;">
                <label style="display:block;font-size:11.5px;color:var(--fg-3);margin-bottom:5px;">Section heading <span style="color:var(--fg-4);">(optional)</span></label>
                <input type="text" name="secs[{{ $i }}][heading]" value="{{ old('secs.' . $i . '.heading', $sec['heading'] ?? '') }}"
                       placeholder="e.g. Introduction" style="font-size:13px;font-weight:500;">
            </div>

            <div>
                <label style="display:block;font-size:11.5px;color:var(--fg-3);margin-bottom:5px;">Content <span style="color:#EF4444;">*</span></label>
                <textarea name="secs[{{ $i }}][content]" rows="18"
                          style="font-size:13px;line-height:1.65;resize:vertical;width:100%;padding:12px 14px;font-family:'Poppins',sans-serif;"
                          placeholder="HTML content for this section…">{{ old('secs.' . $i . '.content', $sec['content'] ?? '') }}</textarea>
                <div style="font-size:11px;color:var(--fg-4);margin-top:4px;">Supports HTML: &lt;h2&gt;, &lt;p&gt;, &lt;ul&gt;, &lt;ol&gt;, &lt;strong&gt;, &lt;a&gt;, &lt;blockquote&gt;, etc.</div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Add section --}}
    <button type="button" id="add-section"
            style="width:100%;padding:11px;border-radius:10px;border:1.5px dashed var(--border);background:transparent;color:var(--fg-3);font-size:13px;font-weight:500;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:7px;transition:all .14s;margin-bottom:20px;"
            onmouseover="this.style.borderColor='var(--accent)';this.style.color='var(--accent)';this.style.background='var(--accent-soft)'"
            onmouseout="this.style.borderColor='var(--border)';this.style.color='var(--fg-3)';this.style.background='transparent'">
        <svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="7" y1="1" x2="7" y2="13"/><line x1="1" y1="7" x2="13" y2="7"/></svg>
        Add section
    </button>

    <div style="display:flex;gap:10px;">
        <button type="submit" class="btn btn-primary" style="padding:10px 24px;">
            <i data-lucide="save" style="width:14px;height:14px;"></i> Save page
        </button>
        <a href="{{ route('admin.pages.index') }}" class="btn" style="padding:10px 18px;text-decoration:none;">Cancel</a>
    </div>

    </form>
    </div>

    {{-- ── Sidebar info ── --}}
    <div style="position:sticky;top:76px;">
        <div class="jobstation-card" style="padding:18px;margin-bottom:14px;">
            <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--fg-3);margin-bottom:14px;">Page info</div>
            <div style="display:flex;flex-direction:column;gap:10px;">
                <div>
                    <div style="font-size:11px;color:var(--fg-4);margin-bottom:2px;">Slug</div>
                    <div style="font-family:ui-monospace,monospace;font-size:12.5px;color:var(--fg-2);">/{{ $page->slug }}</div>
                </div>
                <div>
                    <div style="font-size:11px;color:var(--fg-4);margin-bottom:2px;">Last updated</div>
                    <div style="font-size:12.5px;color:var(--fg-2);">{{ $page->updated_at?->format('M j, Y') }}</div>
                </div>
                <div>
                    <div style="font-size:11px;color:var(--fg-4);margin-bottom:2px;">Sections</div>
                    <div id="section-count" style="font-size:12.5px;color:var(--fg-2);">{{ count($page->secs ?? []) }}</div>
                </div>
            </div>
        </div>

        <div class="jobstation-card" style="padding:18px;">
            <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--fg-3);margin-bottom:12px;">Actions</div>
            <div style="display:flex;flex-direction:column;gap:8px;">
                <a href="{{ route('pages.show', $page->slug) }}" target="_blank"
                   style="display:flex;align-items:center;gap:8px;padding:8px 12px;border-radius:8px;font-size:13px;color:var(--fg-2);text-decoration:none;background:var(--surface-2);transition:.14s;"
                   onmouseover="this.style.color='var(--fg)'" onmouseout="this.style.color='var(--fg-2)'">
                    <i data-lucide="external-link" style="width:13px;height:13px;flex-shrink:0;"></i>
                    Preview live page
                </a>
                <button type="submit" form="page-form"
                        style="display:flex;align-items:center;gap:8px;padding:8px 12px;border-radius:8px;font-size:13px;color:white;background:var(--accent);border:none;cursor:pointer;width:100%;justify-content:center;font-family:inherit;transition:.14s;"
                        onmouseover="this.style.background='#2442c4'" onmouseout="this.style.background='var(--accent)'">
                    <i data-lucide="save" style="width:13px;height:13px;"></i>
                    Save changes
                </button>
            </div>
        </div>
    </div>

</div>

<style>
@media(max-width:900px) { .page-edit-grid { grid-template-columns:1fr !important; } }
.section-block { transition: box-shadow .14s; }
</style>

<script>
var sectionCount = {{ count($secs) }};

function updateSectionNumbers() {
    var blocks = document.querySelectorAll('.section-block');
    blocks.forEach(function(b, i) {
        b.querySelector('[data-idx]') && (b.dataset.idx = i);
        var numEl = b.querySelector('.sec-num');
        if (numEl) numEl.textContent = i + 1;
        var labelEl = b.querySelector('.sec-label');
        if (labelEl) labelEl.textContent = 'Section ' + (i + 1);
        // Re-index names
        b.querySelectorAll('[name]').forEach(function(el) {
            el.name = el.name.replace(/secs\[\d+\]/, 'secs[' + i + ']');
        });
    });
    var cnt = document.getElementById('section-count');
    if (cnt) cnt.textContent = blocks.length;
}

document.getElementById('add-section').addEventListener('click', function() {
    var idx = sectionCount++;
    var wrap = document.getElementById('sections-wrap');
    var div  = document.createElement('div');
    div.className = 'section-block jobstation-card';
    div.style.cssText = 'padding:20px;margin-bottom:12px;';
    div.dataset.idx = idx;
    div.innerHTML = `
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;">
            <div style="width:22px;height:22px;border-radius:6px;background:var(--surface-2);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <span class="sec-num" style="font-size:11px;font-weight:700;color:var(--fg-3);">${idx + 1}</span>
            </div>
            <span class="sec-label" style="font-size:12px;font-weight:600;color:var(--fg-2);flex:1;">Section ${idx + 1}</span>
            <button type="button" onclick="removeSection(this)"
                    style="width:26px;height:26px;border-radius:6px;background:rgba(239,68,68,0.06);border:1px solid rgba(239,68,68,0.18);color:#EF4444;cursor:pointer;display:flex;align-items:center;justify-content:center;">
                <svg width="11" height="11" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="10" y1="2" x2="2" y2="10"/><line x1="2" y1="2" x2="10" y2="10"/></svg>
            </button>
        </div>
        <div style="margin-bottom:12px;">
            <label style="display:block;font-size:11.5px;color:var(--fg-3);margin-bottom:5px;">Section heading <span style="color:var(--fg-4);">(optional)</span></label>
            <input type="text" name="secs[${idx}][heading]" placeholder="e.g. Introduction" style="font-size:13px;font-weight:500;">
        </div>
        <div>
            <label style="display:block;font-size:11.5px;color:var(--fg-3);margin-bottom:5px;">Content <span style="color:#EF4444;">*</span></label>
            <textarea name="secs[${idx}][content]" rows="18"
                      style="font-size:13px;line-height:1.65;resize:vertical;width:100%;padding:12px 14px;font-family:'Poppins',sans-serif;"
                      placeholder="HTML content for this section…"></textarea>
            <div style="font-size:11px;color:var(--fg-4);margin-top:4px;">Supports HTML: &lt;h2&gt;, &lt;p&gt;, &lt;ul&gt;, &lt;ol&gt;, &lt;strong&gt;, &lt;a&gt;, &lt;blockquote&gt;, etc.</div>
        </div>`;
    wrap.appendChild(div);
    updateSectionNumbers();
    div.querySelector('textarea').focus();
});

function removeSection(btn) {
    var blocks = document.querySelectorAll('.section-block');
    if (blocks.length <= 1) {
        alert('A page needs at least one section.');
        return;
    }
    btn.closest('.section-block').remove();
    updateSectionNumbers();
}

// Initial section-count label sync
updateSectionNumbers();
</script>

@endsection
