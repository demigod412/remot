<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentChannel;
use Illuminate\Http\Request;

class PaymentChannelController extends Controller
{
    public function index()
    {
        $channels = PaymentChannel::withCount(['rates'])->orderBy('name')->get();
        return view('admin.payment-channels.index', compact('channels'));
    }

    public function edit(int $id)
    {
        $channel = PaymentChannel::findOrFail($id);
        return view('admin.payment-channels.edit', compact('channel'));
    }

    public function update(Request $request, int $id)
    {
        $channel = PaymentChannel::findOrFail($id);

        $data = $request->validate([
            'instructions' => ['nullable', 'string', 'max:1000'],
        ]);

        // Handle credentials as key-value pairs
        if ($request->filled('credential_keys')) {
            $keys   = $request->input('credential_keys', []);
            $values = $request->input('credential_values', []);
            $creds  = [];
            foreach ($keys as $i => $key) {
                if ($key !== '') {
                    $creds[$key] = $values[$i] ?? '';
                }
            }
            $data['credentials'] = $creds;
        }

        $channel->update($data);

        return redirect()->route('admin.payment-channels.index')
            ->with('success', "Payment channel \"{$channel->name}\" updated.");
    }

    public function toggleStatus(int $id)
    {
        $channel = PaymentChannel::findOrFail($id);
        $channel->status = $channel->status ? 0 : 1;
        $channel->save();
        return back()->with('success', 'Channel status updated.');
    }
}
