@extends('user.layouts.app')
@section('title', 'Change Password')
@section('page-title', 'Change Password')

@section('content')
<div class="max-w-md">
    <div class="jobstation-card p-6"
         x-data="{
             password: '',
             confirm: '',

             get score() {
                 let s = 0;
                 if (this.password.length >= 8)  s++;
                 if (this.password.length >= 12) s++;
                 if (/[A-Z]/.test(this.password)) s++;
                 if (/[0-9]/.test(this.password)) s++;
                 if (/[^A-Za-z0-9]/.test(this.password)) s++;
                 return s;
             },

             get strengthLabel() {
                 return ['', 'Very Weak', 'Weak', 'Fair', 'Good', 'Strong'][this.score] ?? '';
             },

             get strengthColor() {
                 return [
                     '',
                     'bg-red-500',
                     'bg-orange-500',
                     'bg-amber-400',
                     'bg-lime-400',
                     'bg-green-500'
                 ][this.score] ?? '';
             },

             get strengthTextColor() {
                 return [
                     '',
                     'text-red-400',
                     'text-orange-400',
                     'text-amber-400',
                     'text-lime-400',
                     'text-green-400'
                 ][this.score] ?? '';
             },

             get matchOk() {
                 return this.confirm.length > 0 && this.confirm === this.password;
             },

             get matchFail() {
                 return this.confirm.length > 0 && this.confirm !== this.password;
             }
         }">

        <form method="POST" action="{{ route('user.profile.password.update') }}" class="space-y-5">
            @csrf @method('PUT')

            <div>
                <label class="block text-sm text-white/60 mb-1.5">Current Password <span class="text-red-400">*</span></label>
                <input type="password" name="current_password"
                       class="jobstation-input w-full @error('current_password') border-red-500/50 @enderror"
                       required autocomplete="current-password">
                @error('current_password') <div class="text-xs text-red-400 mt-1">{{ $message }}</div> @enderror
            </div>

            <div>
                <label class="block text-sm text-white/60 mb-1.5">New Password <span class="text-red-400">*</span></label>
                <input type="password" name="password" x-model="password"
                       class="jobstation-input w-full @error('password') border-red-500/50 @enderror"
                       required autocomplete="new-password">
                @error('password') <div class="text-xs text-red-400 mt-1">{{ $message }}</div> @enderror

                {{-- Strength bar --}}
                <div x-show="password.length > 0" class="mt-2 space-y-1">
                    <div class="flex gap-1">
                        <template x-for="i in 5" :key="i">
                            <div class="h-1 flex-1 rounded-full transition-all duration-300"
                                 :class="i <= score ? strengthColor : 'bg-white/10'"></div>
                        </template>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3 text-xs text-white/30">
                            <span :class="password.length >= 8 ? 'text-green-400' : ''">
                                <i data-lucide="check" class="w-3 h-3 inline" x-show="password.length >= 8"></i>
                                <i data-lucide="x" class="w-3 h-3 inline" x-show="password.length < 8"></i>
                                8+ chars
                            </span>
                            <span :class="/[A-Z]/.test(password) ? 'text-green-400' : ''">
                                <i data-lucide="check" class="w-3 h-3 inline" x-show="/[A-Z]/.test(password)"></i>
                                <i data-lucide="x" class="w-3 h-3 inline" x-show="!/[A-Z]/.test(password)"></i>
                                Uppercase
                            </span>
                            <span :class="/[0-9]/.test(password) ? 'text-green-400' : ''">
                                <i data-lucide="check" class="w-3 h-3 inline" x-show="/[0-9]/.test(password)"></i>
                                <i data-lucide="x" class="w-3 h-3 inline" x-show="!/[0-9]/.test(password)"></i>
                                Number
                            </span>
                            <span :class="/[^A-Za-z0-9]/.test(password) ? 'text-green-400' : ''">
                                <i data-lucide="check" class="w-3 h-3 inline" x-show="/[^A-Za-z0-9]/.test(password)"></i>
                                <i data-lucide="x" class="w-3 h-3 inline" x-show="!/[^A-Za-z0-9]/.test(password)"></i>
                                Symbol
                            </span>
                        </div>
                        <span class="text-xs font-semibold" :class="strengthTextColor" x-text="strengthLabel"></span>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-sm text-white/60 mb-1.5">Confirm New Password <span class="text-red-400">*</span></label>
                <div class="relative">
                    <input type="password" name="password_confirmation" x-model="confirm"
                           class="jobstation-input w-full pr-9"
                           :class="matchFail ? 'border-red-500/50' : matchOk ? 'border-green-500/40' : ''"
                           required autocomplete="new-password">
                    <div class="absolute right-3 top-1/2 -translate-y-1/2">
                        <i data-lucide="check-circle" class="w-4 h-4 text-green-400" x-show="matchOk"></i>
                        <i data-lucide="x-circle" class="w-4 h-4 text-red-400" x-show="matchFail"></i>
                    </div>
                </div>
                <div class="text-xs mt-1" x-show="matchFail" style="display:none">
                    <span class="text-red-400">Passwords do not match</span>
                </div>
            </div>

            <div class="pt-2 flex gap-3">
                <button type="submit" class="btn-primary px-6 py-2.5 text-sm"
                        :disabled="matchFail"
                        :class="matchFail ? 'opacity-50 cursor-not-allowed' : ''">
                    <i data-lucide="save" class="w-4 h-4 inline mr-1.5"></i> Update Password
                </button>
                <a href="{{ route('user.profile.settings') }}" class="btn-secondary px-5 py-2.5 text-sm">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
