@extends('user.layouts.app')
@section('title', __('New Contract'))
@section('page-title', __('Send Contract Offer'))

@section('content')
@php $user = Auth::guard('web')->user(); @endphp

<div style="">

    @if(session('error'))
    <div style="padding:14px 16px; border-radius:10px; background:rgba(239,68,68,0.08); border:1px solid rgba(239,68,68,0.2); color:#EF4444; font-size:13.5px; margin-bottom:18px;">
        {{ session('error') }}
    </div>
    @endif

    {{-- Balance reminder --}}
    <div class="card" style="padding:14px 16px; margin-bottom:18px; display:flex; align-items:center; gap:12px;">
        <div style="width:36px; height:36px; border-radius:10px; background:rgba(47,84,235,0.08); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
        </div>
        <div style="font-size:13.5px; color:var(--fg-3);">
            {{ __('Your balance') }}: <strong style="color:var(--fg);">{{ formatCoins($user->coin_balance) }}</strong>
        </div>
    </div>

    <form method="POST" action="{{ route('user.contracts.store') }}" style="display:flex; flex-direction:column; gap:16px;"
          x-data="{
              msEnabled: {{ old('milestones') ? 'true' : 'false' }},
              milestones: {{ old('milestones') ? json_encode(array_values(old('milestones'))) : '[]' }},
              addMs() { this.milestones.push({ title:'', amount:'', description:'', deadline_at:'' }); },
              removeMs(i) { this.milestones.splice(i, 1); },
              get msTotal() { return this.milestones.reduce((s,m) => s + (parseFloat(m.amount)||0), 0); }
          }">
        @csrf

        {{-- Worker section --}}
        <div class="card" style="padding:22px;">
            <h3 style="font-size:14px; font-weight:600; color:var(--fg); margin:0 0 16px;">{{ __('Worker') }}</h3>

            <div>
                <label style="font-size:12px; color:var(--fg-3); display:block; margin-bottom:6px;">
                    {{ __('Username or Email') }} <span style="color:#EF4444;">*</span>
                </label>
                <input type="text" name="worker_identifier"
                       value="{{ old('worker_identifier', $worker?->username ?? $worker?->email) }}"
                       placeholder="worker_username or worker@email.com"
                       style="width:100%; background:var(--surface-2); border:1px solid {{ $errors->has('worker_identifier') ? '#EF4444' : 'var(--border)' }}; border-radius:8px; padding:10px 12px; color:var(--fg); font-size:13.5px; outline:none; box-sizing:border-box;">
                @error('worker_identifier')
                <p style="font-size:12px; color:#EF4444; margin:4px 0 0;">{{ $message }}</p>
                @enderror
                @if($worker)
                <p style="font-size:12px; color:#22C55E; margin:6px 0 0; display:flex; align-items:center; gap:5px;">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    {{ $worker->fullname }} ({{ $worker->username }})
                </p>
                @endif
            </div>
        </div>

        {{-- Contract details --}}
        <div class="card" style="padding:22px;">
            <h3 style="font-size:14px; font-weight:600; color:var(--fg); margin:0 0 16px;">{{ __('Contract Details') }}</h3>

            <div style="display:flex; flex-direction:column; gap:14px;">
                <div>
                    <label style="font-size:12px; color:var(--fg-3); display:block; margin-bottom:6px;">
                        {{ __('Title') }} <span style="color:#EF4444;">*</span>
                    </label>
                    <input type="text" name="title" value="{{ old('title', $prefillTitle) }}"
                           placeholder="{{ __('e.g. Build landing page in React') }}"
                           style="width:100%; background:var(--surface-2); border:1px solid {{ $errors->has('title') ? '#EF4444' : 'var(--border)' }}; border-radius:8px; padding:10px 12px; color:var(--fg); font-size:13.5px; outline:none; box-sizing:border-box;">
                    @error('title')
                    <p style="font-size:12px; color:#EF4444; margin:4px 0 0;">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label style="font-size:12px; color:var(--fg-3); display:block; margin-bottom:6px;">
                        {{ __('Description / Scope') }} <span style="color:#EF4444;">*</span>
                    </label>
                    <textarea name="description" rows="5"
                              placeholder="{{ __('Describe what you need done, deliverables, any specific requirements…') }}"
                              style="width:100%; background:var(--surface-2); border:1px solid {{ $errors->has('description') ? '#EF4444' : 'var(--border)' }}; border-radius:8px; padding:10px 12px; color:var(--fg); font-size:13.5px; outline:none; resize:vertical; box-sizing:border-box;">{{ old('description') }}</textarea>
                    @error('description')
                    <p style="font-size:12px; color:#EF4444; margin:4px 0 0;">{{ $message }}</p>
                    @enderror
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
                    <div x-show="!msEnabled">
                        <label style="font-size:12px; color:var(--fg-3); display:block; margin-bottom:6px;">
                            {{ __('Payment (coins)') }} <span style="color:#EF4444;">*</span>
                        </label>
                        <input type="number" name="amount" value="{{ old('amount') }}"
                               min="1" step="1" placeholder="0"
                               style="width:100%; background:var(--surface-2); border:1px solid {{ $errors->has('amount') ? '#EF4444' : 'var(--border)' }}; border-radius:8px; padding:10px 12px; color:var(--fg); font-size:13.5px; outline:none; box-sizing:border-box;">
                        @error('amount')
                        <p style="font-size:12px; color:#EF4444; margin:4px 0 0;">{{ $message }}</p>
                        @else
                        <p style="font-size:11.5px; color:var(--fg-4); margin:5px 0 0;">{{ __('Held in escrow until you approve the work.') }}</p>
                        @enderror
                    </div>
                    <div x-show="msEnabled" style="display:flex; align-items:center; gap:6px; background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:10px 12px;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                        <span style="font-size:13px; color:var(--fg-3);">{{ __('Total:') }}</span>
                        <span class="mono" style="font-size:13.5px; font-weight:700; color:var(--accent);" x-text="msTotal.toLocaleString() + ' coins'"></span>
                    </div>
                    <div>
                        <label style="font-size:12px; color:var(--fg-3); display:block; margin-bottom:6px;">
                            {{ __('Deadline') }}
                        </label>
                        <input type="date" name="deadline_at" value="{{ old('deadline_at') }}"
                               min="{{ date('Y-m-d', strtotime('+1 day')) }}"
                               style="width:100%; background:var(--surface-2); border:1px solid {{ $errors->has('deadline_at') ? '#EF4444' : 'var(--border)' }}; border-radius:8px; padding:10px 12px; color:var(--fg); font-size:13.5px; outline:none; box-sizing:border-box;">
                        @error('deadline_at')
                        <p style="font-size:12px; color:#EF4444; margin:4px 0 0;">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Milestones (optional) --}}
        <div class="card" style="padding:22px;">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:14px;">
                <div>
                    <div style="font-size:14px; font-weight:600; color:var(--fg);">{{ __('Milestones') }} <span style="font-size:12px; font-weight:400; color:var(--fg-4);">({{ __('optional') }})</span></div>
                    <div style="font-size:12px; color:var(--fg-4); margin-top:2px;">{{ __('Break the work into phases with separate payments') }}</div>
                </div>
                <button type="button" @click="msEnabled = !msEnabled; if(!msEnabled) milestones=[]"
                        :style="msEnabled ? 'background:var(--accent); color:white; border-color:var(--accent);' : ''"
                        style="font-size:12px; padding:5px 14px; border-radius:8px; border:1px solid var(--border); background:var(--surface-2); color:var(--fg-2); cursor:pointer; font-family:inherit; transition:all .14s;"
                        x-text="msEnabled ? '{{ __('Enabled') }}' : '{{ __('Add Milestones') }}'"></button>
            </div>

            <div x-show="msEnabled" x-transition>
                {{-- Milestone rows --}}
                <template x-for="(ms, i) in milestones" :key="i">
                <div style="border:1px solid var(--border); border-radius:10px; padding:14px; margin-bottom:10px; position:relative; background:var(--surface-2);">
                    <div style="display:flex; align-items:center; gap:8px; margin-bottom:10px;">
                        <span class="mono" style="font-size:11px; color:var(--fg-4); background:var(--surface-3); padding:2px 7px; border-radius:5px;" x-text="'#'+(i+1)"></span>
                        <span style="font-size:12.5px; font-weight:500; color:var(--fg-3);">{{ __('Milestone') }}</span>
                        <button type="button" @click="removeMs(i)"
                                style="margin-left:auto; background:none; border:none; color:var(--fg-4); cursor:pointer; padding:0; display:flex; align-items:center;"
                                onmouseover="this.style.color='#EF4444'" onmouseout="this.style.color='var(--fg-4)'">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        </button>
                    </div>
                    <div style="display:flex; flex-direction:column; gap:10px;">
                        <div style="display:grid; grid-template-columns:1fr 160px; gap:10px;">
                            <div>
                                <label style="font-size:11.5px; color:var(--fg-3); display:block; margin-bottom:5px;">{{ __('Title') }} <span style="color:#EF4444;">*</span></label>
                                <input type="text" :name="'milestones['+i+'][title]'" x-model="ms.title"
                                       :placeholder="'{{ __('e.g. Phase') }} '+(i+1)"
                                       style="width:100%; background:var(--surface); border:1px solid var(--border); border-radius:8px; padding:8px 10px; color:var(--fg); font-size:13px; outline:none; box-sizing:border-box;">
                            </div>
                            <div>
                                <label style="font-size:11.5px; color:var(--fg-3); display:block; margin-bottom:5px;">{{ __('Amount (coins)') }} <span style="color:#EF4444;">*</span></label>
                                <input type="number" :name="'milestones['+i+'][amount]'" x-model="ms.amount"
                                       min="1" step="1" placeholder="0"
                                       style="width:100%; background:var(--surface); border:1px solid var(--border); border-radius:8px; padding:8px 10px; color:var(--fg); font-size:13px; outline:none; box-sizing:border-box;">
                            </div>
                        </div>
                        <div style="display:grid; grid-template-columns:1fr 160px; gap:10px;">
                            <div>
                                <label style="font-size:11.5px; color:var(--fg-3); display:block; margin-bottom:5px;">{{ __('Description') }} <span style="font-size:11px; color:var(--fg-4);">({{ __('optional') }})</span></label>
                                <input type="text" :name="'milestones['+i+'][description]'" x-model="ms.description"
                                       placeholder="{{ __('What should be delivered?') }}"
                                       style="width:100%; background:var(--surface); border:1px solid var(--border); border-radius:8px; padding:8px 10px; color:var(--fg); font-size:13px; outline:none; box-sizing:border-box;">
                            </div>
                            <div>
                                <label style="font-size:11.5px; color:var(--fg-3); display:block; margin-bottom:5px;">{{ __('Deadline') }} <span style="font-size:11px; color:var(--fg-4);">({{ __('optional') }})</span></label>
                                <input type="date" :name="'milestones['+i+'][deadline_at]'" x-model="ms.deadline_at"
                                       min="{{ date('Y-m-d', strtotime('+1 day')) }}"
                                       style="width:100%; background:var(--surface); border:1px solid var(--border); border-radius:8px; padding:8px 10px; color:var(--fg); font-size:13px; outline:none; box-sizing:border-box;">
                            </div>
                        </div>
                    </div>
                </div>
                </template>

                <button type="button" @click="addMs()"
                        style="width:100%; padding:9px; border-radius:8px; border:1px dashed var(--border); background:transparent; color:var(--fg-3); cursor:pointer; font-size:13px; font-family:inherit; display:flex; align-items:center; justify-content:center; gap:6px; transition:all .14s; margin-bottom:10px;"
                        onmouseover="this.style.borderColor='var(--accent)'; this.style.color='var(--accent)'" onmouseout="this.style.borderColor='var(--border)'; this.style.color='var(--fg-3)'">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    {{ __('Add Milestone') }}
                </button>

                <div x-show="milestones.length > 0" style="display:flex; justify-content:flex-end; align-items:center; gap:8px; font-size:13.5px; padding:10px 4px 0; border-top:1px solid var(--border);">
                    <span style="color:var(--fg-3);">{{ __('Total') }}:</span>
                    <span class="mono" style="font-size:15px; font-weight:700; color:var(--accent);" x-text="msTotal.toLocaleString() + ' coins'"></span>
                </div>
            </div>
        </div>

        {{-- Info note --}}
        <div style="padding:14px 16px; border-radius:10px; background:rgba(245,158,11,0.07); border:1px solid rgba(245,158,11,0.18); display:flex; gap:10px; align-items:flex-start;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#F59E0B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0; margin-top:1px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <span style="font-size:13px; color:#F59E0B; line-height:1.5;">
                {{ __('Coins are only deducted when the worker') }} <strong>{{ __('accepts') }}</strong> {{ __('the contract. You can cancel a pending offer anytime.') }}
            </span>
        </div>

        {{-- Actions --}}
        <div style="display:flex; gap:10px; align-items:center;">
            <button type="submit" class="btn btn-primary" style="padding:11px 24px; font-size:14px;">
                {{ __('Send Contract Offer') }}
            </button>
            <a href="{{ route('user.contracts.sent') }}" class="btn" style="padding:11px 20px; font-size:14px;">
                {{ __('Cancel') }}
            </a>
        </div>
    </form>
</div>

<style>
input:focus, textarea:focus, select:focus {
    border-color: var(--accent) !important;
    box-shadow: 0 0 0 3px rgba(47,84,235,0.12);
}
</style>
@endsection
