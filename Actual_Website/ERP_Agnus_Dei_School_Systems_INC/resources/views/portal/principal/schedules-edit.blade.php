@extends('portal.layouts.app')
@section('breadcrumbs')
    <a href="{{ route('principal.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a><span class="opacity-40"> / </span><a href="{{ route('principal.schedules') }}" class="no-underline" style="color: var(--muted);">Schedules</a><span class="opacity-40"> / </span><span class="current">Edit</span>
@endsection
@section('content')
<div class="mb-6"><h2 class="text-2xl font-bold text-gray-900 dark:text-[#E8EAF6]">Edit Schedule</h2><p class="text-sm text-gray-500 dark:text-[#8A90B0]">{{ $schedule->schoolClass->subject->name ?? 'Class' }} — {{ $schedule->schoolClass->section ?? '' }} ({{ $schedule->schoolClass->grade_level ?? '' }}) — {{ $schedule->schoolClass->teacher->name ?? 'No teacher' }}</p></div>
@if(session('error'))<div class="mb-4 p-3 bg-red-50 dark:bg-[rgba(248,113,113,0.12)] border border-red-200 dark:border-[rgba(248,113,113,0.25)] rounded text-sm text-red-700 dark:text-[#F87171]">{{ session('error') }}</div>@endif
<div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-6 max-w-lg">
    <form method="POST" action="{{ route('principal.schedules.update', $schedule) }}">
        @csrf @method('PATCH')
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 dark:text-[#C1C4DC] mb-1">Day *</label>
            <select name="day_of_week" required class="w-full rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-3 py-2 text-sm"><option value="Monday" {{ $schedule->day_of_week==='Monday'?'selected':'' }}>Monday</option><option value="Tuesday" {{ $schedule->day_of_week==='Tuesday'?'selected':'' }}>Tuesday</option><option value="Wednesday" {{ $schedule->day_of_week==='Wednesday'?'selected':'' }}>Wednesday</option><option value="Thursday" {{ $schedule->day_of_week==='Thursday'?'selected':'' }}>Thursday</option><option value="Friday" {{ $schedule->day_of_week==='Friday'?'selected':'' }}>Friday</option></select>
        </div>
        <div class="grid grid-cols-2 gap-4 mb-4">
            <div><label class="block text-sm font-medium text-gray-700 dark:text-[#C1C4DC] mb-1">Start *</label><input type="time" name="start_time" value="{{ substr($schedule->start_time,0,5) }}" required class="w-full rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-3 py-2 text-sm"></div>
            <div><label class="block text-sm font-medium text-gray-700 dark:text-[#C1C4DC] mb-1">End *</label><input type="time" name="end_time" value="{{ substr($schedule->end_time,0,5) }}" required class="w-full rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-3 py-2 text-sm"></div>
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 dark:text-[#C1C4DC] mb-1">Room</label>
            <input type="text" name="room" value="{{ $schedule->room }}" maxlength="50" class="w-full rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-3 py-2 text-sm" placeholder="e.g. J-101">
        </div>
        <div class="flex gap-2">
            <button type="submit" class="px-5 py-2 rounded-lg text-sm font-semibold text-white" style="background: var(--navy);">Save Changes</button>
            <a href="{{ route('principal.schedules') }}" class="px-5 py-2 rounded-lg text-sm font-semibold bg-gray-100 dark:bg-[#23274C] hover:bg-gray-200 dark:hover:bg-[#2A2F58] text-gray-700 dark:text-[#C1C4DC]">Cancel</a>
        </div>
    </form>
    <form method="POST" action="{{ route('principal.schedules.destroy', $schedule) }}" onsubmit="return confirm('Delete this schedule? This action will be audit logged.');" class="mt-6 pt-6 border-t border-gray-100 dark:border-[#2A2F58]">
        @csrf @method('DELETE')
        <button type="submit" class="px-4 py-2 rounded-lg text-sm font-semibold bg-red-50 dark:bg-[rgba(248,113,113,0.12)] text-red-600 dark:text-[#F87171] border border-red-200 dark:border-[rgba(248,113,113,0.25)] hover:bg-red-100">Delete Schedule</button>
    </form>
</div>
@endsection
