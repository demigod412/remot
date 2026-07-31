<?php

namespace App\Http\Controllers\User\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class ResetPasswordController extends Controller
{
    public function showForm()
    {
        if (! session('reset_verified')) {
            return redirect()->route('user.forgot-password')->with('error', 'Please verify your email first.');
        }
        return view('user.auth.reset-password');
    }

    public function reset(Request $request)
    {
        $email = session('reset_verified');
        if (! $email) {
            return redirect()->route('user.forgot-password')->with('error', 'Session expired. Please start again.');
        }

        $request->validate([
            'password' => ['required', 'confirmed', 'min:6'],
        ]);

        $user = User::where('email', $email)->firstOrFail();
        $user->update(['password' => $request->password]);

        $request->session()->forget(['reset_email', 'reset_verified']);

        return redirect()->route('user.login')
            ->with('success', 'Password reset successfully. Please log in.');
    }
}
