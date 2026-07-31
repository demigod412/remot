<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LedgerEntry;
use Illuminate\Http\Request;

class LedgerController extends Controller
{
    public function index(Request $request)
    {
        $query = LedgerEntry::with('user');

        if ($request->filled('type')) {
            $query->where('entry_type', $request->input('type'));
        }

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('user', fn ($u) =>
                      $u->where('username', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                  );
            });
        }

        $entries = $query->latest()->paginate(config('jobstation.per_page', 20))->withQueryString();

        $categories = config('jobstation.ledger_categories', []);

        $stats = [
            'total_credits' => LedgerEntry::where('entry_type', '+')->sum('coins'),
            'total_debits'  => LedgerEntry::where('entry_type', '-')->sum('coins'),
            'total_entries' => LedgerEntry::count(),
        ];

        return view('admin.ledger.index', compact('entries', 'categories', 'stats'));
    }
}
