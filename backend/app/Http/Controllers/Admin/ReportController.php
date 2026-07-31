<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LedgerEntry;
use App\Models\NotificationLog;
use App\Models\UserSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        // Redirect to transactions as default
        return redirect()->route('admin.reports.transactions');
    }

    public function transactions(Request $request)
    {
        $query = LedgerEntry::with('user');

        if ($request->filled('type')) {
            $query->where('entry_type', $request->input('type'));
        }

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->input('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->input('to'));
        }

        $entries = $query->latest()->paginate(config('jobstation.per_page', 20))->withQueryString();

        $summary = [
            'total_credits' => LedgerEntry::where('entry_type', '+')
                ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->input('from')))
                ->when($request->filled('to'),   fn ($q) => $q->whereDate('created_at', '<=', $request->input('to')))
                ->sum('coins'),
            'total_debits'  => LedgerEntry::where('entry_type', '-')
                ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->input('from')))
                ->when($request->filled('to'),   fn ($q) => $q->whereDate('created_at', '<=', $request->input('to')))
                ->sum('coins'),
        ];

        $categories = config('jobstation.ledger_categories', []);

        return view('admin.reports.transactions', compact('entries', 'summary', 'categories'));
    }

    public function logins(Request $request)
    {
        $query = UserSession::with('user');

        if ($search = $request->input('search')) {
            $query->whereHas('user', fn ($q) =>
                $q->where('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
            );
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->input('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->input('to'));
        }

        $sessions = $query->latest()->paginate(config('jobstation.per_page', 20))->withQueryString();

        return view('admin.reports.logins', compact('sessions'));
    }

    public function notifications(Request $request)
    {
        $query = NotificationLog::with('user');

        if ($request->filled('type')) {
            $query->where('notification_type', $request->input('type'));
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('sent_to', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%")
                  ->orWhereHas('user', fn ($u) =>
                      $u->where('username', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                  );
            });
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->input('to'));
        }

        $logs = $query->latest()->paginate(config('jobstation.per_page', 20))->withQueryString();

        $types = NotificationLog::select('notification_type')
            ->distinct()->pluck('notification_type');

        return view('admin.reports.notifications', compact('logs', 'types'));
    }
}
