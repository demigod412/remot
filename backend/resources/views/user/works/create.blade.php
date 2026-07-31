@extends('user.layouts.app')
@section('title', 'Post a Work')
@section('page-title', 'Post a Work')

@section('content')
<div style=""
     x-data="{
         step: 1,
         totalSteps: 4,

         title: '{{ old('title') }}',
         categoryId: '{{ old('category_id') }}',
         subcategoryId: '{{ old('subcategory_id') }}',
         subcategories: [],

         description: {{ json_encode(old('description', '')) }},
         avgMinutes: '{{ old('avg_minutes') }}',
         expiresAt: '{{ old('expires_at') }}',

         slots: {{ old('worker_slots', 1) }},
         coinsEach: {{ old('coins_per_worker', 0) }},
         autoApproveHours: '{{ old('auto_approve_hours') }}',
         get totalCoins() { return this.slots * this.coinsEach; },

         coverPreview: null,

         loadSubs(id) {
             this.subcategoryId = '';
             this.subcategories = [];
             if (!id) return;
             fetch('/subcategories?category_id=' + id)
                 .then(r => r.json())
                 .then(data => { this.subcategories = data; });
         },

         onCoverChange(e) {
             const file = e.target.files[0];
             if (!file) return;
             const reader = new FileReader();
             reader.onload = ev => { this.coverPreview = ev.target.result; };
             reader.readAsDataURL(file);
         },

         nextStep() {
             if (this.step === 1 && !this.title.trim()) { alert('Please enter a title.'); return; }
             if (this.step === 2 && this.description.length < 50) { alert('Description must be at least 50 characters.'); return; }
             if (this.step === 3 && (this.slots < 1 || this.coinsEach < 1)) { alert('Slots and coins per worker must be at least 1.'); return; }
             if (this.step < this.totalSteps) this.step++;
         },
         prevStep() { if (this.step > 1) this.step--; }
     }">

{{-- ── Stepper header ──────────────────────────────────────────────── --}}
<div style="margin-bottom:24px;">
    <div style="display:flex; align-items:center; gap:0;">
        @foreach(['Basic Info', 'Description', 'Budget', 'Review'] as $i => $label)
        @php $n = $i + 1; @endphp
        <div style="display:flex; align-items:center; {{ $n < 4 ? 'flex:1;' : '' }}">
            <div style="display:flex; flex-direction:column; align-items:center;">
                <div style="width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:13px; font-weight:700; flex-shrink:0; transition:all .3s;"
                     :style="step === {{ $n }}
                         ? 'background:var(--accent); color:white;'
                         : step > {{ $n }}
                             ? 'background:rgba(34,197,94,0.15); color:#22C55E;'
                             : 'background:var(--surface-2); color:var(--fg-4);'">
                    <template x-if="step > {{ $n }}">
                        <i data-lucide="check" style="width:13px; height:13px;"></i>
                    </template>
                    <template x-if="step <= {{ $n }}">
                        <span>{{ $n }}</span>
                    </template>
                </div>
                <span style="font-size:11px; margin-top:5px; transition:color .3s;"
                      :style="step === {{ $n }} ? 'color:var(--fg); font-weight:500;' : 'color:var(--fg-4);'">
                    {{ $label }}
                </span>
            </div>
            @if($n < 4)
            <div style="flex:1; height:1px; margin:0 8px; margin-bottom:16px; transition:background .3s;"
                 :style="step > {{ $n }} ? 'background:rgba(34,197,94,0.4);' : 'background:var(--border-strong);'"></div>
            @endif
        </div>
        @endforeach
    </div>
</div>

<form method="POST" action="{{ route('user.works.store') }}" enctype="multipart/form-data" id="work-form">
@csrf

{{-- ── Step 1: Basic Info ──────────────────────────────────────────── --}}
<div x-show="step === 1"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0 translate-x-4"
     x-transition:enter-end="opacity-100 translate-x-0">
    <div class="card" style="padding:22px; display:flex; flex-direction:column; gap:18px;">
        <div style="font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.07em; color:var(--fg-3);">Step 1 — Basic Info</div>

        <div>
            <div style="font-size:12px; font-weight:500; color:var(--fg-2); margin-bottom:6px;">
                Title <span style="color:var(--danger);">*</span>
            </div>
            <input type="text" name="title" x-model="title"
                   style="{{ $errors->has('title') ? 'border-color:var(--danger);' : '' }}"
                   placeholder="e.g. Write a 500-word product review">
            <div style="display:flex; justify-content:space-between; margin-top:4px;">
                @error('title')
                    <div style="font-size:12px; color:var(--danger);">{{ $message }}</div>
                @else
                    <div></div>
                @enderror
                <span style="font-size:11px; color:var(--fg-4);" x-text="title.length + ' chars'"></span>
            </div>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
            <div>
                <div style="font-size:12px; font-weight:500; color:var(--fg-2); margin-bottom:6px;">
                    Category <span style="color:var(--danger);">*</span>
                </div>
                <select name="category_id" x-model="categoryId" @change="loadSubs($event.target.value)"
                        style="{{ $errors->has('category_id') ? 'border-color:var(--danger);' : '' }}">
                    <option value="">Select category</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>
                            {{ $cat->name }}
                        </option>
                    @endforeach
                </select>
                @error('category_id') <div style="font-size:12px; color:var(--danger); margin-top:4px;">{{ $message }}</div> @enderror
            </div>
            <div>
                <div style="font-size:12px; font-weight:500; color:var(--fg-2); margin-bottom:6px;">Subcategory</div>
                <select name="subcategory_id" x-model="subcategoryId" :disabled="subcategories.length === 0">
                    <option value="">— None —</option>
                    <template x-for="sub in subcategories" :key="sub.id">
                        <option :value="sub.id" x-text="sub.name" :selected="sub.id == subcategoryId"></option>
                    </template>
                </select>
            </div>
        </div>

        <div style="display:flex; justify-content:flex-end; padding-top:4px;">
            <button type="button" @click="nextStep()" class="btn btn-primary" style="padding:9px 22px;">
                Next <i data-lucide="arrow-right" style="width:14px; height:14px;"></i>
            </button>
        </div>
    </div>
</div>

{{-- ── Step 2: Description ─────────────────────────────────────────── --}}
<div x-show="step === 2"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0 translate-x-4"
     x-transition:enter-end="opacity-100 translate-x-0">
    <div class="card" style="padding:22px; display:flex; flex-direction:column; gap:18px;">
        <div style="font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.07em; color:var(--fg-3);">Step 2 — Description</div>

        <div>
            <div style="font-size:12px; font-weight:500; color:var(--fg-2); margin-bottom:6px;">
                Work Description <span style="color:var(--danger);">*</span>
            </div>
            <textarea name="description" rows="8" x-model="description"
                      style="resize:vertical; {{ $errors->has('description') ? 'border-color:var(--danger);' : '' }}"
                      placeholder="Describe the task clearly. What should workers do? What proof is required?"></textarea>
            <div style="display:flex; justify-content:space-between; margin-top:4px;">
                @error('description')
                    <div style="font-size:12px; color:var(--danger);">{{ $message }}</div>
                @else
                    <div style="font-size:11px; color:var(--fg-4);">Minimum 50 characters</div>
                @enderror
                <span style="font-size:11px; transition:color .2s;"
                      :style="description.length >= 50 ? 'color:var(--success);' : 'color:var(--fg-4);'"
                      x-text="description.length + ' / 50 min'"></span>
            </div>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
            <div>
                <div style="font-size:12px; font-weight:500; color:var(--fg-2); margin-bottom:6px;">Avg. Time (minutes)</div>
                <input type="number" name="avg_minutes" x-model="avgMinutes" min="1" placeholder="e.g. 10">
            </div>
            <div>
                <div style="font-size:12px; font-weight:500; color:var(--fg-2); margin-bottom:6px;">Expiry Date</div>
                <input type="date" name="expires_at" x-model="expiresAt">
            </div>
        </div>

        <div style="display:flex; justify-content:space-between; padding-top:4px;">
            <button type="button" @click="prevStep()" class="btn" style="padding:9px 18px;">
                <i data-lucide="arrow-left" style="width:14px; height:14px;"></i> Back
            </button>
            <button type="button" @click="nextStep()" class="btn btn-primary" style="padding:9px 22px;">
                Next <i data-lucide="arrow-right" style="width:14px; height:14px;"></i>
            </button>
        </div>
    </div>
</div>

{{-- ── Step 3: Budget ──────────────────────────────────────────────── --}}
<div x-show="step === 3"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0 translate-x-4"
     x-transition:enter-end="opacity-100 translate-x-0">
    <div class="card" style="padding:22px; display:flex; flex-direction:column; gap:18px;">
        <div style="font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.07em; color:var(--fg-3);">Step 3 — Budget</div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
            <div>
                <div style="font-size:12px; font-weight:500; color:var(--fg-2); margin-bottom:6px;">
                    Worker Slots <span style="color:var(--danger);">*</span>
                </div>
                <input type="number" name="worker_slots" x-model.number="slots" min="1" max="10000"
                       style="{{ $errors->has('worker_slots') ? 'border-color:var(--danger);' : '' }}">
                @error('worker_slots') <div style="font-size:12px; color:var(--danger); margin-top:4px;">{{ $message }}</div> @enderror
            </div>
            <div>
                <div style="font-size:12px; font-weight:500; color:var(--fg-2); margin-bottom:6px;">
                    Coins Per Worker <span style="color:var(--danger);">*</span>
                </div>
                <input type="number" name="coins_per_worker" x-model.number="coinsEach" step="0.01" min="1"
                       style="{{ $errors->has('coins_per_worker') ? 'border-color:var(--danger);' : '' }}">
                @error('coins_per_worker') <div style="font-size:12px; color:var(--danger); margin-top:4px;">{{ $message }}</div> @enderror
            </div>
        </div>

        <div>
            <div style="font-size:12px; font-weight:500; color:var(--fg-2); margin-bottom:6px;">
                Auto-Approve After
                <span style="color:var(--fg-3); font-weight:400;">(hours — leave empty to approve manually)</span>
            </div>
            <div style="position:relative;">
                <input type="number" name="auto_approve_hours" x-model="autoApproveHours" min="1" max="168"
                       style="padding-right:48px; {{ $errors->has('auto_approve_hours') ? 'border-color:var(--danger);' : '' }}"
                       placeholder="e.g. 24">
                <span style="position:absolute; right:12px; top:50%; transform:translateY(-50%); font-size:11px; color:var(--fg-4);">hours</span>
            </div>
            <div style="font-size:11px; color:var(--fg-4); margin-top:4px;">
                Submissions will be auto-approved after this many hours if you don't review them.
            </div>
            @error('auto_approve_hours') <div style="font-size:12px; color:var(--danger); margin-top:4px;">{{ $message }}</div> @enderror
        </div>

        {{-- Requires KYC --}}
        <div style="display:flex; align-items:flex-start; gap:10px; padding:14px; border-radius:10px; border:1px solid var(--border); background:var(--surface-2);">
            <input type="hidden" name="requires_kyc" value="0">
            <input type="checkbox" id="requires_kyc_work" name="requires_kyc" value="1"
                   style="width:15px; height:15px; accent-color:#F59E0B; cursor:pointer; margin-top:2px; flex-shrink:0;"
                   {{ old('requires_kyc') ? 'checked' : '' }}>
            <div>
                <label for="requires_kyc_work" style="font-size:13px; font-weight:500; color:var(--fg); cursor:pointer; display:flex; align-items:center; gap:6px;">
                    🔒 Requires KYC verification
                </label>
                <div style="font-size:11.5px; color:var(--fg-3); margin-top:3px; line-height:1.5;">Only users who have completed identity (KYC) verification will be able to apply to this work.</div>
            </div>
        </div>

        {{-- Budget calculator --}}
        <div style="padding:16px; border-radius:10px; background:var(--surface-2); border:1px solid var(--border); display:flex; flex-direction:column; gap:10px;">
            <div style="font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.07em; color:var(--fg-3);">Budget Summary</div>
            <div style="display:flex; align-items:center; justify-content:space-between;">
                <span style="font-size:13px; color:var(--fg-2);">Workers</span>
                <span style="font-size:13px; font-weight:600; color:var(--fg);" x-text="slots.toLocaleString()"></span>
            </div>
            <div style="display:flex; align-items:center; justify-content:space-between;">
                <span style="font-size:13px; color:var(--fg-2);">Coins each</span>
                <span class="mono" style="font-size:13px; font-weight:600; color:var(--fg);" x-text="'{{ coinSymbol() }}' + coinsEach.toLocaleString()"></span>
            </div>
            <div style="height:1px; background:var(--border);"></div>
            <div style="display:flex; align-items:center; justify-content:space-between;">
                <span style="font-size:13px; font-weight:600; color:var(--fg-2);">Total deducted</span>
                <span class="mono" style="font-size:17px; font-weight:700; color:#E6C400;" x-text="'{{ coinSymbol() }}' + totalCoins.toLocaleString()"></span>
            </div>
        </div>

        <div style="padding:12px 14px; border-radius:8px; background:rgba(245,158,11,0.06); border:1px solid rgba(245,158,11,0.2); font-size:12px; color:var(--warn); display:flex; gap:8px; align-items:flex-start;">
            <i data-lucide="info" style="width:13px; height:13px; flex-shrink:0; margin-top:1px;"></i>
            <span>This amount will be held from your balance when you submit. Your current balance:
                <strong style="color:var(--fg-2);">{{ formatCoins(auth()->user()->coin_balance) }}</strong>
            </span>
        </div>

        <div style="display:flex; justify-content:space-between; padding-top:4px;">
            <button type="button" @click="prevStep()" class="btn" style="padding:9px 18px;">
                <i data-lucide="arrow-left" style="width:14px; height:14px;"></i> Back
            </button>
            <button type="button" @click="nextStep()" class="btn btn-primary" style="padding:9px 22px;">
                Next <i data-lucide="arrow-right" style="width:14px; height:14px;"></i>
            </button>
        </div>
    </div>
</div>

{{-- ── Step 4: Review & Submit ─────────────────────────────────────── --}}
<div x-show="step === 4"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0 translate-x-4"
     x-transition:enter-end="opacity-100 translate-x-0">
    <div class="card" style="padding:22px; display:flex; flex-direction:column; gap:18px;">
        <div style="font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.07em; color:var(--fg-3);">Step 4 — Cover & Submit</div>

        {{-- Cover image drop zone --}}
        <div>
            <div style="font-size:12px; font-weight:500; color:var(--fg-2); margin-bottom:8px;">
                Cover Image <span style="color:var(--fg-4); font-weight:400;">(optional)</span>
            </div>
            <label style="position:relative; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:8px;
                          border-radius:10px; border:2px dashed; padding:24px; cursor:pointer; transition:all .2s;"
                   :style="coverPreview ? 'border-color:var(--accent); background:var(--accent-soft);' : 'border-color:var(--border-strong); background:var(--surface-2);'"
                   onmouseover="if(!this.querySelector('img')) { this.style.borderColor='rgba(47,84,235,0.5)'; this.style.background='var(--accent-soft)'; }"
                   onmouseout="if(!this.querySelector('img')) { this.style.borderColor='var(--border-strong)'; this.style.background='var(--surface-2)'; }">
                <input type="file" name="cover_image" accept="image/*" style="display:none;"
                       @change="onCoverChange($event)">
                <template x-if="!coverPreview">
                    <div style="text-align:center; pointer-events:none;">
                        <i data-lucide="image-plus" style="width:32px; height:32px; color:var(--fg-4); margin:0 auto 8px; display:block;"></i>
                        <div style="font-size:13px; color:var(--fg-3);">Click to upload cover image</div>
                        <div style="font-size:11px; color:var(--fg-4); margin-top:4px;">PNG, JPG, WEBP — max 2MB</div>
                    </div>
                </template>
                <template x-if="coverPreview">
                    <div style="position:relative;">
                        <img :src="coverPreview" style="height:120px; border-radius:8px; object-fit:cover;">
                        <div style="position:absolute; inset:0; display:flex; align-items:center; justify-content:center; background:rgba(0,0,0,0.35); border-radius:8px; opacity:0; font-size:12px; color:white; transition:opacity .2s;"
                             onmouseover="this.style.opacity=1" onmouseout="this.style.opacity=0">
                            Click to change
                        </div>
                    </div>
                </template>
            </label>
            @error('cover_image') <div style="font-size:12px; color:var(--danger); margin-top:4px;">{{ $message }}</div> @enderror
        </div>

        {{-- Review summary --}}
        <div style="padding:16px; border-radius:10px; background:var(--surface-2); border:1px solid var(--border); display:flex; flex-direction:column; gap:8px;">
            <div style="font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.07em; color:var(--fg-3); margin-bottom:4px;">Review Summary</div>
            <div style="display:flex; gap:10px; font-size:13px;">
                <span style="width:110px; flex-shrink:0; color:var(--fg-3);">Title</span>
                <span style="color:var(--fg); font-weight:500; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" x-text="title || '—'"></span>
            </div>
            <div style="display:flex; gap:10px; font-size:13px;">
                <span style="width:110px; flex-shrink:0; color:var(--fg-3);">Description</span>
                <span style="color:var(--fg);" x-text="description.length + ' chars'"></span>
            </div>
            <div style="display:flex; gap:10px; font-size:13px;">
                <span style="width:110px; flex-shrink:0; color:var(--fg-3);">Slots</span>
                <span style="color:var(--fg);" x-text="slots"></span>
            </div>
            <div style="display:flex; gap:10px; font-size:13px;">
                <span style="width:110px; flex-shrink:0; color:var(--fg-3);">Total Budget</span>
                <span class="mono" style="color:#E6C400; font-weight:700;" x-text="'{{ coinSymbol() }}' + totalCoins.toLocaleString()"></span>
            </div>
            <div x-show="expiresAt" style="display:flex; gap:10px; font-size:13px;">
                <span style="width:110px; flex-shrink:0; color:var(--fg-3);">Expires</span>
                <span style="color:var(--fg);" x-text="expiresAt || '—'"></span>
            </div>
            <div x-show="autoApproveHours" style="display:flex; gap:10px; font-size:13px;">
                <span style="width:110px; flex-shrink:0; color:var(--fg-3);">Auto-Approve</span>
                <span style="color:var(--fg);" x-text="autoApproveHours ? autoApproveHours + 'h after submission' : 'Manual'"></span>
            </div>
        </div>

        <div style="display:flex; justify-content:space-between; padding-top:4px;">
            <button type="button" @click="prevStep()" class="btn" style="padding:9px 18px;">
                <i data-lucide="arrow-left" style="width:14px; height:14px;"></i> Back
            </button>
            <button type="submit" class="btn btn-primary" style="padding:9px 22px;">
                <i data-lucide="send" style="width:14px; height:14px;"></i> Submit for Review
            </button>
        </div>
    </div>
</div>

</form>
</div>
@endsection
