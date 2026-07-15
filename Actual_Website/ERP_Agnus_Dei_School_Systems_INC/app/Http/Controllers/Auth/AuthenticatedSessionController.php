<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = $request->user();
        if (!$user->first_login_at) {
            $user->first_login_at = Carbon::now();
        }
        $user->last_login_at = Carbon::now();
        $user->save();

        $role = $user->role_id;
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

        return redirect()->intended($url);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
