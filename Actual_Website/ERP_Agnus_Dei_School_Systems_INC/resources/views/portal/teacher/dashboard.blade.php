@extends('portal.layouts.app')

@section('breadcrumbs')
    <span class="current">Teacher Dashboard</span>
@endsection

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">Faculty Portal</h2>
    <p class="text-gray-600 mt-1">Manage your classes, submit grades, and view your schedule.</p>
</div>

@if(!auth()->user()->has_seen_welcome)
<div x-data="{ show: true }" x-show="show" x-transition class="mb-4 p-5 bg-gradient-to-r from-orange-50 to-amber-50 border border-orange-200 rounded-xl">
    <div class="flex items-start gap-4">
        <div class="w-12 h-12 rounded-full bg-orange-100 flex items-center justify-center flex-shrink-0">
            <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div class="flex-1">
            <h3 class="font-bold text-gray-900">Welcome to the Faculty Portal!</h3>
            <p class="text-sm text-gray-600 mt-1">View your classes, manage student grades, and check your daily schedule. Click a class to open its grade sheet.</p>
            <div class="flex gap-2 mt-3">
                <a href="{{ route('teacher.schedule') }}" class="text-xs font-semibold text-orange-700 hover:text-orange-900 underline">View Schedule &rarr;</a>
            </div>
        </div>
        <button @click="show = false; fetch('/dismiss-welcome', {method:'POST', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'}})" class="text-gray-400 hover:text-gray-600 flex-shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
</div>
@endif

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
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6" x-data="{ search: '', grade: '' }">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-gray-900">My Classes</h3>
        </div>
        <div class="flex gap-2 mb-4">
            <input type="text" x-model="search" placeholder="Search subject..."
                   class="flex-1 px-3 py-1.5 rounded-lg border border-gray-300 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
            <select x-model="grade"
                    class="px-3 py-1.5 rounded-lg border border-gray-300 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                <option value="">All Grades</option>
                @foreach($classes->pluck('grade_level')->unique()->sort() as $gl)
                <option value="{{ $gl }}">{{ $gl }}</option>
                @endforeach
            </select>
        </div>
        @if($classes->isEmpty())
            <p class="text-sm text-gray-500">No classes assigned.</p>
        @else
        <div class="space-y-2">
            @foreach($classes as $class)
            <a href="{{ route('teacher.classes.show', $class) }}"
               class="flex items-center justify-between p-3 bg-gray-50 hover:bg-blue-50 rounded-lg transition group"
               x-show="(search === '' || '{{ strtolower($class->subject->name ?? '') }}'.includes(search.toLowerCase())) && (grade === '' || '{{ $class->grade_level }}' === grade)">
                <div>
                    <p class="font-medium text-gray-900 group-hover:text-blue-700">{{ $class->subject->name ?? 'N/A' }}</p>
                    <p class="text-xs text-gray-500">{{ $class->grade_level }} - {{ $class->section }} | {{ $class->schedules->count() }} session(s)</p>
                </div>
                <svg class="w-4 h-4 text-gray-400 group-hover:text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
            @endforeach
            <template x-if="search !== '' || grade !== ''">
                <div x-show="document.querySelectorAll('[x-show]:not([x-show=__x_false])').length === 0" class="hidden">
                    <p class="text-sm text-gray-400 text-center py-3">No classes match your filter.</p>
                </div>
            </template>
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
