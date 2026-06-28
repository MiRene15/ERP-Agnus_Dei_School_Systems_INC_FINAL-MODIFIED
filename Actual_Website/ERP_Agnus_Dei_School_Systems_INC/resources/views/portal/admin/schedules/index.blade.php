@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('admin.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <span class="current">Schedule Management</span>
@endsection

@section('sidebar-links')
    <a href="{{ route('admin.dashboard') }}" class="sidebar-link"><svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg><span class="sidebar-label">Dashboard</span></a>
    <a href="{{ route('admin.schedules.index') }}" class="sidebar-link"><svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg><span class="sidebar-label">Schedules</span></a>
    <a href="{{ route('admin.pending-accounts') }}" class="sidebar-link"><svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg><span class="sidebar-label">IT Confirmations</span></a>
    <a href="{{ route('admin.users.index') }}" class="sidebar-link"><svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg><span class="sidebar-label">Staff Accounts</span></a>
@endsection

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Schedule Management</h2>
        <p class="text-gray-600 mt-1">Manage classes, assign teachers, and set schedules per grade level and section.</p>
    </div>
    <a href="{{ route('admin.schedules.create') }}" class="px-4 py-2 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">+ Create Class</a>
</div>

@if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
@endif

@forelse($classes as $gradeLevel => $gradeClasses)
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-4">
    <h3 class="font-semibold text-gray-900 mb-3">{{ $gradeLevel }}</h3>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Subject</th>
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Section</th>
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Teacher</th>
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Semester</th>
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Room</th>
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Schedules</th>
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($gradeClasses as $class)
                <tr class="border-b border-gray-100">
                    <td class="py-3 px-2 font-medium text-gray-900">{{ $class->subject->name ?? 'N/A' }}</td>
                    <td class="py-3 px-2 text-gray-700">{{ $class->section }}</td>
                    <td class="py-3 px-2 text-gray-700">{{ $class->teacher->name ?? 'N/A' }}</td>
                    <td class="py-3 px-2 text-gray-700">{{ $class->semester }}</td>
                    <td class="py-3 px-2 text-gray-700">{{ $class->room ?? '—' }}</td>
                    <td class="py-3 px-2">
                        @if($class->schedules->isNotEmpty())
                            <span class="text-xs text-gray-500">{{ $class->schedules->count() }} slot(s)</span>
                        @else
                            <span class="text-xs text-red-500">No slots</span>
                        @endif
                    </td>
                    <td class="py-3 px-2">
                        <div class="flex gap-1">
                            <a href="{{ route('admin.schedules.slots', $class) }}" class="px-2 py-1 text-xs font-medium text-blue-600 hover:text-blue-800">Slots</a>
                            <a href="{{ route('admin.schedules.edit', $class) }}" class="px-2 py-1 text-xs font-medium text-gray-600 hover:text-gray-800">Edit</a>
                            <form method="POST" action="{{ route('admin.schedules.destroy', $class) }}" onsubmit="return confirm('Delete this class?')" class="inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="px-2 py-1 text-xs font-medium text-red-600 hover:text-red-800">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@empty
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <p class="text-sm text-gray-500 text-center py-4">No classes created yet. <a href="{{ route('admin.schedules.create') }}" class="text-blue-600 hover:text-blue-800 font-medium">Create one</a>.</p>
</div>
@endforelse
@endsection
