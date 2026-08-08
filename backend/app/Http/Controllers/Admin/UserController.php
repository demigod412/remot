<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use App\Models\LedgerEntry;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\WorkerReliabilityService;
use App\Models\WorkSubmission;
use App\Services\NotifyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    // -------------------------------------------------------------------------
    // User List
    // -------------------------------------------------------------------------

    public function index(Request $request)
    {
        $query = User::query();

        // Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('firstname', 'like', "%{$search}%")
                  ->orWhere('lastname', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('mobile', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // KYC filter
        if ($request->filled('kyc')) {
            $query->where('kyc_status', $request->input('kyc'));
        }

        $users = $query->latest()->paginate(config('jobstation.per_page', 20))->withQueryString();

        $totalUsers  = User::count();
        $activeUsers = User::where('status', 1)->count();
        $bannedUsers = User::where('status', 0)->count();
        $kycPending  = User::where('kyc_status', 2)->count();

        return view('admin.users.index', compact(
            'users', 'totalUsers', 'activeUsers', 'bannedUsers', 'kycPending'
        ));
    }

    // -------------------------------------------------------------------------
    // KYC Pending List
    // -------------------------------------------------------------------------

    public function kycList(Request $request)
    {
        $users = User::where('kyc_status', 2)
            ->latest()
            ->paginate(config('jobstation.per_page', 20))
            ->withQueryString();

        $selectedUser = $users->first();
        if ($request->filled('review')) {
            $candidate = User::find($request->input('review'));
            if ($candidate && $candidate->kyc_status === 2) {
                $selectedUser = $candidate;
            }
        }

        $approvedRecent = User::where('kyc_status', 1)->where('updated_at', '>=', now()->subDays(7))->count();
        $rejectedRecent = User::where('kyc_status', 0)->where('updated_at', '>=', now()->subDays(7))->count();

        return view('admin.users.kyc', compact('users', 'selectedUser', 'approvedRecent', 'rejectedRecent'));
    }

    // -------------------------------------------------------------------------
    // Show User
    // -------------------------------------------------------------------------

    public function show(int $id)
    {
        $user = User::findOrFail($id);

        $stats = [
            'total_earned'     => LedgerEntry::where('user_id', $id)->where('entry_type', '+')->sum('coins'),
            'total_spent'      => LedgerEntry::where('user_id', $id)->where('entry_type', '-')->sum('coins'),
            'total_submissions'=> WorkSubmission::where('worker_id', $id)->count(),
            'approved_submissions' => WorkSubmission::where('worker_id', $id)->where('status', 2)->count(),
            'rejected_submissions' => WorkSubmission::where('worker_id', $id)->where('status', 3)->count(),
            'works_posted'     => \App\Models\Work::where('poster_id', $id)->where('poster_type', 2)->count(),
            'referrals'        => User::where('referred_by', $id)->count(),
        ];

        $recentLedger = LedgerEntry::where('user_id', $id)
            ->latest()
            ->limit(10)
            ->get();

        $recentSubmissions = WorkSubmission::with('work')
            ->where('worker_id', $id)
            ->latest()
            ->limit(5)
            ->get();

        $reliabilityService = app(WorkerReliabilityService::class);
        $reliability = $reliabilityService->summary($user);
        $performance = $reliabilityService->performance($user);

        // Eager loaded so the card does not issue its own query per render.
        $user->load('skills');

        return view('admin.users.show', compact(
            'user', 'stats', 'recentLedger', 'recentSubmissions', 'reliability', 'performance'
        ));
    }

    // -------------------------------------------------------------------------
    // Update User (basic info)
    // -------------------------------------------------------------------------

    public function update(Request $request, int $id)
    {
        $user = User::findOrFail($id);

        $data = $request->validate([
            'firstname' => ['required', 'string', 'max:50'],
            'lastname'  => ['required', 'string', 'max:50'],
            'username'  => ['required', 'string', 'max:50', "unique:users,username,{$id}"],
            'email'     => ['required', 'email', 'max:100', "unique:users,email,{$id}"],
            'mobile'    => ['nullable', 'string', 'max:20'],
        ]);

        $user->update($data);

        return back()->with('success', 'User updated successfully.');
    }

    // -------------------------------------------------------------------------
    // Ban / Unban
    // -------------------------------------------------------------------------

    public function toggleStatus(Request $request, int $id)
    {
        $user = User::findOrFail($id);

        if ($user->status == 1) {
            $request->validate(['ban_reason' => ['nullable', 'string', 'max:255']]);
            $user->status     = 0;
            $user->ban_reason = $request->input('ban_reason');
            $user->save();

            // Synchronous, not queued. A queued job leaves a window in which a banned
            // user's pending withdrawal could still be approved and paid, which is
            // precisely what this rule exists to prevent.
            $cancelled = app(\App\Services\WithdrawalPolicy::class)->cancelPendingFor($user);

            $msg = "User {$user->username} has been banned.";

            if ($cancelled > 0) {
                $msg .= " {$cancelled} pending withdrawal(s) cancelled. The balance was NOT returned —"
                     . " reverse it by hand from the cashout record if that is the intention.";
            }
        } else {
            $user->status     = 1;
            $user->ban_reason = null;
            $user->save();

            $msg = "User {$user->username} has been unbanned.";
        }

        // Irreversible from the user's point of view, so it gets an audit row.
        ActivityLogger::log(
            $user->status == 0 ? 'user.ban' : 'user.unban',
            $user,
            [
                'username' => $user->username,
                'reason'   => $user->ban_reason,
            ]
        );

        // Notify admin
        AdminNotification::notify(
            Auth::guard('admin')->id(),
            'User Status Changed',
            $msg,
            'user',
            route('admin.users.show', $user->id)
        );

        return back()->with('success', $msg);
    }

    // -------------------------------------------------------------------------
    // Adjust Coin Balance
    // -------------------------------------------------------------------------

    public function adjustBalance(Request $request, int $id)
    {
        $user = User::findOrFail($id);

        $data = $request->validate([
            'type'   => ['required', 'in:add,deduct'],
            'amount' => ['required', 'numeric', 'min:1'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $amount = (float) $data['amount'];

        DB::transaction(function () use ($user, $data, $amount) {
            if ($data['type'] === 'add') {
                $user->increment('coin_balance', $amount);
                $entryType = '+';
            } else {
                if ($user->coin_balance < $amount) {
                    throw new \Exception('Insufficient balance to deduct.');
                }
                $user->decrement('coin_balance', $amount);
                $entryType = '-';
            }

            $user->refresh();

            LedgerEntry::create([
                'user_id'       => $user->id,
                'coins'         => $amount,
                'fee'           => 0,
                'balance_after' => $user->coin_balance,
                'entry_type'    => $entryType,
                'reference'     => generateReference(),
                'description'   => 'Admin adjustment: ' . $data['reason'],
                'category'      => 'admin',
            ]);
        });

        ActivityLogger::logMoney(
            $data['type'] === 'add' ? 'user.credit' : 'user.debit',
            $user,
            $amount,
            $user->id,
            [
                'reason'        => $data['reason'],
                'balance_after' => (float) $user->fresh()->coin_balance,
            ]
        );

        $action = $data['type'] === 'add' ? 'added to' : 'deducted from';
        return back()->with('success', formatCoins($amount) . " {$action} {$user->username}'s balance.");
    }

    // -------------------------------------------------------------------------
    // KYC Approve
    // -------------------------------------------------------------------------

    public function kycApprove(Request $request, int $id)
    {
        $user = User::findOrFail($id);

        if ($user->kyc_status !== 2) {
            return back()->with('error', 'No pending KYC request for this user.');
        }

        $user->kyc_status = 1;
        $user->save();

        AdminNotification::notify(
            Auth::guard('admin')->id(),
            'KYC Approved',
            "KYC for {$user->username} has been approved.",
            'success',
            route('admin.users.show', $user->id)
        );

        NotifyService::send($user, 'KYC_APPROVED');

        return back()->with('success', 'KYC approved successfully.');
    }

    // -------------------------------------------------------------------------
    // KYC Reject
    // -------------------------------------------------------------------------

    public function kycReject(Request $request, int $id)
    {
        $user = User::findOrFail($id);

        $data = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ]);

        if ($user->kyc_status !== 2) {
            return back()->with('error', 'No pending KYC request for this user.');
        }

        // Store rejection reason in kyc_data
        $kycData = $user->kyc_data ?? [];
        $kycData['rejection_reason'] = $data['rejection_reason'];

        $user->kyc_status = 0;
        $user->kyc_data   = $kycData;
        $user->save();

        AdminNotification::notify(
            Auth::guard('admin')->id(),
            'KYC Rejected',
            "KYC for {$user->username} has been rejected.",
            'warning',
            route('admin.users.show', $user->id)
        );

        NotifyService::send($user, 'KYC_REJECTED', [
            'reason' => $data['rejection_reason'],
        ]);

        return back()->with('success', 'KYC rejected.');
    }

    // -------------------------------------------------------------------------
    // KYC Request Documents
    // -------------------------------------------------------------------------

    public function kycRequestDocs(Request $request, int $id)
    {
        $user = User::findOrFail($id);

        $data = $request->validate([
            'request_message' => ['nullable', 'string', 'max:500'],
        ]);

        $message = $data['request_message'] ?? 'Please re-upload your KYC documents. Ensure images are clear and all details are visible.';

        // Send in-app notification to user
        \App\Models\UserNotification::notify(
            $user->id,
            'warning',
            'KYC Documents Requested',
            $message,
            route('user.profile.kyc'),
            'shield-alert'
        );

        NotifyService::send($user, 'KYC_REQUEST_DOCS', ['message' => $message]);

        return back()->with('success', 'Document request sent to ' . $user->fullname . '.');
    }

    // -------------------------------------------------------------------------
    // KYC Approve Single Document
    // -------------------------------------------------------------------------

    public function kycApproveDoc(Request $request, int $id)
    {
        $user = User::findOrFail($id);

        $data = $request->validate([
            'doc_side' => ['required', 'in:front,back'],
        ]);

        $kycData = $user->kyc_data ?? [];
        $key = $data['doc_side'] . '_approved';
        $kycData[$key] = true;
        $user->kyc_data = $kycData;

        // Auto-approve KYC when both sides are marked approved
        if (!empty($kycData['front_approved']) && !empty($kycData['back_approved'])) {
            $user->kyc_status = 1;
            $user->save();

            AdminNotification::notify(
                Auth::guard('admin')->id(),
                'KYC Auto-Approved',
                "KYC for {$user->username} auto-approved after both documents verified.",
                'success',
                route('admin.users.show', $user->id)
            );

            NotifyService::send($user, 'KYC_APPROVED');

            return back()->with('success', 'Both documents approved — KYC fully verified.');
        }

        $user->save();

        $side = ucfirst($data['doc_side']);
        return back()->with('success', "{$side} document marked as approved.");
    }

    // -------------------------------------------------------------------------
    // Login As User (impersonate)
    // -------------------------------------------------------------------------

    /**
     * Forgive a worker's reliability strikes.
     *
     * Sets users.strikes_cleared_at rather than editing any submission, so the
     * underlying history stays intact and auditable.
     */
    public function clearStrikes(int $id)
    {
        $user = User::findOrFail($id);

        app(WorkerReliabilityService::class)
            ->clearStrikes($user, Auth::guard('admin')->id());

        return back()->with('success', "Reliability strikes cleared for {$user->username}.");
    }

    public function loginAsUser(int $id)
    {
        $user = User::findOrFail($id);

        // Store admin ID in session so we can return later
        session(['impersonate_admin_id' => Auth::guard('admin')->id()]);

        Auth::guard('web')->login($user);

        return redirect()->route('user.dashboard')
            ->with('info', "You are now logged in as {$user->username}. Close tab or logout to return.");
    }

    // -------------------------------------------------------------------------
    // Notify Single User
    // -------------------------------------------------------------------------

    public function notifyForm(int $id)
    {
        $user = User::findOrFail($id);
        return view('admin.users.notify', compact('user'));
    }

    public function sendNotification(Request $request, int $id)
    {
        $user = User::findOrFail($id);

        $data = $request->validate([
            'subject' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        NotifyService::sendEmailTo($user, $data['subject'], $data['message']);

        return redirect()->route('admin.users.show', $user->id)
            ->with('success', 'Notification sent.');
    }

    // -------------------------------------------------------------------------
    // Bulk Notify All Users
    // -------------------------------------------------------------------------

    public function bulkNotifyForm()
    {
        $totalUsers = User::where('status', 1)->where('email_verified', 1)->count();
        return view('admin.users.notify-bulk', compact('totalUsers'));
    }

    public function sendBulkNotification(Request $request)
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:2000'],
            'target'  => ['required', 'in:all,active,verified'],
        ]);

        $query = User::query();

        if ($data['target'] === 'active') {
            $query->where('status', 1);
        } elseif ($data['target'] === 'verified') {
            $query->where('status', 1)->where('email_verified', 1);
        }

        $users = $query->get();
        $count = $users->count();

        foreach ($users as $recipient) {
            NotifyService::sendEmailTo($recipient, $data['subject'], $data['message']);
        }

        AdminNotification::notify(
            Auth::guard('admin')->id(),
            'Bulk Notification Queued',
            "Notification queued for {$count} users.",
            'info'
        );

        return redirect()->route('admin.users.index')
            ->with('success', "Notification queued for {$count} users.");
    }
}
