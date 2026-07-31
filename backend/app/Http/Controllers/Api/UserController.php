<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    /** GET /api/v1/user */
    public function profile(Request $request): JsonResponse
    {
        return response()->json(['user' => $this->userResource($request->user())]);
    }

    /** PUT /api/v1/user/profile */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'firstname'    => ['sometimes', 'string', 'max:60'],
            'lastname'     => ['sometimes', 'string', 'max:60'],
            'username'     => ['sometimes', 'string', 'max:60', 'unique:users,username,' . $user->id],
            'bio'          => ['sometimes', 'nullable', 'string', 'max:500'],
            'mobile'       => ['sometimes', 'nullable', 'string', 'max:20'],
            'country_code' => ['sometimes', 'nullable', 'string', 'max:10'],
            'address'      => ['sometimes', 'nullable', 'string', 'max:255'],
            'city'         => ['sometimes', 'nullable', 'string', 'max:100'],
            'state'        => ['sometimes', 'nullable', 'string', 'max:100'],
            'zip'          => ['sometimes', 'nullable', 'string', 'max:20'],
        ]);

        $user->update($data);

        return response()->json([
            'message' => 'Profile updated.',
            'user'    => $this->userResource($user->fresh()),
        ]);
    }

    /** PUT /api/v1/user/password */
    public function changePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'confirmed', 'min:6'],
        ]);

        if (! Hash::check($data['current_password'], $user->password)) {
            return response()->json(['message' => 'Current password is incorrect.'], 422);
        }

        $user->update(['password' => Hash::make($data['password'])]);

        return response()->json(['message' => 'Password updated successfully.']);
    }

    /** POST /api/v1/user/avatar */
    public function uploadAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'max:2048'],
        ]);

        $user = $request->user();

        $filename = uploadFile($request->file('avatar'), config('jobstation.upload_paths.user_avatar'));

        $user->update(['image' => $filename]);

        return response()->json([
            'message' => 'Avatar updated.',
            'avatar'  => fileUrl(config('jobstation.upload_paths.user_avatar'), $filename),
        ]);
    }

    /** GET /api/v1/user/kyc */
    public function kycForm(Request $request): JsonResponse
    {
        $user = $request->user();
        $kyc  = $user->kyc_data;

        return response()->json([
            'kyc_status' => $user->kyc_status,
            'document'   => $kyc ? [
                'document_type' => $kyc['document_type'] ?? null,
                'front_image'   => isset($kyc['front_image'])
                    ? \Illuminate\Support\Facades\URL::temporarySignedRoute('secure.kyc', now()->addMinutes(30), ['user' => $user->id, 'side' => 'front'])
                    : null,
                'back_image'    => isset($kyc['back_image'])
                    ? \Illuminate\Support\Facades\URL::temporarySignedRoute('secure.kyc', now()->addMinutes(30), ['user' => $user->id, 'side' => 'back'])
                    : null,
            ] : null,
        ]);
    }

    /** POST /api/v1/user/device-token — register or refresh an FCM push token */
    public function registerDeviceToken(Request $request): JsonResponse
    {
        $request->validate([
            'token'    => ['required', 'string', 'max:512'],
            'platform' => ['required', 'in:android,ios,unknown'],
        ]);

        $user = $request->user();

        // Upsert: if the token already exists for any user (device switched accounts),
        // remove the old binding first, then re-bind to the current user.
        \App\Models\UserDeviceToken::where('token', $request->token)
            ->where('user_id', '!=', $user->id)
            ->delete();

        $user->deviceTokens()->updateOrCreate(
            ['token' => $request->token],
            ['platform' => $request->platform],
        );

        return response()->json(['message' => 'Device token registered.']);
    }

    /** DELETE /api/v1/user/device-token — unregister (called on app sign-out if logout route not used) */
    public function removeDeviceToken(Request $request): JsonResponse
    {
        $request->validate(['token' => ['required', 'string', 'max:512']]);

        $request->user()->deviceTokens()->where('token', $request->token)->delete();

        return response()->json(['message' => 'Device token removed.']);
    }

    /** POST /api/v1/user/kyc */
    public function submitKyc(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->kyc_status === 1) {
            return response()->json(['message' => 'KYC already approved.'], 422);
        }

        $request->validate([
            // The app sends `id_type`; accept either for compatibility.
            'document_type' => ['nullable', 'string', 'max:50'],
            'id_type'       => ['nullable', 'string', 'max:50'],
            'front_image'   => ['required', 'image', 'max:4096'],
            'back_image'    => ['nullable', 'image', 'max:4096'],
            'selfie'        => ['nullable', 'image', 'max:4096'],
        ]);

        $kycData = [
            'document_type' => $request->document_type ?? $request->id_type ?? 'id',
            'front_image'   => uploadPrivateFile($request->file('front_image'), 'kyc'),
        ];

        if ($request->hasFile('back_image')) {
            $kycData['back_image'] = uploadPrivateFile($request->file('back_image'), 'kyc');
        }

        if ($request->hasFile('selfie')) {
            $kycData['selfie'] = uploadPrivateFile($request->file('selfie'), 'kyc');
        }

        $user->update([
            'kyc_data'   => $kycData,
            'kyc_status' => 2, // pending review
        ]);

        return response()->json(['message' => 'KYC documents submitted. We will review them shortly.']);
    }

    /** GET /api/v1/user/skills — the current user's selected skills. */
    public function skills(Request $request): JsonResponse
    {
        $skills = $request->user()->skills()->get(['skills.id', 'skills.name']);

        return response()->json([
            'data' => $skills->map(fn ($s) => ['id' => $s->id, 'name' => $s->name]),
        ]);
    }

    /** PUT /api/v1/user/skills — replace the user's skills. */
    public function updateSkills(Request $request): JsonResponse
    {
        $request->validate([
            'skill_ids'   => ['present', 'array'],
            'skill_ids.*' => ['integer', 'exists:skills,id'],
        ]);

        $request->user()->skills()->sync($request->skill_ids);

        return response()->json(['message' => 'Skills updated.']);
    }

    private function userResource($user): array
    {
        $approvedSubs  = $user->workSubmissions()->where('status', 2)->count();
        $reviewedSubs  = $user->workSubmissions()->whereIn('status', [2, 3])->count();
        $approvalRate  = $reviewedSubs > 0 ? round(($approvedSubs / $reviewedSubs) * 100, 1) : 0.0;
        $ratingData    = $user->publicRatingData();

        // Real counts so profile badges match their lists.
        $activeContracts = \App\Models\Contract::where(fn ($q) => $q
                ->where('employer_id', $user->id)->orWhere('worker_id', $user->id))
            ->whereIn('status', [0, 1, 2]) // offered / accepted / submitted
            ->count();
        $applicationCount = \App\Models\JobApplication::where('applicant_id', $user->id)->count();
        $submissionCount  = $user->workSubmissions()->count(); // all submissions, any status

        return [
            'id'              => $user->id,
            'firstname'       => $user->firstname,
            'lastname'        => $user->lastname,
            'username'        => $user->username,
            'email'           => $user->email,
            'mobile'          => $user->mobile,
            'phone'           => $user->mobile,
            'bio'             => $user->bio ?? null,
            'country_code'    => $user->country_code,
            'address'         => $user->address,
            'city'            => $user->city,
            'state'           => $user->state,
            'zip'             => $user->zip,
            'email_verified'  => (bool) $user->email_verified,
            'phone_verified'  => (bool) $user->phone_verified,
            'two_fa_enabled'  => (bool) $user->two_fa_enabled,
            'kyc_status'      => $user->kyc_status,
            'kyc_verified'    => $user->kyc_status === 1,
            'coin_balance'    => (float) $user->coin_balance,
            'status'          => $user->status,
            'avatar'          => $user->image
                ? fileUrl(config('jobstation.upload_paths.user_avatar'), $user->image)
                : null,
            'joined_at'       => $user->created_at,
            'rating'          => $ratingData['avg'],
            'completed_tasks' => $approvedSubs,
            'submission_count'=> $submissionCount,
            'active_contracts'=> $activeContracts,
            'application_count'=> $applicationCount,
            'approval_rate'   => $approvalRate,
            'streak'          => 0,
            'role'            => null,
        ];
    }
}
