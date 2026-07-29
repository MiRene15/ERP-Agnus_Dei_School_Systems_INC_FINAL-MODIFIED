@extends('portal.layouts.app')

@section('breadcrumbs')
    <span class="current">Teacher Dashboard</span>
@endsection

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">Faculty Portal</h2>
    <p class="text-gray-600 mt-1">Manage your classes, submit grades, and view your schedule.</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <p class="text-sm text-gray-500">Assigned Classes</p>
        <p class="text-3xl font-bold text-gray-900 mt-1">{{ $classes->count() }}</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <p class="text-sm text-gray-500">Total Students</p>
        <p class="text-3xl font-bold text-gray-900 mt-1">{{ $totalStudents }}</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <p class="text-sm text-gray-500">Today's Sessions</p>
        <p class="text-3xl font-bold text-gray-900 mt-1">{{ $todaySchedule->count() }}</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-4">My Classes</h3>
        @if($classes->isEmpty())
            <p class="text-sm text-gray-500">No classes assigned.</p>
        @else
        <div class="space-y-2">
            @foreach($classes as $class)
            <a href="{{ route('teacher.classes.show', $class) }}" class="flex items-center justify-between p-3 bg-gray-50 hover:bg-blue-50 rounded-lg transition group">
                <div>
                    <p class="font-medium text-gray-900 group-hover:text-blue-700">{{ $class->subject->name ?? 'N/A' }}</p>
                    <p class="text-xs text-gray-500">{{ $class->grade_level }} - {{ $class->section }} | {{ $class->schedules->count() }} session(s)</p>
                </div>
                <svg class="w-4 h-4 text-gray-400 group-hover:text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
            @endforeach
        </div>
        @endif
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-4">Today's Schedule <span class="text-sm font-normal text-gray-500">({{ now()->format('l, F d') }})</span></h3>
        @if($todaySchedule->isEmpty())
            <p class="text-sm text-gray-500">No classes scheduled today.</p>
        @else
        <div class="space-y-2">
            @foreach($todaySchedule as $slot)
            <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg border border-blue-100">
                <div class="flex items-center gap-3">
                    <span class="text-blue-800 font-bold text-sm w-20">{{ \Carbon\Carbon::parse($slot->start_time)->format('h:i A') }}</span>
                    <span class="font-medium text-gray-800 text-sm">{{ $slot->schoolClass->subject->name ?? 'N/A' }}</span>
                </div>
                <span class="text-xs font-medium text-gray-500">{{ $slot->room ?? $slot->schoolClass->room ?? 'N/A' }}</span>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>
@endsection
