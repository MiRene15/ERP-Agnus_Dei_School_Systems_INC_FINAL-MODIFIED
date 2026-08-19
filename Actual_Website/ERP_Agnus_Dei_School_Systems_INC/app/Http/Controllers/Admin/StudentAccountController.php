<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Student;
use Illuminate\Http\Request;

class StudentAccountController extends Controller
{
    public function index(Request $request)
    {
        $isAjax = $request->boolean('ajax');
        $request->query->remove('ajax');
        $query = User::with('student')->where('role_id', 7);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $users = $query->orderBy('name')->paginate(20)->withQueryString();

        if ($isAjax) {
            return response()->json([
                'html' => view('portal.admin.partials.student-accounts-results', compact('users'))->render(),
            ]);
        }

        return view('portal.admin.student-accounts.index', compact('users'));
    }

    public function toggleStatus(User $user)
    {
        $user->status = $user->status === 'active' ? 'inactive' : 'active';
        $user->save();

        $label = $user->status === 'active' ? 'activated' : 'deactivated';
        log_activity($user, 'Status Changed', "Student account status changed to {$label} for {$user->name}");
        return redirect()->route('admin.student-accounts.index')
            ->with('success', "Account for {$user->name} has been {$label}.");
    }

    public function resetPassword(User $user)
    {
        $rawPassword = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::random(4)) . rand(100, 999) . '!' . \Illuminate\Support\Str::random(3);
        $user->update(['password' => \Illuminate\Support\Facades\Hash::make($rawPassword)]);

        log_activity($user, 'Password Reset', "Password reset for student {$user->name}");
        return redirect()->route('admin.student-accounts.index')
            ->with('success', "Password reset for {$user->name}. New temporary password: <strong>{$rawPassword}</strong>");
    }
}
