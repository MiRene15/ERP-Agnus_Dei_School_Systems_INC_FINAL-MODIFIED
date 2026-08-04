<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ForceChangePasswordController extends Controller
{
    public function show(): View
    {
        return view('auth.force-change-password');
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('forcePassword', [
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
            'first_login_at' => now(),
        ]);

        log_activity($request->user(), 'Password Changed', 'First-login password changed successfully.');

        $role = $request->user()->role_id;
        $url = match($role) {
            1 => '/admin/dashboard',
            2 => '/registrar/dashboard',
            3 => '/cashier/dashboard',
            4 => '/teacher/dashboard',
            5 => '/librarian/dashboard',
            6 => '/nurse/dashboard',
            7 => '/student/dashboard',
            default => '/dashboard',
        };

        return redirect()->to($url)->with('success', 'Your password has been updated successfully.');
    }
}
