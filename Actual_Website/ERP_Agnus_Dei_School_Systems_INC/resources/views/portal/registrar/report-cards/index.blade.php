@extends('portal.layouts.app')
@section('breadcrumbs')
    <a href="{{ route('registrar.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <span class="current">Report Cards</span>
@endsection

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">Report Cards</h2>
    <p class="text-gray-600 mt-1">View and print student report cards for {{ active_school_year() }}.</p>
</div>
@if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
@endif
<div class="mb-4 flex gap-2 flex-wrap items-center">
    <form method="GET" class="flex gap-2 flex-1 flex-wrap">
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="Search by student name or section..."
               class="flex-1 min-w-[200px] rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
        <select name="grade_level" class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
            <option value="All" {{ request('grade_level') === 'All' || !request('grade_level') ? 'selected' : '' }}>All Grade Levels</option>
            @foreach(['Kinder', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'] as $gl)
                <option value="{{ $gl }}" {{ request('grade_level') === $gl ? 'selected' : '' }}>{{ $gl }}</option>
            @endforeach
        </select>
        <button type="submit" class="px-4 py-2 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);">Search</button>
        @if(request()->anyFilled(['search', 'grade_level']))
            <a href="{{ url()->current() }}" class="px-4 py-2 rounded-lg text-sm font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 transition">Clear</a>
        @endif
    </form>
</div>
@if($enrollments->isEmpty())
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <p class="text-sm text-gray-500 text-center py-4">No active enrollments found for this school year.</p>
    </div>
@else
    @foreach($enrollments as $grade => $group)
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Grade {{ $grade }}</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="text-left py-3 px-2 font-medium text-gray-600">Student</th>
                        <th class="text-left py-3 px-2 font-medium text-gray-600">Section</th>
                        <th class="text-left py-3 px-2 font-medium text-gray-600">LRN</th>
                        <th class="text-right py-3 px-2 font-medium text-gray-600">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($group as $enrollment)
                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                        <td class="py-3 px-2">
                            <span class="font-medium text-gray-900">{{ $enrollment->student->first_name }} {{ $enrollment->student->last_name }}</span>
                        </td>
                        <td class="py-3 px-2 text-gray-700">{{ $enrollment->section->section_name ?? 'N/A' }}</td>
                        <td class="py-3 px-2 text-gray-700">{{ $enrollment->student->student_number ?? 'N/A' }}</td>
                        <td class="py-3 px-2 text-right">
                            <a href="{{ route('registrar.report-cards.show', $enrollment) }}" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-indigo-600 bg-indigo-50 hover:bg-indigo-100 rounded-lg mr-1">View</a>
                            <a href="{{ route('registrar.report-cards.print', $enrollment) }}" target="_blank" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg">Print PDF</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endforeach
@endif
@endsection