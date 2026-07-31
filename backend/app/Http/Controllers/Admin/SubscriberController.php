<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscriber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class SubscriberController extends Controller
{
    public function index(Request $request)
    {
        $query = Subscriber::latest();

        if ($search = $request->input('search')) {
            $query->where('email', 'like', "%{$search}%");
        }

        $subscribers = $query->paginate(config('jobstation.per_page', 20))->withQueryString();

        return view('admin.subscribers.index', compact('subscribers'));
    }

    public function destroy(int $id)
    {
        Subscriber::findOrFail($id)->delete();
        return back()->with('success', 'Subscriber removed.');
    }

    public function sendEmail(Request $request)
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
        ]);

        $subscribers = Subscriber::pluck('email');

        foreach ($subscribers as $email) {
            try {
                Mail::raw($data['message'], function ($msg) use ($email, $data) {
                    $msg->to($email)->subject($data['subject']);
                });
            } catch (\Exception $e) {
                // continue on individual failure
            }
        }

        return back()->with('success', 'Email sent to ' . $subscribers->count() . ' subscribers.');
    }
}
