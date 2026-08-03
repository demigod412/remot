@extends('admin.layouts.app')
@section('title', 'Edit Work')
@section('page-title', 'Edit Work')

@section('content')

<div style="display:flex;align-items:center;gap:6px;font-size:12.5px;color:var(--fg-3);margin-bottom:20px;">
    <a href="{{ route('admin.works.index') }}" style="color:var(--fg-3);text-decoration:none;transition:.12s;"
       onmouseover="this.style.color='var(--fg)'" onmouseout="this.style.color='var(--fg-3)'">Works</a>
    <i data-lucide="chevron-right" style="width:13px;height:13px;color:var(--fg-4);"></i>
    <a href="{{ route('admin.works.show', $work->id) }}" style="color:var(--fg-3);text-decoration:none;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:180px;transition:.12s;"
       onmouseover="this.style.color='var(--fg)'" onmouseout="this.style.color='var(--fg-3)'">{{ $work->title }}</a>
    <i data-lucide="chevron-right" style="width:13px;height:13px;color:var(--fg-4);"></i>
    <span style="color:var(--fg-2);">Edit</span>
</div>

<div style="max-width:820px;">
<form method="POST" action="{{ route('admin.works.update', $work->id) }}" enctype="multipart/form-data">
@csrf @method('PUT')

<div style="display:flex;flex-direction:column;gap:16px;">

    {{-- Basic Info --}}
    <div class="jobstation-card" style="padding:24px;">
        <h3 style="font-size:14px;font-weight:600;color:var(--fg);margin:0 0 18px;">Basic Information</h3>
        <div style="display:flex;flex-direction:column;gap:16px;">
            <div>
                <label style="display:block;font-size:12px;color:var(--fg-2);margin-bottom:6px;font-weight:500;">Title <span style="color:#EF4444;">*</span></label>
                <input type="text" name="title" value="{{ old('title', $work->title) }}"
                       @error('title') style="border-color:#EF4444;" @enderror required>
                @error('title') <div style="font-size:12px;color:#EF4444;margin-top:4px;">{{ $message }}</div> @enderror
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;" class="work-form-2col"
                 x-data="{ catId: '{{ old('category_id', $work->category_id) }}' }">
                <div>
                    <label style="display:block;font-size:12px;color:var(--fg-2);margin-bottom:6px;font-weight:500;">Category <span style="color:#EF4444;">*</span></label>
                    <select name="category_id" x-model="catId"
                            @change="fetch('/admin/categories/'+catId+'/subcategories').then(r=>r.json()).then(d=>{ $refs.subcat.innerHTML = '<option value=\'\'>None</option>' + d.map(s=>`<option value='${s.id}'>${s.name}</option>`).join('') })"
                            required>
                        <option value="">Select…</option>
                        @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" @selected(old('category_id',$work->category_id)==$cat->id)>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="display:block;font-size:12px;color:var(--fg-2);margin-bottom:6px;font-weight:500;">Subcategory</label>
                    <select name="subcategory_id" x-ref="subcat">
                        <option value="">None</option>
                        @foreach($subcategories as $sub)
                        <option value="{{ $sub->id }}" @selected(old('subcategory_id',$work->subcategory_id)==$sub->id)>{{ $sub->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label style="display:block;font-size:12px;color:var(--fg-2);margin-bottom:6px;font-weight:500;">Description <span style="color:#EF4444;">*</span></label>
                <textarea name="description" rows="7"
                          style="resize:vertical;width:100%;font-size:13.5px;line-height:1.6;"
                          @error('description') style="border-color:#EF4444;" @enderror
                          required>{{ old('description', $work->description) }}</textarea>
                @error('description') <div style="font-size:12px;color:#EF4444;margin-top:4px;">{{ $message }}</div> @enderror
            </div>

            <div>
                <label style="display:block;font-size:12px;color:var(--fg-2);margin-bottom:6px;font-weight:500;">Cover Image</label>
                @if($work->cover_image)
                <div style="margin-bottom:8px;">
                    <img src="{{ fileUrl(config('jobstation.upload_paths.work_cover'), $work->cover_image) }}"
                         style="height:100px;border-radius:10px;object-fit:cover;border:1px solid var(--border);" alt="">
                    <div style="font-size:11.5px;color:var(--fg-4);margin-top:4px;">Current cover — upload a new one to replace.</div>
                </div>
                @endif
                <input type="file" name="cover_image" accept="image/*" style="font-size:13px;">
            </div>
        </div>
    </div>

    {{-- Reward & Slots --}}
    <div class="jobstation-card" style="padding:24px;">
        <h3 style="font-size:14px;font-weight:600;color:var(--fg);margin:0 0 18px;">Reward & Capacity</h3>
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;" class="work-form-3col">
            <div>
                <label style="display:block;font-size:12px;color:var(--fg-2);margin-bottom:6px;font-weight:500;">Worker Slots</label>
                <input type="number" name="worker_slots" value="{{ old('worker_slots', $work->worker_slots) }}" min="1" required>
            </div>
            <div>
                <label style="display:block;font-size:12px;color:var(--fg-2);margin-bottom:6px;font-weight:500;">Coins / Worker</label>
                <input type="number" name="coins_per_worker" value="{{ old('coins_per_worker', $work->coins_per_worker) }}"
                       min="0.01" step="0.01" style="font-family:ui-monospace,monospace;" required>
            </div>
            <div>
                <label style="display:block;font-size:12px;color:var(--fg-2);margin-bottom:6px;font-weight:500;">Est. Minutes</label>
                <input type="number" name="avg_minutes" value="{{ old('avg_minutes', $work->avg_minutes) }}" min="1">
            </div>
        </div>

        <div style="margin-top:14px;">
                <div>
                    <label style="display:block;font-size:12px;color:var(--fg-2);margin-bottom:6px;font-weight:500;">
                        Worker Payout (USD) <span style="color:#EF4444;">*</span>
                    </label>
                    <input type="number" name="payout_usd" value="{{ old('payout_usd', $work->payout_usd) }}"
                           min="0" step="0.01" placeholder="0.00"
                           style="font-family:ui-monospace,monospace;"
                           @error('payout_usd') style="border-color:#EF4444;" @enderror required>
                    <small style="display:block;color:var(--fg-3);font-size:11px;line-height:1.6;margin-top:4px;">
                        Paid to each worker in USD when you approve their work, minus the
                        category commission. Separate from the JC coin figure: coins are only
                        spent on application fees and never convert to USD.
                    </small>
                    @error('payout_usd') <div style="font-size:12px;color:#EF4444;margin-top:4px;">{{ $message }}</div> @enderror
                </div>
        </div>

        {{-- Display-only applicant seed. Never enters slot arithmetic. --}}
        @include('admin.partials.work-display-boost-field', ['work' => $work])
    </div>

    {{-- Status --}}
    <div class="jobstation-card" style="padding:24px;">
        <h3 style="font-size:14px;font-weight:600;color:var(--fg);margin:0 0 18px;">Status</h3>
        <div style="display:flex;flex-direction:column;gap:16px;">
            <div>
                <label style="display:block;font-size:12px;color:var(--fg-2);margin-bottom:6px;font-weight:500;">Work Status</label>
                <select name="work_status" style="width:240px;">
                    <option value="1" @selected(old('work_status',$work->work_status)==1)>Active</option>
                    <option value="0" @selected(old('work_status',$work->work_status)==0)>Holding</option>
                    <option value="2" @selected(old('work_status',$work->work_status)==2)>Finished</option>
                </select>
            </div>
            <label style="display:flex;align-items:flex-start;gap:10px;cursor:pointer;">
                <input type="hidden" name="allow_multiple_submissions" value="0">
                <input type="checkbox" id="allow_multiple_edit" name="allow_multiple_submissions" value="1"
                       style="width:15px;height:15px;accent-color:var(--accent);margin-top:2px;flex-shrink:0;"
                       @checked(old('allow_multiple_submissions', $work->allow_multiple_submissions))>
                <div>
                    <div style="font-size:13px;color:var(--fg);font-weight:500;">Allow users to do this work more than once</div>
                    <div style="font-size:11.5px;color:var(--fg-3);margin-top:2px;">Workers can re-apply after each approved submission to earn more coins.</div>
                </div>
            </label>
            <label style="display:flex;align-items:flex-start;gap:10px;cursor:pointer;">
                <input type="hidden" name="requires_kyc" value="0">
                <input type="checkbox" id="requires_kyc_edit" name="requires_kyc" value="1"
                       style="width:15px;height:15px;accent-color:#F59E0B;margin-top:2px;flex-shrink:0;"
                       @checked(old('requires_kyc', $work->requires_kyc))>
                <div>
                    <div style="font-size:13px;color:var(--fg);font-weight:500;">Requires KYC verification</div>
                    <div style="font-size:11.5px;color:var(--fg-3);margin-top:2px;">Only users who have completed identity verification can apply.</div>
                </div>
            </label>
        </div>
    </div>

    <div style="display:flex;gap:10px;">
        <button type="submit" class="btn btn-primary" style="padding:9px 22px;">
            <i data-lucide="save" style="width:14px;height:14px;"></i> Save Changes
        </button>
        <a href="{{ route('admin.works.show', $work->id) }}" class="btn" style="padding:9px 18px;">Cancel</a>
    </div>

</div>
</form>
</div>

<style>
@media (max-width: 640px) {
    .work-form-2col, .work-form-3col { grid-template-columns: 1fr !important; }
}
</style>

@endsection
