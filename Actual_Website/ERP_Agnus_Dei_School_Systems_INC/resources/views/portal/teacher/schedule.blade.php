@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('teacher.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <span class="current">My Schedule</span>
@endsection

@section('sidebar-links')
    <a href="{{ route('teacher.dashboard') }}" class="sidebar-link"><svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg><span class="sidebar-label">Dashboard</span></a>
    <a href="{{ route('teacher.classes') }}" class="sidebar-link"><svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg><span class="sidebar-label">My Classes</span></a>
    <a href="{{ route('teacher.schedule') }}" class="sidebar-link"><svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg><span class="sidebar-label">My Schedule</span></a>
@endsection

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">My Weekly Schedule</h2>
    <p class="text-gray-600 mt-1">{{ active_school_year() }}</p>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 overflow-x-auto">
    <div class="grid grid-cols-5 gap-3 min-w-[700px]">
        @foreach($weekDays as $day)
        <div>
            <div class="text-center font-bold text-sm text-gray-700 bg-gray-50 rounded-lg py-2 mb-2 border border-gray-100">{{ $day }}</div>
            <div class="space-y-2">
                @forelse($schedulesByDay[$day] as $slot)
                <div class="p-3 rounded-lg border border-blue-100 bg-blue-50 text-xs">
                    <p class="font-semibold text-gray-900">{{ \Carbon\Carbon::parse($slot->start_time)->format('h:i A') }} - {{ \Carbon\Carbon::parse($slot->end_time)->format('h:i A') }}</p>
                    <p class="text-gray-700 mt-1">{{ $slot->schoolClass->subject->name ?? 'N/A' }}</p>
                    <p class="text-gray-500">{{ $slot->schoolClass->grade_level }} - {{ $slot->schoolClass->section }}</p>
                    <p class="text-gray-400">{{ $slot->room ?? $slot->schoolClass->room ?? 'N/A' }}</p>
                </div>
                @empty
                <div class="p-3 rounded-lg bg-gray-50 border border-dashed border-gray-200 text-center">
                    <p class="text-xs text-gray-400">No class</p>
                </div>
                @endforelse
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection