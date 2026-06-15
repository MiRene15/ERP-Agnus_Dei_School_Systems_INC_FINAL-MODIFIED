@extends('portal.layouts.app')

@section('breadcrumbs')
    <span class="current">Registrar Dashboard</span>
@endsection

@section('sidebar-links')
    <a href="{{ route('registrar.admissions.index') }}" class="sidebar-link">
        <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        <span class="sidebar-label">Admissions Queue</span>
    </a>
    <a href="#" class="sidebar-link">
        <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
        <span class="sidebar-label">Student Records</span>
    </a>
@endsection

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">Registrar Office</h2>
    <p class="text-gray-600 mt-1">Review student applications, manage academic records, and process enrollments.</p>
</div>

@if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
@endif

<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
    <a href="{{ route('registrar.admissions.index') }}" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition block">
        <h3 class="text-lg font-bold text-gray-900 mb-2">Pending Admissions</h3>
        <p class="text-4xl font-bold text-blue-600">{{ $pendingCount }}</p>
        <p class="text-sm text-gray-500 mt-2">Awaiting document verification</p>
    </a>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-2">Currently Enrolled</h3>
        <p class="text-4xl font-bold text-green-600">{{ $enrolledCount }}</p>
        <p class="text-sm text-gray-500 mt-2">Active enrollments this school year</p>
    </div>
</div>

@if($recentAdmissions->isNotEmpty())
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <h3 class="font-semibold text-gray-900 mb-4">Recent Applications</h3>
    <div class="divide-y divide-gray-50">
        @foreach($recentAdmissions as $admission)
        <div class="py-3 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-xs font-bold text-blue-700">
                    {{ substr($admission->student->first_name, 0, 1) }}{{ substr($admission->student->last_name, 0, 1) }}
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-900">{{ $admission->student->first_name }} {{ $admission->student->last_name }}</p>
                    <p class="text-xs text-gray-500">{{ $admission->application_type }} &middot; {{ $admission->grade_level }}{{ $admission->strand ? ' — '.$admission->strand : '' }} &middot; {{ $admission->school_year }} &middot; {{ $admission->created_at->diffForHumans() }}</p>
                </div>
            </div>
            <a href="{{ route('registrar.admissions.show', $admission) }}" class="text-sm text-blue-600 hover:text-blue-800 font-medium">Review</a>
        </div>
        @endforeach
    </div>
    @if($pendingCount > 5)
    <div class="mt-3 text-center">
        <a href="{{ route('registrar.admissions.index') }}" class="text-sm text-blue-600 hover:text-blue-800 font-medium">View all {{ $pendingCount }} pending admissions &rarr;</a>
    </div>
    @endif
</div>
@endif
@endsection
