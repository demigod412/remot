<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MembershipApplication;
use App\Services\ApplicationException;
use App\Services\MembershipService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MembershipApplicationController extends Controller
{
    public function __construct(protected MembershipService $memberships)
    {
    }

    public function index(Request $request)
    {
        $query = MembershipApplication::query();

        if ($request->filled('status')) {
            $query->where('status', (int) $request->input('status'));
        }

        if ($request->filled('applicant_type')) {
            $query->where('applicant_type', (int) $request->input('applicant_type'));
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('reference_code', 'like', "%{$search}%")
                  ->orWhere('business_name', 'like', "%{$search}%");
            });
        }

        $applications = $query->latest()
            ->paginate(config('jobstation.per_page', 20))
            ->withQueryString();

        $stats = [
            'total'    => MembershipApplication::count(),
            'pending'  => MembershipApplication::pending()->count(),
            'approved' => MembershipApplication::approved()->count(),
            'rejected' => MembershipApplication::rejected()->count(),
        ];

        return view('admin.membership.index', compact('applications', 'stats'));
    }

    public function show(int $id)
    {
        $application = MembershipApplication::with('reviewer')->findOrFail($id);

        return view('admin.membership.show', compact('application'));
    }

    public function approve(int $id)
    {
        $application = MembershipApplication::findOrFail($id);

        try {
            $user = $this->memberships->approve($application, Auth::guard('admin')->id());
        } catch (ApplicationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with(
            'success',
            "Approved. Account created for {$user->email} (username {$user->username}) and login details emailed."
        );
    }

    public function reject(Request $request, int $id)
    {
        $data = $request->validate([
            'rejection_reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $application = MembershipApplication::findOrFail($id);

        try {
            $this->memberships->reject(
                $application,
                $data['rejection_reason'],
                Auth::guard('admin')->id()
            );
        } catch (ApplicationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Application rejected and the applicant notified.');
    }
}
