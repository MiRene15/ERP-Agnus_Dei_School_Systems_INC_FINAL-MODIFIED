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
    protected function normalizePhone(?string $raw): ?string
    {
        if (!$raw || trim($raw) === '') return null;
        $digits = preg_replace('/[^0-9]/', '', $raw);
        if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            return '+63' . substr($digits, 1);
        }
        if (strlen($digits) === 10 && str_starts_with($digits, '9')) {
            return '+63' . $digits;
        }
        if (strlen($digits) === 12 && str_starts_with($digits, '63')) {
            return '+' . $digits;
        }
        if (strlen($digits) === 13 && str_starts_with($digits, '63')) {
            return '+' . $digits;
        }
        return $raw;
    }

    // List all staff accounts (excludes student role 7)
    public function index(Request $request)
    {
        $isAjax = $request->boolean('ajax');
        $request->query->remove('ajax');
        $query = User::with('role')->whereNotIn('role_id', [7]);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role_id')) {
            $query->where('role_id', $request->role_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $users = $query->orderBy('role_id')->paginate(20)->withQueryString();
        $roles = Role::whereNotIn('id', [7])->get();

        if ($isAjax) {
            return response()->json([
                'html' => view('portal.admin.partials.users-index-results', compact('users'))->render(),
            ]);
        }

        return view('portal.admin.users.index', compact('users', 'roles'));
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
            'contact_number' => 'nullable|string|max:15',
        ]);

        // Generate a strong randomized password
        $rawPassword = Str::upper(Str::random(4)) . rand(100, 999) . '!' . Str::random(3);

        $newUser = User::create([
            'name'           => $request->name,
            'email'          => $request->email,
            'role_id'        => $request->role_id,
            'contact_number' => $this->normalizePhone($request->contact_number),
            'password'       => Hash::make($rawPassword),
            'status'         => 'active',
        ]);

        log_activity($newUser, 'Created', "Created staff account: {$request->name}");

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
            'contact_number' => 'nullable|string|max:15',
        ]);

        $user->update([
            'name'           => $request->name,
            'email'          => $request->email,
            'role_id'        => $request->role_id,
            'contact_number' => $this->normalizePhone($request->contact_number),
        ]);

        log_activity($user, 'Updated', "Updated staff account: {$user->name}");

        return redirect()->route('admin.users.index')
            ->with('success', "Account for {$user->name} has been updated.");
    }

    // Toggle Active / Inactive status
    public function toggleStatus(User $user)
    {
        $user->status = $user->status === 'active' ? 'inactive' : 'active';
        $user->save();

        $label = $user->status === 'active' ? 'activated' : 'deactivated';
        log_activity($user, 'Status Changed', "Account status changed to {$label} for {$user->name}");
        return redirect()->route('admin.users.index')
            ->with('success', "Account for {$user->name} has been {$label}.");
    }

    // Reset password with new random one
    public function resetPassword(User $user)
    {
        $rawPassword = Str::upper(Str::random(4)) . rand(100, 999) . '!' . Str::random(3);
        $user->update(['password' => Hash::make($rawPassword)]);

        log_activity($user, 'Password Reset', "Password reset for {$user->name}");
        return redirect()->route('admin.users.index')
            ->with('success', "Password reset for {$user->name}. New temporary password: <strong>{$rawPassword}</strong>");
    }
}
