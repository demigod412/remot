<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Skill;
use App\Services\RankService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function settings()
    {
        $user        = Auth::guard('web')->user();
        $skills      = Skill::active()->orderBy('name')->get();
        $userSkillIds= $user->skills()->pluck('skills.id')->toArray();
        $rank        = RankService::getLevel($user);
        return view('user.profile.settings', compact('user', 'skills', 'userSkillIds', 'rank'));
    }

    public function update(Request $request)
    {
        $user = Auth::guard('web')->user();

        $data = $request->validate([
            'firstname'    => ['required', 'string', 'max:60'],
            'lastname'     => ['required', 'string', 'max:60'],
            'username'     => ['required', 'alpha_num', 'min:4', 'max:30', "unique:users,username,{$user->id}"],
            'mobile'       => ['nullable', 'string', 'max:20'],
            'country_code' => ['nullable', 'string', 'max:10'],
            'image'        => ['nullable', 'image', 'max:2048'],
            // The skills checkboxes are inside THIS form, not a separate one.
            //
            // updateSkills() and its route exist and work, but no view ever posts to
            // them — so skills arrived here, were not validated, and were discarded.
            // The page said "Profile updated", which was true, while the selection
            // silently went nowhere.
            'skills'       => ['nullable', 'array', 'max:20'],
            'skills.*'     => ['integer', 'exists:skills,id'],
        ]);

        if ($request->hasFile('image')) {
            if ($user->image) {
                removeFile(config('jobstation.upload_paths.user_avatar'), $user->image);
            }
            $data['image'] = uploadFile($request->file('image'), config('jobstation.upload_paths.user_avatar'));
        }

        // Removed before update(): 'skills' is a relationship, not a users column, and
        // passing it through would attempt to write a column that does not exist.
        $skills = $data['skills'] ?? [];
        unset($data['skills']);

        $user->update($data);

        // Guarded by a hidden marker field. An absent 'skills' key means either
        // "every box was unchecked" or "this form had no skills block at all", and
        // those need opposite handling — sync([]) on the second would wipe the
        // selection every time an unrelated form was saved.
        if ($request->has('skills_submitted')) {
            $user->skills()->sync($skills);
        }

        return back()->with('success', 'Profile updated successfully.');
    }

    public function password()
    {
        return view('user.profile.password');
    }

    public function updatePassword(Request $request)
    {
        $user = Auth::guard('web')->user();

        $request->validate([
            'current_password' => ['required', function ($attribute, $value, $fail) use ($user) {
                if (! Hash::check($value, $user->password)) {
                    $fail('Current password is incorrect.');
                }
            }],
            'password' => ['required', 'confirmed', 'min:6', 'different:current_password'],
        ]);

        $user->update(['password' => $request->password]);

        return back()->with('success', 'Password changed successfully.');
    }

    public function twoFactor()
    {
        $user = Auth::guard('web')->user();
        return view('user.profile.two-factor', compact('user'));
    }

    public function enableTwoFactor(Request $request)
    {
        $user = Auth::guard('web')->user();

        // Step 1: send confirmation code, show the verify form
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $user->update(['verify_code' => $code, 'verify_code_sent_at' => now()]);
        \App\Services\NotifyService::send($user, 'EMAIL_VERIFICATION', ['code' => $code]);

        return back()->with('2fa_pending', true)
                     ->with('info', 'A 6-digit code has been sent to ' . $user->email . '. Enter it below to activate 2FA.');
    }

    public function confirmTwoFactor(Request $request)
    {
        $request->validate(['code' => ['required', 'digits:6']]);
        $user = Auth::guard('web')->user();

        if ($user->verify_code !== $request->code) {
            return back()->with('2fa_pending', true)->with('error', 'Invalid code. Try again.');
        }

        if ($user->verify_code_sent_at && $user->verify_code_sent_at->diffInMinutes(now()) > 10) {
            return back()->with('error', 'Code has expired. Please request a new one.');
        }

        $user->update(['two_fa_enabled' => 1, 'verify_code' => null]);
        return back()->with('success', '2FA is now active. A code will be required on each login.');
    }

    public function disableTwoFactor(Request $request)
    {
        $request->validate(['password' => ['required']]);
        $user = Auth::guard('web')->user();

        if (! Hash::check($request->password, $user->password)) {
            return back()->with('error', 'Incorrect password. 2FA not disabled.');
        }

        $user->update(['two_fa_enabled' => 0, 'two_fa_verified' => 0]);
        return back()->with('success', '2FA has been disabled.');
    }

    public function kyc()
    {
        $user = Auth::guard('web')->user();
        return view('user.profile.kyc', compact('user'));
    }

    public function submitKyc(Request $request)
    {
        $user = Auth::guard('web')->user();

        if ($user->kyc_status === 1) {
            return back()->with('error', 'Your KYC is already verified.');
        }

        $request->validate([
            'document_type' => ['required', 'string'],
            'front_image'   => ['required', 'image', 'max:4096'],
            'back_image'    => ['nullable', 'image', 'max:4096'],
        ]);

        $kycData = [
            'document_type' => $request->document_type,
            'front_image'   => uploadPrivateFile($request->file('front_image'), 'kyc'),
            'back_image'    => null,
            'submitted_at'  => now()->toDateTimeString(),
        ];

        if ($request->hasFile('back_image')) {
            $kycData['back_image'] = uploadPrivateFile($request->file('back_image'), 'kyc');
        }

        $user->update([
            'kyc_data'   => $kycData,
            'kyc_status' => 2, // pending
        ]);

        return back()->with('success', 'KYC documents submitted. We\'ll review within 24–48 hours.');
    }

    public function updateSkills(Request $request)
    {
        $user = Auth::guard('web')->user();

        // The form posts skills[], not skill_ids[].
        //
        // Validating the wrong name meant $data never contained it, so sync([]) ran on
        // every save and cleared the worker's selection. The page then reloaded showing
        // nothing selected, which reads as "it did not save" when in fact it saved
        // emptiness. Worth noting the failure mode: a validator that never sees a field
        // does not complain about it.
        $data = $request->validate([
            'skills'   => ['nullable', 'array', 'max:20'],
            'skills.*' => ['integer', 'exists:skills,id'],
        ]);

        $user->skills()->sync($data['skills'] ?? []);

        return back()->with('success', 'Skills updated.');
    }
}
