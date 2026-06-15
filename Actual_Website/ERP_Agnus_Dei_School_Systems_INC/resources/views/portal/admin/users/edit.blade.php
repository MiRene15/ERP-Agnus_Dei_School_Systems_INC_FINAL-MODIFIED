@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('admin.dashboard') }}">Dashboard</a>
    <span class="sep">›</span>
    <a href="{{ route('admin.users.index') }}">Staff Accounts</a>
    <span class="sep">›</span>
    <span class="current">Edit: {{ $user->name }}</span>
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
@endsection

@section('content')
<div class="max-w-2xl">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-900">Edit Staff Account</h2>
        <p class="text-gray-500 mt-1 text-sm">Update the profile information for <strong>{{ $user->name }}</strong>.</p>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8">
        <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Full Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">School Email Address <span class="text-red-500">*</span></label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                @error('email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Assign Role <span class="text-red-500">*</span></label>
                <select name="role_id" required class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}" {{ $user->role_id == $role->id ? 'selected' : '' }}>{{ $role->name }}</option>
                    @endforeach
                </select>
                @error('role_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Contact Number</label>
                <input type="text" name="contact_number" value="{{ old('contact_number', $user->contact_number) }}"
                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="px-6 py-2.5 bg-blue-700 text-white font-semibold rounded-lg shadow-sm hover:bg-blue-800 transition-colors text-sm">
                    Save Changes
                </button>
                <a href="{{ route('admin.users.index') }}" class="px-6 py-2.5 bg-gray-100 text-gray-700 font-semibold rounded-lg hover:bg-gray-200 transition-colors text-sm">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
