<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Deactivate (archive) the user's account instead of deleting it.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        $rules = [
            'password' => ['required', 'current_password'],
            'reason' => ['required', 'string', 'max:1000'],
        ];

        if ($user->student) {
            $rules['action'] = ['required', 'in:transfer,graduated'];
        } else {
            $rules['action'] = ['nullable', 'in:transfer,graduated'];
        }

        $request->validateWithBag('userDeletion', $rules);

        if ($user->student) {
            $user->student->status = 'archived';
            $user->student->archive_action = $request->input('action');
            $user->student->archive_reason = $request->input('reason');
            $user->student->archived_at = now();
            $user->student->save();
        }

        $user->status = 'inactive';
        $user->save();

        log_activity($user, 'Archived', 'Account archived: ' . $request->input('reason'), [
            'action' => $request->input('action'),
        ]);

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
