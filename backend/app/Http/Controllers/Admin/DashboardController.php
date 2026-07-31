<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use App\Models\CoinTopup;
use App\Models\Cashout;
use App\Models\LedgerEntry;
use App\Models\User;
use App\Models\Work;
use App\Models\WorkSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DashboardController extends Controller
{
    public function index()
    {
        // Summary counts
        $stats = [
            'total_users'       => User::count(),
            'active_users'      => User::where('status', 1)->count(),
            'total_works'       => Work::count(),
            'active_works'      => Work::where('work_status', 1)->where('approval_status', 1)->count(),
            'pending_works'     => Work::where('approval_status', 0)->count(),
            'total_submissions' => WorkSubmission::count(),
            'pending_topups'    => CoinTopup::where('status', 2)->count(),
            'pending_cashouts'  => Cashout::where('status', 0)->count(),
        ];

        // Total coins in circulation
        $stats['coins_credited'] = LedgerEntry::where('entry_type', '+')->sum('coins');
        $stats['coins_debited']  = LedgerEntry::where('entry_type', '-')->sum('coins');

        // Recent users (last 7)
        $recentUsers = User::latest()->limit(7)->get();

        // Recent works (last 7)
        $recentWorks = Work::with('category')->latest()->limit(7)->get();

        // Recent submissions (last 7)
        $recentSubmissions = WorkSubmission::with(['work', 'worker'])->latest()->limit(7)->get();

        // Chart: new users last 30 days
        $userChart = User::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->where('created_at', '>=', now()->subDays(29))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date');

        // Chart: coins credited last 30 days
        $coinChart = LedgerEntry::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(coins) as total')
            )
            ->where('entry_type', '+')
            ->where('created_at', '>=', now()->subDays(29))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date');

        // Chart: cashouts last 30 days
        $cashoutChart = Cashout::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(coin_amount) as total_coins')
            )
            ->where('created_at', '>=', now()->subDays(29))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        // Additional KPIs
        $pendingCashouts  = $stats['pending_cashouts'];
        $pendingSubs      = WorkSubmission::where('status', 1)->count();
        $pendingKyc       = User::where('kyc_status', 2)->count();
        $topCategories    = \App\Models\WorkCategory::withCount('works')->orderByDesc('works_count')->limit(5)->get();
        $maxCatCount      = $topCategories->max('works_count') ?: 1;
        $topUsers         = User::orderByDesc('coin_balance')->limit(5)->get();

        // Fill missing days
        $dates       = collect();
        $userData    = collect();
        $coinData    = collect();
        $cashoutData = collect();

        for ($i = 29; $i >= 0; $i--) {
            $d = now()->subDays($i)->format('Y-m-d');
            $dates->push(now()->subDays($i)->format('M d'));
            $userData->push($userChart[$d] ?? 0);
            $coinData->push($coinChart[$d] ?? 0);
            $cashoutData->push((int) ($cashoutChart[$d]->total_coins ?? 0));
        }

        return view('admin.dashboard', compact(
            'stats',
            'recentUsers',
            'recentWorks',
            'recentSubmissions',
            'dates',
            'userData',
            'coinData',
            'cashoutData',
            'pendingCashouts',
            'pendingSubs',
            'pendingKyc',
            'topCategories',
            'maxCatCount',
            'topUsers'
        ));
    }

    public function profile()
    {
        $admin = Auth::guard('admin')->user();
        return view('admin.profile', compact('admin'));
    }

    public function updateProfile(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        $data = $request->validate([
            'name'     => ['required', 'string', 'max:40'],
            'username' => ['required', 'string', 'max:40'],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
        ]);

        $admin->name     = $data['name'];
        $admin->username = $data['username'];

        if (!empty($data['password'])) {
            $admin->password = Hash::make($data['password']);
        }

        $admin->save();

        return back()->with('success', 'Profile updated successfully.');
    }

    public function notifications()
    {
        $notifications = AdminNotification::where('admin_id', Auth::guard('admin')->id())
            ->latest()
            ->paginate(20);

        AdminNotification::where('admin_id', Auth::guard('admin')->id())
            ->where('is_read', 0)
            ->update(['is_read' => 1]);

        return view('admin.notifications', compact('notifications'));
    }

    public function markRead(Request $request, int $id)
    {
        AdminNotification::where('id', $id)
            ->where('admin_id', Auth::guard('admin')->id())
            ->update(['is_read' => 1]);

        return back();
    }

    public function readAll(Request $request)
    {
        AdminNotification::where('admin_id', Auth::guard('admin')->id())
            ->update(['is_read' => 1]);

        return back()->with('success', 'All notifications marked as read.');
    }
}
