<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ForceChangePasswordController extends Controller
{
    public function update(Request $request)
    {
        $validated = $request->validateWithBag('forcePassword', [
            'password' => ['required', Password::min(8)->letters()->mixedCase()->numbers()->symbols(), 'confirmed'],
        ]);

        $user = $request->user();
        $user->password = $validated['password'];
        $user->first_login_at = now();
        $user->save();
        $request->session()->regenerate();

        log_activity($user, 'Password Changed', 'First-login password changed successfully.');

        if ($request->expectsJson() || $request->ajax() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json(['success' => true, 'message' => 'Your new password has been set.']);
        }

        return redirect()->route('dashboard')->with('success', 'Your new password has been set. Welcome!');
    }
}
