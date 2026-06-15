<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserController extends Controller
{
    // List all staff accounts (excludes student role 7)
    public function index()
    {
        $users = User::with('role')
            ->whereNotIn('role_id', [7])
            ->orderBy('role_id')
            ->get();

        return view('portal.admin.users.index', compact('users'));
    }

    // Show create form
    public function create()
    {
        $roles = Role::whereNotIn('id', [7])->get(); // Staff roles only
        return view('portal.admin.users.create', compact('roles'));
    }

    // Store new staff account with randomized password
    public function store(Request $request)
    {
        $request->validate([
            'name'         => 'required|string|max:255',
            'email'        => 'required|email|unique:users,email',
            'role_id'      => 'required|exists:roles,id',
            'contact_number' => 'nullable|string|max:20',
        ]);

        // Generate a strong randomized password
        $rawPassword = Str::upper(Str::random(4)) . rand(100, 999) . '!' . Str::random(3);

        User::create([
            'name'           => $request->name,
            'email'          => $request->email,
            'role_id'        => $request->role_id,
            'contact_number' => $request->contact_number,
            'password'       => Hash::make($rawPassword),
            'status'         => 'active',
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', "Account created! Temporary password: <strong>{$rawPassword}</strong>. Please share this securely with the staff member.");
    }

    // Show edit form
    public function edit(User $user)
    {
        $roles = Role::whereNotIn('id', [7])->get();
        return view('portal.admin.users.edit', compact('user', 'roles'));
    }

    // Update user info
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name'           => 'required|string|max:255',
            'email'          => 'required|email|unique:users,email,' . $user->id,
            'role_id'        => 'required|exists:roles,id',
            'contact_number' => 'nullable|string|max:20',
        ]);

        $user->update($request->only('name', 'email', 'role_id', 'contact_number'));

        return redirect()->route('admin.users.index')
            ->with('success', "Account for {$user->name} has been updated.");
    }

    // Toggle Active / Inactive status
    public function toggleStatus(User $user)
    {
        $user->status = $user->status === 'active' ? 'inactive' : 'active';
        $user->save();

        $label = $user->status === 'active' ? 'activated' : 'deactivated';
        return redirect()->route('admin.users.index')
            ->with('success', "Account for {$user->name} has been {$label}.");
    }

    // Reset password with new random one
    public function resetPassword(User $user)
    {
        $rawPassword = Str::upper(Str::random(4)) . rand(100, 999) . '!' . Str::random(3);
        $user->update(['password' => Hash::make($rawPassword)]);

        return redirect()->route('admin.users.index')
            ->with('success', "Password reset for {$user->name}. New temporary password: <strong>{$rawPassword}</strong>");
    }
}
