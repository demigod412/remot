@extends('user.layouts.auth')
@section('title', 'Complete Your Profile')

@section('content')
<div class="text-center mb-6">
    <div class="w-12 h-12 rounded-full bg-primary/10 border border-primary/30 flex items-center justify-center mx-auto mb-4">
        <i data-lucide="user-check" class="w-5 h-5 text-primary"></i>
    </div>
    <h1 class="text-xl font-bold text-white">One last step</h1>
    <p class="text-sm text-white/40 mt-1">Complete your profile to get started</p>
</div>

<form method="POST" action="{{ route('user.onboarding.submit') }}" class="space-y-4">
    @csrf

    <div>
        <label class="block text-sm text-white/60 mb-1.5">Country Code <span class="text-red-400">*</span></label>
        <input type="text" name="country_code" value="{{ old('country_code', $user->country_code) }}"
               class="jobstation-input w-full @error('country_code') border-red-500/50 @enderror"
               placeholder="US" required>
        @error('country_code') <div class="text-xs text-red-400 mt-1">{{ $message }}</div> @enderror
    </div>

    <div>
        <label class="block text-sm text-white/60 mb-1.5">Mobile Number</label>
        <input type="text" name="mobile" value="{{ old('mobile', $user->mobile) }}"
               class="jobstation-input w-full @error('mobile') border-red-500/50 @enderror"
               placeholder="+1 555 000 0000">
        @error('mobile') <div class="text-xs text-red-400 mt-1">{{ $message }}</div> @enderror
    </div>

    <button type="submit" class="btn-primary w-full py-2.5 text-sm font-semibold">
        Continue to Dashboard
    </button>
</form>
@endsection
