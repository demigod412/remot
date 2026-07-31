<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PayoutMethod;
use Illuminate\Http\Request;

class PayoutMethodController extends Controller
{
    public function index()
    {
        $methods = PayoutMethod::withCount('cashouts')->orderBy('name')->get();
        return view('admin.payout-methods.index', compact('methods'));
    }

    public function create()
    {
        return view('admin.payout-methods.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'                  => ['required', 'string', 'max:100'],
            'currency'              => ['required', 'string', 'max:10'],
            'min_coins'             => ['required', 'numeric', 'min:0'],
            'max_coins'             => ['required', 'numeric', 'min:0'],
            'coin_to_currency_rate' => ['required', 'numeric', 'min:0'],
            'fixed_fee'             => ['nullable', 'numeric', 'min:0'],
            'percent_fee'           => ['nullable', 'numeric', 'min:0', 'max:100'],
            'description'           => ['nullable', 'string', 'max:500'],
        ]);
        $data['fixed_fee']   = $data['fixed_fee'] ?? 0;
        $data['percent_fee'] = $data['percent_fee'] ?? 0;
        $data['status']      = 1;

        PayoutMethod::create($data);
        return redirect()->route('admin.payout-methods.index')->with('success', 'Payout method created.');
    }

    public function edit(int $id)
    {
        $method = PayoutMethod::findOrFail($id);
        return view('admin.payout-methods.edit', compact('method'));
    }

    public function update(Request $request, int $id)
    {
        $method = PayoutMethod::findOrFail($id);
        $data   = $request->validate([
            'name'                  => ['required', 'string', 'max:100'],
            'currency'              => ['required', 'string', 'max:10'],
            'min_coins'             => ['required', 'numeric', 'min:0'],
            'max_coins'             => ['required', 'numeric', 'min:0'],
            'coin_to_currency_rate' => ['required', 'numeric', 'min:0'],
            'fixed_fee'             => ['nullable', 'numeric', 'min:0'],
            'percent_fee'           => ['nullable', 'numeric', 'min:0', 'max:100'],
            'description'           => ['nullable', 'string', 'max:500'],
        ]);
        $data['fixed_fee']   = $data['fixed_fee'] ?? 0;
        $data['percent_fee'] = $data['percent_fee'] ?? 0;
        $method->update($data);
        return redirect()->route('admin.payout-methods.index')->with('success', 'Payout method updated.');
    }

    public function toggleStatus(int $id)
    {
        $method = PayoutMethod::findOrFail($id);
        $method->status = $method->status ? 0 : 1;
        $method->save();
        return back()->with('success', 'Payout method status updated.');
    }
}
