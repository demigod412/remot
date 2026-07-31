@extends('user.layouts.app')
@section('title', 'Edit Job Listing')
@section('page-title', 'Edit Job Listing')

@section('content')

{{-- Breadcrumb --}}
<div style="font-size:12.5px; color:var(--fg-3); margin-bottom:24px; display:flex; gap:6px; align-items:center;">
    <a href="{{ route('user.jobs.listings.index') }}" style="color:var(--fg-3); text-decoration:none; transition:color .15s;" onmouseover="this.style.color='var(--fg)'" onmouseout="this.style.color='var(--fg-3)'">My Listings</a>
    <svg width="11" height="11" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M7 4l5 5-5 5"/></svg>
    <a href="{{ route('user.jobs.listings.show', $listing->id) }}" style="color:var(--fg-3); text-decoration:none; transition:color .15s; max-width:180px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" onmouseover="this.style.color='var(--fg)'" onmouseout="this.style.color='var(--fg-3)'">{{ $listing->title }}</a>
    <svg width="11" height="11" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M7 4l5 5-5 5"/></svg>
    <span style="color:var(--fg-2);">Edit</span>
</div>

<div x-data="{
    salaryVisible: {{ $listing->salary_visible ? 'true' : 'false' }},
    locationType: '{{ old('location_type', $listing->location_type) }}',
    coverPreview: null,
    loadPreview(e) {
        const file = e.target.files[0];
        if (file) this.coverPreview = URL.createObjectURL(file);
    }
}">

<form method="POST" action="{{ route('user.jobs.listings.update', $listing->id) }}"
      enctype="multipart/form-data">
@csrf @method('PUT')

<div style="display:grid; grid-template-columns:1fr 300px; gap:24px; align-items:start;" class="edit-job-grid">

    {{-- ── LEFT: Form Sections ──────────────────────────────── --}}
    <div style="display:flex; flex-direction:column; gap:20px;">

        @if($errors->any())
        <div style="background:rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.25); border-radius:10px; padding:14px 18px;">
            <div style="display:flex; align-items:center; gap:8px; font-size:13px; font-weight:600; color:#EF4444; margin-bottom:8px;">
                <svg width="14" height="14" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="9" cy="9" r="7"/><path d="M9 5v4M9 13h.01"/></svg>
                Please fix the following errors
            </div>
            <ul style="margin:0; padding-left:18px; font-size:12.5px; color:#EF4444; display:flex; flex-direction:column; gap:3px;">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
        @endif

        {{-- ── Job Details ─────────────────────────────────── --}}
        <div class="card" style="padding:24px;">
            <div style="font-size:13px; font-weight:600; color:var(--fg); margin-bottom:20px; display:flex; align-items:center; gap:8px;">
                <div style="width:28px; height:28px; border-radius:8px; background:rgba(96,165,250,0.12); color:#60A5FA; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                    <svg width="14" height="14" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="2" y="5" width="14" height="10" rx="2"/><path d="M6 5V4a2 2 0 014 0v1"/></svg>
                </div>
                Job Details
            </div>

            <div style="margin-bottom:18px;">
                <label style="display:block; font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.07em; color:var(--fg-3); margin-bottom:7px;">
                    Job Title <span style="color:#EF4444;">*</span>
                </label>
                <input type="text" name="title" value="{{ old('title', $listing->title) }}"
                       placeholder="e.g. Senior Frontend Developer"
                       style="width:100%; box-sizing:border-box; @error('title') border-color:rgba(239,68,68,0.5) !important; @enderror">
                @error('title')<p style="color:#EF4444; font-size:12px; margin:5px 0 0;">{{ $message }}</p>@enderror
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:18px;" class="form-2col">
                <div>
                    <label style="display:block; font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.07em; color:var(--fg-3); margin-bottom:7px;">
                        Category <span style="color:#EF4444;">*</span>
                    </label>
                    <select name="category_id" style="width:100%; @error('category_id') border-color:rgba(239,68,68,0.5) !important; @enderror">
                        @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ old('category_id', $listing->category_id) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                    @error('category_id')<p style="color:#EF4444; font-size:12px; margin:5px 0 0;">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label style="display:block; font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.07em; color:var(--fg-3); margin-bottom:7px;">
                        Employment Type <span style="color:#EF4444;">*</span>
                    </label>
                    <select name="employment_type" style="width:100%; @error('employment_type') border-color:rgba(239,68,68,0.5) !important; @enderror">
                        @foreach(['full_time'=>'Full-time','part_time'=>'Part-time','contract'=>'Contract','freelance'=>'Freelance'] as $val => $label)
                        <option value="{{ $val }}" {{ old('employment_type', $listing->employment_type) == $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('employment_type')<p style="color:#EF4444; font-size:12px; margin:5px 0 0;">{{ $message }}</p>@enderror
                </div>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:18px;" class="form-2col">
                <div>
                    <label style="display:block; font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.07em; color:var(--fg-3); margin-bottom:7px;">
                        Location Type <span style="color:#EF4444;">*</span>
                    </label>
                    <select name="location_type" x-model="locationType"
                            style="width:100%; @error('location_type') border-color:rgba(239,68,68,0.5) !important; @enderror">
                        @foreach([1=>'Remote',2=>'On-site',3=>'Hybrid'] as $val => $label)
                        <option value="{{ $val }}" {{ old('location_type', $listing->location_type) == $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div x-show="locationType != '1'" x-transition>
                    <label style="display:block; font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.07em; color:var(--fg-3); margin-bottom:7px;">City / Location</label>
                    <input type="text" name="location" value="{{ old('location', $listing->location) }}"
                           placeholder="e.g. Dar es Salaam"
                           style="width:100%; box-sizing:border-box;">
                </div>
            </div>

            <div>
                <label style="display:block; font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.07em; color:var(--fg-3); margin-bottom:7px;">
                    Application Deadline
                    <span style="color:var(--fg-4); text-transform:none; letter-spacing:0; font-weight:400;">(optional)</span>
                </label>
                <input type="date" name="closes_at" value="{{ old('closes_at', $listing->closes_at?->format('Y-m-d')) }}"
                       min="{{ date('Y-m-d', strtotime('+1 day')) }}"
                       style="width:100%; box-sizing:border-box; @error('closes_at') border-color:rgba(239,68,68,0.5) !important; @enderror">
                @error('closes_at')<p style="color:#EF4444; font-size:12px; margin:5px 0 0;">{{ $message }}</p>@enderror
            </div>
        </div>

        {{-- ── Description & Requirements ───────────────────── --}}
        <div class="card" style="padding:24px;">
            <div style="font-size:13px; font-weight:600; color:var(--fg); margin-bottom:20px; display:flex; align-items:center; gap:8px;">
                <div style="width:28px; height:28px; border-radius:8px; background:rgba(47,84,235,0.12); color:var(--accent); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                    <svg width="14" height="14" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M3 4h12M3 8h10M3 12h8"/></svg>
                </div>
                Description & Requirements
            </div>

            <div style="margin-bottom:18px;">
                <label style="display:block; font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.07em; color:var(--fg-3); margin-bottom:7px;">
                    Job Description <span style="color:#EF4444;">*</span>
                </label>
                <textarea name="description" rows="7"
                          placeholder="Describe the role, responsibilities, and what you're looking for…"
                          style="width:100%; box-sizing:border-box; resize:vertical; line-height:1.6; @error('description') border-color:rgba(239,68,68,0.5) !important; @enderror">{{ old('description', $listing->description) }}</textarea>
                @error('description')<p style="color:#EF4444; font-size:12px; margin:5px 0 0;">{{ $message }}</p>@enderror
            </div>

            <div style="margin-bottom:18px;">
                <label style="display:block; font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.07em; color:var(--fg-3); margin-bottom:7px;">
                    Requirements
                    <span style="color:var(--fg-4); text-transform:none; letter-spacing:0; font-weight:400;">(optional)</span>
                </label>
                <textarea name="requirements" rows="5"
                          placeholder="List each requirement on a new line…"
                          style="width:100%; box-sizing:border-box; resize:vertical; line-height:1.6;">{{ old('requirements', $listing->requirements) }}</textarea>
                <div style="font-size:11px; color:var(--fg-4); margin-top:5px;">One requirement per line.</div>
            </div>

            <div>
                <label style="display:block; font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.07em; color:var(--fg-3); margin-bottom:7px;">
                    Benefits
                    <span style="color:var(--fg-4); text-transform:none; letter-spacing:0; font-weight:400;">(optional)</span>
                </label>
                <textarea name="benefits" rows="4"
                          placeholder="Perks, benefits, what makes this role great…"
                          style="width:100%; box-sizing:border-box; resize:vertical; line-height:1.6;">{{ old('benefits', $listing->benefits) }}</textarea>
            </div>
        </div>

        {{-- ── Salary ───────────────────────────────────────── --}}
        <div class="card" style="padding:24px;">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:20px;">
                <div style="font-size:13px; font-weight:600; color:var(--fg); display:flex; align-items:center; gap:8px;">
                    <div style="width:28px; height:28px; border-radius:8px; background:rgba(245,213,71,0.12); color:#ca8a04; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                        <svg width="14" height="14" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="9" cy="9" r="7"/><path d="M9 5v1.5M9 11.5V13M6.5 7.5A2.5 1.5 0 019 6a2.5 1.5 0 012.5 1.5A2.5 1.5 0 019 9a2.5 1.5 0 01-2.5 1.5"/></svg>
                    </div>
                    Salary
                </div>
                <label style="display:flex; align-items:center; gap:10px; cursor:pointer;">
                    <span style="font-size:12px; color:var(--fg-3);">Show to applicants</span>
                    <input type="hidden" name="salary_visible" value="0">
                    <div style="position:relative; width:36px; height:20px; flex-shrink:0;" @click="salaryVisible = !salaryVisible">
                        <input type="checkbox" name="salary_visible" value="1" x-model="salaryVisible"
                               style="position:absolute; opacity:0; width:100%; height:100%; cursor:pointer; z-index:1; margin:0;">
                        <div :style="salaryVisible ? 'background:var(--accent)' : 'background:var(--surface-3)'"
                             style="width:36px; height:20px; border-radius:10px; transition:.2s;"></div>
                        <div :style="salaryVisible ? 'left:18px' : 'left:2px'"
                             style="position:absolute; top:2px; width:16px; height:16px; border-radius:50%; background:white; transition:.2s; box-shadow:0 1px 3px rgba(0,0,0,0.25);"></div>
                    </div>
                </label>
            </div>
            <div style="display:grid; grid-template-columns:120px 1fr 1fr; gap:16px;">
                <div>
                    <label style="display:block; font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.07em; color:var(--fg-3); margin-bottom:7px;">Currency</label>
                    <input type="text" name="salary_currency" value="{{ old('salary_currency', $listing->salary_currency) }}"
                           placeholder="USD" style="width:100%; box-sizing:border-box; font-family:ui-monospace,monospace;">
                </div>
                <div>
                    <label style="display:block; font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.07em; color:var(--fg-3); margin-bottom:7px;">Min Salary</label>
                    <input type="number" name="salary_min" value="{{ old('salary_min', $listing->salary_min) }}" min="0" step="1"
                           placeholder="0" style="width:100%; box-sizing:border-box; font-family:ui-monospace,monospace;">
                </div>
                <div>
                    <label style="display:block; font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.07em; color:var(--fg-3); margin-bottom:7px;">Max Salary</label>
                    <input type="number" name="salary_max" value="{{ old('salary_max', $listing->salary_max) }}" min="0" step="1"
                           placeholder="0" style="width:100%; box-sizing:border-box; font-family:ui-monospace,monospace;">
                </div>
            </div>
            <div style="margin-top:12px; font-size:11.5px; color:var(--fg-4); display:flex; align-items:center; gap:6px;">
                <svg width="12" height="12" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="9" cy="9" r="7"/><path d="M9 8v4M9 6h.01"/></svg>
                Leave both fields empty if salary is negotiable.
            </div>
        </div>

        {{-- ── Cover Image ──────────────────────────────────── --}}
        <div class="card" style="padding:24px;">
            <div style="font-size:13px; font-weight:600; color:var(--fg); margin-bottom:20px; display:flex; align-items:center; gap:8px;">
                <div style="width:28px; height:28px; border-radius:8px; background:rgba(168,85,247,0.12); color:#a855f7; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                    <svg width="14" height="14" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="2" y="3" width="14" height="12" rx="2"/><circle cx="6.5" cy="7.5" r="1.5"/><path d="M2 13l4-4 3 3 2-2 5 5"/></svg>
                </div>
                Cover Image
                <span style="font-size:11px; font-weight:400; color:var(--fg-4);">(optional)</span>
            </div>

            @if($listing->cover_image && !request()->old('cover_image'))
            <img src="{{ fileUrl(config('jobstation.upload_paths.work_cover'), $listing->cover_image) }}"
                 style="width:100%; max-height:160px; object-fit:cover; border-radius:10px; margin-bottom:12px; border:1px solid var(--border);" alt="">
            @endif

            <div @click="$refs.coverInput.click()"
                 style="border:2px dashed var(--border-strong); border-radius:10px; padding:32px 24px; text-align:center; cursor:pointer; transition:.15s;"
                 onmouseover="this.style.borderColor='var(--accent)';this.style.background='rgba(47,84,235,0.04)'"
                 onmouseout="this.style.borderColor='var(--border-strong)';this.style.background='transparent'">
                <template x-if="coverPreview">
                    <img :src="coverPreview" style="width:100%; max-height:180px; object-fit:cover; border-radius:8px; margin-bottom:10px;">
                </template>
                <template x-if="!coverPreview">
                    <div>
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="var(--fg-4)" stroke-width="1.4" style="margin:0 auto 10px; display:block;">
                            <rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/>
                        </svg>
                        <div style="font-size:13.5px; font-weight:500; color:var(--fg-2); margin-bottom:4px;">Click to replace image</div>
                        <div style="font-size:11.5px; color:var(--fg-4);">JPG, PNG — max 2MB</div>
                    </div>
                </template>
                <input type="file" name="cover_image" accept="image/*" x-ref="coverInput"
                       style="display:none;" @change="loadPreview($event)">
            </div>
            @error('cover_image')<p style="color:#EF4444; font-size:12px; margin:8px 0 0;">{{ $message }}</p>@enderror
        </div>

    </div>{{-- end left --}}

    {{-- ── RIGHT SIDEBAR ───────────────────────────────────── --}}
    <aside style="position:sticky; top:80px; display:flex; flex-direction:column; gap:16px;">

        {{-- Status info --}}
        <div class="card" style="padding:18px;">
            <div style="font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.07em; color:var(--fg-3); margin-bottom:12px;">Current Status</div>
            @php
                $statusColors = ['pending'=>'#F59E0B','active'=>'var(--accent)','rejected'=>'#EF4444','closed'=>'var(--fg-3)','draft'=>'#60A5FA'];
                $sc = $statusColors[$listing->status] ?? 'var(--fg-3)';
            @endphp
            <div style="display:flex; align-items:center; gap:8px; font-size:13px; font-weight:500; color:{{ $sc }};">
                <div style="width:8px; height:8px; border-radius:50%; background:{{ $sc }};"></div>
                {{ ucfirst($listing->status) }}
            </div>
            @if($listing->status === 'rejected' && $listing->admin_note)
            <div style="margin-top:10px; font-size:12px; color:var(--fg-3); background:rgba(239,68,68,0.06); border:1px solid rgba(239,68,68,0.15); border-radius:8px; padding:10px;">
                <div style="font-size:10.5px; font-weight:600; color:#EF4444; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:4px;">Rejection note</div>
                {{ $listing->admin_note }}
            </div>
            @endif
        </div>

        {{-- Submit card --}}
        <div class="card" style="padding:20px;">
            <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center; padding:12px; font-size:14px; display:flex; gap:8px; align-items:center; margin-bottom:10px;">
                <svg width="15" height="15" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M3 9l4 4 8-8"/></svg>
                Save & Resubmit
            </button>
            <a href="{{ route('user.jobs.listings.show', $listing->id) }}"
               style="display:flex; justify-content:center; align-items:center; padding:10px; font-size:13px; color:var(--fg-3); text-decoration:none; border-radius:8px; transition:.15s;"
               onmouseover="this.style.background='var(--surface-2)';this.style.color='var(--fg)'"
               onmouseout="this.style.background='transparent';this.style.color='var(--fg-3)'">
                Cancel
            </a>
            <div style="margin-top:14px; padding-top:14px; border-top:1px solid var(--border); font-size:11.5px; color:var(--fg-4); line-height:1.6; text-align:center;">
                Saving will resubmit the listing for review. Usually approved within 24 hours.
            </div>
        </div>

    </aside>

</div>{{-- end grid --}}
</form>
</div>{{-- end x-data --}}

<style>
@media (max-width: 1024px) {
    .edit-job-grid { grid-template-columns: 1fr !important; }
    .edit-job-grid aside { position: static !important; }
}
@media (max-width: 560px) {
    .form-2col { grid-template-columns: 1fr !important; }
}
input:focus, textarea:focus, select:focus {
    outline: none;
    border-color: var(--accent) !important;
    box-shadow: 0 0 0 3px rgba(47,84,235,0.15);
}
</style>

@endsection
