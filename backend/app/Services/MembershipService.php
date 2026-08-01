<?php

namespace App\Services;

use App\Models\MembershipApplication;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MembershipService
{
    /**
     * Approve an application and mint the user account.
     *
     * The account and the log row are written in one transaction. The credentials
     * email is sent AFTER the commit on purpose: if the mail server is down we
     * still want the account to exist, and admin can resend. The reverse (email
     * sent, account rolled back) would tell someone to log in to an account that
     * does not exist.
     *
     * @throws ApplicationException
     */
    public function approve(MembershipApplication $app, ?int $adminId = null): User
    {
        if ($app->status !== MembershipApplication::STATUS_PENDING) {
            throw new ApplicationException('This application has already been reviewed.');
        }

        if (User::where('email', $app->email)->exists()) {
            throw new ApplicationException(
                'A user account already exists with the email ' . $app->email . '.'
            );
        }

        // Generated outside the transaction so we still hold it after commit.
        $tempPassword = $this->generateTempPassword();

        $user = DB::transaction(function () use ($app, $adminId, $tempPassword) {
            $user = new User();
            $user->forceFill([
                'firstname'            => $this->firstName($app->full_name),
                'lastname'             => $this->lastName($app->full_name),
                'username'             => $this->generateUsername($app->full_name, $app->email),
                'email'                => $app->email,
                'mobile'               => $app->phone,
                'password'             => Hash::make($tempPassword),
                'status'               => 1,
                'email_verified'       => 1,
                'user_type'            => $app->applicant_type,
                'account_type'         => 1,
                'must_change_password' => true,
                'kyc_status'           => 0,
                'onboarding_step'      => 0,
            ])->save();

            $app->update([
                'status'      => MembershipApplication::STATUS_APPROVED,
                'reviewed_by' => $adminId,
                'reviewed_at' => now(),
            ]);

            ActivityLogger::log('membership.approve', $app, [
                'admin_id'       => $adminId,
                'created_user_id' => $user->id,
                'applicant_type' => $app->applicant_type,
                'email'          => $app->email,
            ]);

            return $user;
        });

        $this->sendCredentials($user, $app, $tempPassword);

        return $user;
    }

    /**
     * @throws ApplicationException
     */
    public function reject(MembershipApplication $app, string $reason, ?int $adminId = null): void
    {
        if ($app->status !== MembershipApplication::STATUS_PENDING) {
            throw new ApplicationException('This application has already been reviewed.');
        }

        DB::transaction(function () use ($app, $reason, $adminId) {
            $app->update([
                'status'           => MembershipApplication::STATUS_REJECTED,
                'rejection_reason' => $reason,
                'reviewed_by'      => $adminId,
                'reviewed_at'      => now(),
            ]);

            ActivityLogger::log('membership.reject', $app, [
                'admin_id' => $adminId,
                'reason'   => $reason,
                'email'    => $app->email,
            ]);
        });

        $this->sendRejection($app, $reason);
    }

    // -------------------------------------------------------------------------
    // Mail
    // -------------------------------------------------------------------------

    protected function sendCredentials(User $user, MembershipApplication $app, string $tempPassword): void
    {
        try {
            NotifyService::send($user, 'MEMBERSHIP_APPROVED', [
                'full_name'      => $app->full_name,
                'username'       => $user->username,
                'email'          => $user->email,
                'temp_password'  => $tempPassword,
                'login_url'      => route('user.login'),
                'reference_code' => $app->reference_code,
                'site_name'      => gs()->site_name ?? config('app.name'),
            ]);
        } catch (\Throwable $e) {
            // The account exists either way. Surface this loudly so admin knows to
            // resend rather than leaving the applicant wondering.
            Log::error('Membership approval email failed', [
                'application_id' => $app->id,
                'user_id'        => $user->id,
                'error'          => $e->getMessage(),
            ]);
        }
    }

    protected function sendRejection(MembershipApplication $app, string $reason): void
    {
        try {
            // Rejected applicants have no User row, so this goes out directly
            // rather than through NotifyService, which is user-keyed.
            $subject = 'Update on your membership application';
            $body    = 'Hello ' . e($app->full_name) . ',<br><br>'
                     . 'Thank you for applying. After review we are not able to approve your '
                     . 'application at this time.<br><br>'
                     . '<strong>Reason:</strong> ' . e($reason) . '<br><br>'
                     . 'Reference: ' . e($app->reference_code);

            NotifyService::sendRawEmail($app->email, $app->full_name, $subject, $body);
        } catch (\Throwable $e) {
            Log::error('Membership rejection email failed', [
                'application_id' => $app->id,
                'error'          => $e->getMessage(),
            ]);
        }
    }

    // -------------------------------------------------------------------------
    // Account details
    // -------------------------------------------------------------------------

    /**
     * Mixed-case, digit-bearing temp password. Long enough that it is not worth
     * guessing, and the user is forced to replace it on first login anyway.
     */
    public function generateTempPassword(int $length = 14): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
        $out      = '';
        for ($i = 0; $i < $length; $i++) {
            $out .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        // Guarantee at least one digit and one uppercase for password policies.
        return substr($out, 0, $length - 2) . random_int(0, 9) . 'X';
    }

    protected function generateUsername(string $fullName, string $email): string
    {
        $base = Str::slug(Str::before($fullName ?: $email, ' '), '');
        $base = preg_replace('/[^a-z0-9]/i', '', $base) ?: 'member';
        $base = strtolower(substr($base, 0, 24));

        $candidate = $base;
        $suffix    = 0;

        while (User::where('username', $candidate)->exists()) {
            $suffix++;
            $candidate = substr($base, 0, 24 - strlen((string) $suffix)) . $suffix;

            if ($suffix > 9999) {
                $candidate = 'member' . bin2hex(random_bytes(4));
                break;
            }
        }

        return $candidate;
    }

    protected function firstName(string $fullName): string
    {
        return substr(trim(Str::before(trim($fullName), ' ')) ?: $fullName, 0, 40);
    }

    protected function lastName(string $fullName): string
    {
        $rest = trim(Str::after(trim($fullName), ' '));
        return substr($rest, 0, 40);
    }
}
