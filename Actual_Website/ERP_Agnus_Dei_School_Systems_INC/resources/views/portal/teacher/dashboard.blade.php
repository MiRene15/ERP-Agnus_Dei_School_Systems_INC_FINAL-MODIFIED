@extends('portal.layouts.app')

@section('breadcrumbs')
    <span class="current">Teacher Dashboard</span>
@endsection

@section('sidebar-links')
    <a href="#" class="sidebar-link">
        <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
        <span class="sidebar-label">My Classes</span>
    </a>
    <a href="#" class="sidebar-link">
        <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
        <span class="sidebar-label">Grading System</span>
    </a>
@endsection

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">Faculty Portal</h2>
    <p class="text-gray-600 mt-1">Manage your advisory classes, submit grades, and view your schedule.</p>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
    <h3 class="text-lg font-bold text-gray-900 mb-4">Today's Schedule</h3>
    <div class="space-y-3">
        <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg border border-blue-100">
            <div class="flex items-center">
                <span class="text-blue-800 font-bold w-24">08:00 AM</span>
                <span class="ml-4 font-medium text-gray-800">Science 10 (St. Matthew)</span>
            </div>
            <span class="text-sm font-medium text-gray-500">Room 302</span>
        </div>
        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
            <div class="flex items-center">
                <span class="text-gray-600 font-bold w-24">10:30 AM</span>
                <span class="ml-4 font-medium text-gray-800">Chemistry 11 (St. Luke)</span>
            </div>
            <span class="text-sm font-medium text-gray-500">Lab A</span>
        </div>
    </div>
</div>
@endsection
