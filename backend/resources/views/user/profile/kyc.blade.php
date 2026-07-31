@extends('user.layouts.app')
@section('title', 'KYC Verification')
@section('page-title', 'KYC Verification')

@section('content')
@php $kyc = $user->kyc_status ?? 0; @endphp

{{-- Breadcrumb --}}
<div style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--fg-4);margin-bottom:24px;">
    <a href="{{ route('user.profile.settings') }}" style="color:var(--fg-4);text-decoration:none;"
       onmouseover="this.style.color='var(--fg-2)'" onmouseout="this.style.color='var(--fg-4)'">Profile</a>
    <i data-lucide="chevron-right" style="width:12px;height:12px;"></i>
    <span style="color:var(--fg-3);">KYC Verification</span>
</div>

<div style="">

    {{-- Status banner --}}
    @if($kyc == 1)
    <div style="display:flex;align-items:center;gap:14px;padding:16px 18px;border-radius:12px;background:rgba(34,197,94,0.08);border:1px solid rgba(34,197,94,0.2);margin-bottom:20px;">
        <div style="width:38px;height:38px;border-radius:10px;background:rgba(34,197,94,0.15);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i data-lucide="shield-check" style="width:18px;height:18px;color:#22C55E;"></i>
        </div>
        <div>
            <div style="font-size:14px;font-weight:600;color:#22C55E;margin-bottom:2px;">Identity Verified</div>
            <div style="font-size:12.5px;color:var(--fg-3);">Your account is fully verified. You have access to all features including cashouts and contracts.</div>
        </div>
    </div>

    @elseif($kyc == 2)
    <div style="display:flex;align-items:center;gap:14px;padding:16px 18px;border-radius:12px;background:rgba(245,158,11,0.08);border:1px solid rgba(245,158,11,0.2);margin-bottom:20px;">
        <div style="width:38px;height:38px;border-radius:10px;background:rgba(245,158,11,0.15);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i data-lucide="clock" style="width:18px;height:18px;color:#F59E0B;"></i>
        </div>
        <div>
            <div style="font-size:14px;font-weight:600;color:#F59E0B;margin-bottom:2px;">Under Review</div>
            <div style="font-size:12.5px;color:var(--fg-3);">Your documents were submitted and are being reviewed. This usually takes 24–48 hours. We'll notify you when done.</div>
        </div>
    </div>

    @else
    <div style="display:flex;align-items:center;gap:14px;padding:16px 18px;border-radius:12px;background:rgba(239,68,68,0.06);border:1px solid rgba(239,68,68,0.18);margin-bottom:20px;">
        <div style="width:38px;height:38px;border-radius:10px;background:rgba(239,68,68,0.12);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i data-lucide="shield-alert" style="width:18px;height:18px;color:#EF4444;"></i>
        </div>
        <div>
            <div style="font-size:14px;font-weight:600;color:#EF4444;margin-bottom:2px;">Not Verified</div>
            <div style="font-size:12.5px;color:var(--fg-3);">Submit your government-issued ID to unlock cashouts, contracts, and access to all tasks.</div>
        </div>
    </div>
    @endif

    {{-- What you unlock --}}
    @if($kyc == 0)
    <div class="card" style="padding:18px;margin-bottom:16px;">
        <div class="label" style="margin-bottom:12px;">What verification unlocks</div>
        <div style="display:flex;flex-direction:column;gap:8px;">
            @foreach([
                ['Withdraw earnings to your bank or mobile money', 'banknote', '#22C55E'],
                ['Access to all tasks including restricted ones', 'zap', 'var(--accent)'],
                ['Send and receive contracts', 'file-text', '#60A5FA'],
                ['Verified badge on your public profile', 'shield-check', '#F59E0B'],
            ] as [$text, $icon, $color])
            <div style="display:flex;align-items:center;gap:10px;">
                <div style="width:28px;height:28px;border-radius:7px;background:{{ $color }}15;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i data-lucide="{{ $icon }}" style="width:13px;height:13px;color:{{ $color }};"></i>
                </div>
                <span style="font-size:13px;color:var(--fg-2);">{{ $text }}</span>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Submission form --}}
    @if($kyc != 1)
    <div class="card" style="padding:24px;">
        <h2 style="font-size:15px;font-weight:600;color:var(--fg);margin:0 0 20px;">
            {{ $kyc == 2 ? 'Documents Submitted' : 'Submit Your ID Document' }}
        </h2>

        @if(session('success'))
        <div style="padding:11px 14px;border-radius:9px;background:rgba(34,197,94,0.08);border:1px solid rgba(34,197,94,0.2);color:#22C55E;font-size:13px;margin-bottom:16px;">
            {{ session('success') }}
        </div>
        @endif
        @if($errors->any())
        <div style="padding:11px 14px;border-radius:9px;background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.2);color:#EF4444;font-size:13px;margin-bottom:16px;">
            {{ $errors->first() }}
        </div>
        @endif

        @if($kyc == 2)
        <div style="text-align:center;padding:20px 0;color:var(--fg-3);">
            <i data-lucide="clock" style="width:32px;height:32px;color:#F59E0B;margin:0 auto 12px;display:block;"></i>
            <div style="font-size:14px;font-weight:500;color:var(--fg-2);margin-bottom:6px;">Documents received</div>
            <div style="font-size:13px;color:var(--fg-3);">Our team is reviewing your submission. You'll get a notification once approved.</div>
        </div>
        @else
        <form method="POST" action="{{ route('user.profile.kyc.submit') }}" enctype="multipart/form-data">
            @csrf

            {{-- Document type --}}
            <div style="margin-bottom:16px;">
                <label style="display:block;font-size:12.5px;font-weight:500;color:var(--fg-2);margin-bottom:7px;">
                    Document Type <span style="color:#EF4444;">*</span>
                </label>
                <select name="document_type"
                        style="width:100%;padding:10px 12px;border-radius:9px;border:1px solid var(--border);background:var(--surface);color:var(--fg);font-size:13.5px;outline:none;cursor:pointer;"
                        onfocus="this.style.borderColor='var(--accent)'" onblur="this.style.borderColor='var(--border)'"
                        required>
                    <option value="">Select document type…</option>
                    <option value="national_id"       {{ old('document_type') == 'national_id'       ? 'selected' : '' }}>National ID</option>
                    <option value="passport"          {{ old('document_type') == 'passport'          ? 'selected' : '' }}>Passport</option>
                    <option value="drivers_license"   {{ old('document_type') == 'drivers_license'   ? 'selected' : '' }}>Driver's License</option>
                    <option value="voter_id"          {{ old('document_type') == 'voter_id'          ? 'selected' : '' }}>Voter's ID</option>
                </select>
                @error('document_type')<div style="font-size:12px;color:#EF4444;margin-top:5px;">{{ $message }}</div>@enderror
            </div>

            {{-- Front image --}}
            <div style="margin-bottom:16px;">
                <label style="display:block;font-size:12.5px;font-weight:500;color:var(--fg-2);margin-bottom:7px;">
                    Front Image <span style="color:#EF4444;">*</span>
                </label>
                <div style="border:2px dashed var(--border);border-radius:10px;padding:20px;text-align:center;cursor:pointer;transition:border-color .15s;position:relative;"
                     onmouseover="this.style.borderColor='var(--accent)'" onmouseout="this.style.borderColor='var(--border)'">
                    <i data-lucide="upload-cloud" style="width:24px;height:24px;color:var(--fg-4);margin:0 auto 8px;display:block;"></i>
                    <div style="font-size:13px;color:var(--fg-3);margin-bottom:4px;">Click to upload front of document</div>
                    <div style="font-size:11.5px;color:var(--fg-4);">JPG, PNG — max 4MB</div>
                    <input type="file" name="front_image" accept="image/*" required
                           style="position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%;">
                </div>
                <div id="front-preview" style="display:none;margin-top:8px;"></div>
                @error('front_image')<div style="font-size:12px;color:#EF4444;margin-top:5px;">{{ $message }}</div>@enderror
            </div>

            {{-- Back image --}}
            <div style="margin-bottom:20px;">
                <label style="display:block;font-size:12.5px;font-weight:500;color:var(--fg-2);margin-bottom:7px;">
                    Back Image <span style="font-size:11.5px;color:var(--fg-4);font-weight:400;">(optional)</span>
                </label>
                <div style="border:2px dashed var(--border);border-radius:10px;padding:20px;text-align:center;cursor:pointer;transition:border-color .15s;position:relative;"
                     onmouseover="this.style.borderColor='var(--accent)'" onmouseout="this.style.borderColor='var(--border)'">
                    <i data-lucide="upload-cloud" style="width:24px;height:24px;color:var(--fg-4);margin:0 auto 8px;display:block;"></i>
                    <div style="font-size:13px;color:var(--fg-3);margin-bottom:4px;">Click to upload back of document</div>
                    <div style="font-size:11.5px;color:var(--fg-4);">JPG, PNG — max 4MB</div>
                    <input type="file" name="back_image" accept="image/*"
                           style="position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%;">
                </div>
                <div id="back-preview" style="display:none;margin-top:8px;"></div>
                @error('back_image')<div style="font-size:12px;color:#EF4444;margin-top:5px;">{{ $message }}</div>@enderror
            </div>

            {{-- Privacy note --}}
            <div style="display:flex;align-items:flex-start;gap:9px;padding:12px 14px;border-radius:9px;background:var(--surface-2);border:1px solid var(--border);margin-bottom:20px;">
                <i data-lucide="lock" style="width:13px;height:13px;color:var(--fg-4);margin-top:1px;flex-shrink:0;"></i>
                <span style="font-size:12px;color:var(--fg-4);line-height:1.5;">Your documents are encrypted and used solely for identity verification. We never share them with third parties.</span>
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;display:flex;align-items:center;gap:8px;padding:12px;font-size:14px;">
                <i data-lucide="upload" style="width:15px;height:15px;"></i>
                Submit for Verification
            </button>
        </form>
        @endif
    </div>
    @endif

</div>

<script>
['front', 'back'].forEach(function(side) {
    var input = document.querySelector('input[name="' + side + '_image"]');
    var preview = document.getElementById(side + '-preview');
    if (!input || !preview) return;
    input.addEventListener('change', function() {
        var file = this.files[0];
        if (!file) return;
        var reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = '<img src="' + e.target.result + '" style="width:100%;max-height:160px;object-fit:cover;border-radius:8px;border:1px solid var(--border);">';
            preview.style.display = 'block';
        };
        reader.readAsDataURL(file);
    });
});
</script>

@endsection
