@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('admin.dashboard') }}">Dashboard</a>
    <span class="sep">›</span>
    <span class="current">Staff Accounts</span>
@endsection

@section('sidebar-links')
    <a href="{{ route('admin.dashboard') }}" class="sidebar-link">
        <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
        <span class="sidebar-label">Dashboard</span>
    </a>
    <a href="{{ route('admin.users.index') }}" class="sidebar-link active">
        <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
        <span class="sidebar-label">Staff Accounts</span>
    </a>
    <a href="#" class="sidebar-link">
        <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        <span class="sidebar-label">System Settings</span>
    </a>
@endsection

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Staff Account Management</h2>
        <p class="text-gray-500 mt-1 text-sm">Create, activate, deactivate, and reset passwords for all staff accounts.</p>
    </div>
    <a href="{{ route('admin.users.create') }}" class="inline-flex items-center gap-2 bg-blue-700 text-white font-semibold px-5 py-2.5 rounded-lg shadow-sm hover:bg-blue-800 transition-colors text-sm">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
        New Staff Account
    </a>
</div>

{{-- Flash Messages --}}
@if (session('success'))
    <div class="mb-5 p-4 rounded-xl bg-green-50 border border-green-200 text-green-800 text-sm font-medium flex items-start gap-3">
        <svg class="w-5 h-5 text-green-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        <span>{!! session('success') !!}</span>
    </div>
@endif

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
                <th class="text-left px-6 py-3 font-semibold text-gray-600 uppercase tracking-wide text-xs">Name</th>
                <th class="text-left px-6 py-3 font-semibold text-gray-600 uppercase tracking-wide text-xs">Email</th>
                <th class="text-left px-6 py-3 font-semibold text-gray-600 uppercase tracking-wide text-xs">Role</th>
                <th class="text-left px-6 py-3 font-semibold text-gray-600 uppercase tracking-wide text-xs">Status</th>
                <th class="text-left px-6 py-3 font-semibold text-gray-600 uppercase tracking-wide text-xs">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse ($users as $user)
            <tr class="hover:bg-gray-50/50 transition-colors">
                <td class="px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div class="h-9 w-9 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-sm border border-blue-200 shrink-0">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                        <span class="font-medium text-gray-800">{{ $user->name }}</span>
                    </div>
                </td>
                <td class="px-6 py-4 text-gray-500">{{ $user->email }}</td>
                <td class="px-6 py-4">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-800">
                        {{ $user->role?->name ?? 'N/A' }}
                    </span>
                </td>
                <td class="px-6 py-4">
                    @if ($user->status === 'active')
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                            <span class="w-1.5 h-1.5 rounded-full bg-green-500 inline-block"></span> Active
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800">
                            <span class="w-1.5 h-1.5 rounded-full bg-red-500 inline-block"></span> Inactive
                        </span>
                    @endif
                </td>
                <td class="px-6 py-4">
                    <div class="flex items-center gap-2 flex-wrap">
                        {{-- Edit --}}
                        <a href="{{ route('admin.users.edit', $user) }}" class="px-3 py-1.5 text-xs font-semibold text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">Edit</a>

                        {{-- Toggle Status --}}
                        <form method="POST" action="{{ route('admin.users.toggle-status', $user) }}" onsubmit="return confirm('Toggle account status for {{ $user->name }}?')">
                            @csrf
                            <button type="submit" class="px-3 py-1.5 text-xs font-semibold rounded-lg transition-colors {{ $user->status === 'active' ? 'text-red-700 bg-red-50 hover:bg-red-100' : 'text-green-700 bg-green-50 hover:bg-green-100' }}">
                                {{ $user->status === 'active' ? 'Deactivate' : 'Activate' }}
                            </button>
                        </form>

                        {{-- Reset Password --}}
                        <form method="POST" action="{{ route('admin.users.reset-password', $user) }}" onsubmit="return confirm('Reset password for {{ $user->name }}? The new password will be shown once.')">
                            @csrf
                            <button type="submit" class="px-3 py-1.5 text-xs font-semibold text-orange-700 bg-orange-50 rounded-lg hover:bg-orange-100 transition-colors">Reset PW</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="px-6 py-12 text-center text-gray-400">
                    <svg class="w-10 h-10 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    No staff accounts yet. Create the first one!
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
