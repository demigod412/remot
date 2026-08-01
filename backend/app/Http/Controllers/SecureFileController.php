<?php

namespace App\Http\Controllers;

use App\Models\CoinTopup;
use App\Models\Contract;
use App\Models\ContractMilestone;
use App\Models\HelpFile;
use App\Models\JobApplication;
use App\Models\User;
use App\Models\WorkSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * Streams private, access-controlled files (KYC identity documents) that must
 * never live in the public web root. Access is granted only to:
 *   - the document owner (web session or Sanctum token), or
 *   - any authenticated admin (KYC review), or
 *   - a valid temporary signed URL (used by the mobile app to load images).
 */
class SecureFileController extends Controller
{
    /** Where KYC documents live on the private "local" disk. */
    private const KYC_DIR = 'kyc';

    public function kyc(Request $request, int $user, string $side)
    {
        $owner = User::findOrFail($user);

        $authorised = $request->hasValidSignature()
            || Auth::guard('admin')->check()
            || (Auth::guard('web')->id() === $owner->id)
            || (optional(Auth::guard('sanctum')->user())->id === $owner->id);

        abort_unless($authorised, 403);

        $kyc = $owner->kyc_data ?? [];
        $key = $side === 'back' ? 'back_image' : 'front_image';
        $filename = $kyc[$key] ?? null;

        abort_if(! $filename, 404);

        $path = self::KYC_DIR . '/' . $filename;

        return $this->stream($path);
    }

    /**
     * A job-application résumé (applicant PII). Viewable by the applicant, the
     * employer who owns the listing, an admin, or via a signed URL.
     */
    public function resume(Request $request, int $application)
    {
        $app = JobApplication::with('listing')->findOrFail($application);

        $viewerWeb     = Auth::guard('web')->id();
        $viewerSanctum = optional(Auth::guard('sanctum')->user())->id;
        $employerId    = optional($app->listing)->employer_id;

        $authorised = $request->hasValidSignature()
            || Auth::guard('admin')->check()
            || in_array($app->applicant_id, [$viewerWeb, $viewerSanctum], true)
            || ($employerId !== null && in_array($employerId, [$viewerWeb, $viewerSanctum], true));

        abort_unless($authorised, 403);
        abort_if(! $app->resume, 404);

        return $this->stream('resumes/' . $app->resume);
    }

    /**
     * A manual top-up payment proof (financial data). Viewable by the top-up
     * owner, an admin, or via a signed URL.
     */
    public function topupProof(Request $request, int $topup)
    {
        $row = CoinTopup::findOrFail($topup);

        $authorised = $request->hasValidSignature()
            || Auth::guard('admin')->check()
            || in_array($row->user_id, [
                Auth::guard('web')->id(),
                optional(Auth::guard('sanctum')->user())->id,
            ], true);

        abort_unless($authorised, 403);
        abort_if(! $row->proof_image, 404);

        return $this->stream('topup-proofs/' . $row->proof_image);
    }

    /**
     * A help-desk attachment. Viewable by the ticket owner, an admin, or a signed URL.
     */
    public function helpFile(Request $request, int $file)
    {
        $hf      = HelpFile::with('message.ticket')->findOrFail($file);
        $ownerId = optional(optional($hf->message)->ticket)->user_id;

        $authorised = $request->hasValidSignature()
            || Auth::guard('admin')->check()
            || ($ownerId !== null && $this->isViewer($ownerId));

        abort_unless($authorised, 403);
        abort_if(! $hf->attachment, 404);

        return $this->stream('helpdesk/' . $hf->attachment);
    }

    /**
     * A contract / milestone proof file. Viewable by the contract's employer or
     * worker, an admin, or via a signed URL.
     */
    public function contractProof(Request $request, int $contract, ?int $milestone = null)
    {
        $row = Contract::findOrFail($contract);

        $authorised = $request->hasValidSignature()
            || Auth::guard('admin')->check()
            || $this->isViewer($row->employer_id)
            || $this->isViewer($row->worker_id);

        abort_unless($authorised, 403);

        if ($milestone !== null) {
            $ms = ContractMilestone::where('id', $milestone)->where('contract_id', $row->id)->firstOrFail();
            $filename = $ms->proof_file;
        } else {
            $filename = $row->proof_file;
        }

        abort_if(! $filename, 404);

        return $this->stream('contract-proofs/' . $filename);
    }

    /**
     * A work-submission proof file (by array index). Viewable by the work poster,
     * the worker who submitted it, an admin, or via a signed URL.
     */
    /**
     * Membership application documents (CV, cover letter, business registration).
     *
     * Admin only. Applicants have no account yet, so there is no owner to check
     * against, and these files contain personal data.
     */
    public function membershipDoc(Request $request, int $application, string $kind)
    {
        abort_unless(
            $request->hasValidSignature() || Auth::guard('admin')->check(),
            403
        );

        $app = \App\Models\MembershipApplication::findOrFail($application);

        $filename = match ($kind) {
            'resume'       => $app->resume_path,
            'cover'        => $app->cover_letter_path,
            'registration' => $app->business_registration_doc,
            default        => null,
        };

        abort_if(! $filename, 404);

        $path = trim(config('jobstation.upload_paths.membership_docs', 'uploads/membership/documents'), '/');

        return $this->stream($path . '/' . $filename);
    }

    /**
     * A task package file delivered to an assigned worker.
     */
    public function taskFile(Request $request, int $submission, int $index)
    {
        $row = \App\Models\WorkSubmission::findOrFail($submission);

        $authorised = $request->hasValidSignature()
            || Auth::guard('admin')->check()
            || $this->isViewer($row->worker_id);

        abort_unless($authorised, 403);

        $filename = ($row->task_files ?? [])[$index] ?? null;
        abort_if(! $filename, 404);

        $path = trim(config('jobstation.upload_paths.task_files', 'uploads/tasks/packages'), '/');

        return $this->stream($path . '/' . $filename);
    }

    public function workProof(Request $request, int $submission, int $index)
    {
        $row      = WorkSubmission::with('work')->findOrFail($submission);
        $posterId = $row->work_poster_id ?: optional($row->work)->poster_id;

        $authorised = $request->hasValidSignature()
            || Auth::guard('admin')->check()
            || $this->isViewer($row->worker_id)
            || ($posterId !== null && $this->isViewer($posterId));

        abort_unless($authorised, 403);

        $filename = ($row->proof_files ?? [])[$index] ?? null;
        abort_if(! $filename, 404);

        return $this->stream('work-proofs/' . $filename);
    }

    /**
     * True when the current web-session user or Sanctum-token user is $userId.
     */
    private function isViewer(?int $userId): bool
    {
        if ($userId === null) {
            return false;
        }

        return in_array($userId, [
            Auth::guard('web')->id(),
            optional(Auth::guard('sanctum')->user())->id,
        ], true);
    }

    /**
     * Stream a private file inline, never cached by shared proxies. 404 if missing.
     */
    private function stream(string $path)
    {
        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, [
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }
}
