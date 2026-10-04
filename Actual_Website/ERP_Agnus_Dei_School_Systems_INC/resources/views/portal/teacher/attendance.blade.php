@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('teacher.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <a href="{{ route('teacher.classes') }}" class="no-underline" style="color: var(--muted);">My Classes</a>
    <span class="opacity-40">/</span>
    <span class="current">Attendance</span>
@endsection

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900 dark:text-[#E8EAF6]">Attendance — {{ $class->subject->name ?? 'N/A' }}</h2>
    <p class="text-gray-600 dark:text-[#C1C4DC] mt-1">{{ $class->grade_level }} - {{ $class->section }} | Mark each student once per day. Saving again overwrites that day.</p>
</div>

@if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm">
        <ul class="list-disc list-inside">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif

<div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-6 mb-6">
    <form method="GET" action="{{ route('teacher.attendance', $class) }}" class="flex items-center gap-3 flex-wrap">
        <label class="text-sm font-medium text-gray-700 dark:text-[#C1C4DC]">Date:</label>
        <input type="date" name="date" value="{{ $markedOn }}" min="1987-01-01" max="{{ date('Y-m-d') }}" onchange="this.form.submit()"
               class="rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
        @if($recentDates->isNotEmpty())
        <span class="text-xs text-gray-400">Marked days: {{ $recentDates->map(fn($d) => \Carbon\Carbon::parse($d)->format('M d'))->implode(', ') }}</span>
        @endif
    </form>
</div>

@if($activeEnrollments->isEmpty())
<div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-6 text-center">
    <p class="text-sm text-gray-500 py-4">No active students enrolled in this class.</p>
</div>
@else
<form method="POST" action="{{ route('teacher.attendance.store', $class) }}">
    @csrf
    <input type="hidden" name="marked_on" value="{{ $markedOn }}">
    <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-[#2A2F58] bg-gray-50 dark:bg-[#161A33]">
                        <th class="text-left py-3 px-4 font-medium text-gray-600 dark:text-[#C1C4DC]">Student</th>
                        <th class="text-center py-3 px-2 font-medium text-gray-600 dark:text-[#C1C4DC]">Present</th>
                        <th class="text-center py-3 px-2 font-medium text-gray-600 dark:text-[#C1C4DC]">Absent</th>
                        <th class="text-center py-3 px-2 font-medium text-gray-600 dark:text-[#C1C4DC]">Late</th>
                        <th class="text-center py-3 px-2 font-medium text-gray-600 dark:text-[#C1C4DC]">Excused</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($activeEnrollments as $enrollment)
                    @php $current = $existing[$enrollment->id]->status ?? 'present'; @endphp
                    <tr class="border-b border-gray-100 dark:border-[#2A2F58] hover:bg-gray-50 dark:hover:bg-[#1E2447]">
                        <td class="py-2 px-4">
                            <span class="font-medium text-gray-900 dark:text-[#E8EAF6]">{{ $enrollment->student->first_name }} {{ $enrollment->student->last_name }}</span>
                            <span class="block text-xs text-gray-400">{{ $enrollment->student->student_number }}</span>
                        </td>
                        @foreach(['present' => 'green', 'absent' => 'red', 'late' => 'amber', 'excused' => 'blue'] as $value => $color)
                        <td class="py-2 px-2 text-center">
                            <input type="radio" name="status[{{ $enrollment->id }}]" value="{{ $value }}" {{ $current === $value ? 'checked' : '' }} required
                                   class="w-4 h-4 text-{{ $color }}-600 focus:ring-{{ $color }}-500">
                        </td>
                        @endforeach
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-gray-100 dark:border-[#2A2F58] flex justify-end">
            <button type="submit" class="px-6 py-2.5 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">Save Attendance for {{ \Carbon\Carbon::parse($markedOn)->format('M d, Y') }}</button>
        </div>
    </div>
</form>
@endif
@endsection
