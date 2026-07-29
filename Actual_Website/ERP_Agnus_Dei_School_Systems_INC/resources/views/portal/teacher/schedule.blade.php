@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('teacher.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <span class="current">My Schedule</span>
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