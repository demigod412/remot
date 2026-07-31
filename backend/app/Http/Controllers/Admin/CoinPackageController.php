<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CoinPackage;
use Illuminate\Http\Request;

class CoinPackageController extends Controller
{
    public function index()
    {
        $packages = CoinPackage::orderBy('price')->get();
        return view('admin.coin-packages.index', compact('packages'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:100'],
            'coins'       => ['required', 'integer', 'min:1'],
            'price'       => ['required', 'numeric', 'min:0.01'],
            'currency'    => ['required', 'string', 'max:10'],
            'bonus_coins' => ['nullable', 'integer', 'min:0'],
            'badge_label' => ['nullable', 'string', 'max:30'],
            'is_popular'  => ['nullable', 'boolean'],
        ]);
        $data['bonus_coins'] = $data['bonus_coins'] ?? 0;
        $data['is_popular']  = $request->boolean('is_popular');
        $data['status']      = 1;

        CoinPackage::create($data);
        return back()->with('success', 'Coin package created.');
    }

    public function update(Request $request, int $id)
    {
        $pkg  = CoinPackage::findOrFail($id);
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:100'],
            'coins'       => ['required', 'integer', 'min:1'],
            'price'       => ['required', 'numeric', 'min:0.01'],
            'currency'    => ['required', 'string', 'max:10'],
            'bonus_coins' => ['nullable', 'integer', 'min:0'],
            'badge_label' => ['nullable', 'string', 'max:30'],
            'is_popular'  => ['nullable', 'boolean'],
        ]);
        $data['bonus_coins'] = $data['bonus_coins'] ?? 0;
        $data['is_popular']  = $request->boolean('is_popular');
        $pkg->update($data);
        return back()->with('success', 'Coin package updated.');
    }

    public function destroy(int $id)
    {
        CoinPackage::findOrFail($id)->delete();
        return back()->with('success', 'Coin package deleted.');
    }

    public function toggleStatus(int $id)
    {
        $pkg = CoinPackage::findOrFail($id);
        $pkg->status = $pkg->status ? 0 : 1;
        $pkg->save();
        return back()->with('success', 'Package status updated.');
    }
}
