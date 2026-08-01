<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/**
 * Forced password change for accounts created by admin approval.
 */
class PasswordChangeController extends Controller
{
    public function show()
    {
        return view('user.auth.force-password-change');
    }

    public function update(Request $request)
    {
        $user = Auth::guard('web')->user();

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        if (! Hash::check($data['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => 'That temporary password is not correct.']);
        }

        if (Hash::check($data['password'], $user->password)) {
            return back()->withErrors(['password' => 'Please choose a password different from the temporary one.']);
        }

        $user->forceFill([
            'password'             => Hash::make($data['password']),
            'must_change_password' => false,
        ])->save();

        return redirect()->route('user.dashboard')
            ->with('success', 'Password updated. Welcome aboard.');
    }
}
