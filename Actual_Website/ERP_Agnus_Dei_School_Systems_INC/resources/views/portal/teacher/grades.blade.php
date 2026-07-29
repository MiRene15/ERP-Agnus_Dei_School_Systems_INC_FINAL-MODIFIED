@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('teacher.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <a href="{{ route('teacher.classes') }}" class="no-underline" style="color: var(--muted);">My Classes</a>
    <span class="opacity-40">/</span>
    <span class="current">{{ $class->subject->name ?? 'Class' }}</span>
@endsection

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">{{ $class->subject->name ?? 'N/A' }}</h2>
        <p class="text-gray-600 mt-1">{{ $class->grade_level }} - {{ $class->section }} | {{ $class->subject->subject_code ?? '' }}</p>
    </div>
    <div class="text-sm text-gray-500 bg-gray-50 px-3 py-2 rounded-lg">
        {{ $activeEnrollments->count() }} student(s)
    </div>
</div>

@if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
@endif

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <form method="GET" class="mb-6">
        <div class="flex items-center gap-3">
            <label class="text-sm font-medium text-gray-700">Grading Period:</label>
            <select name="grading_period" onchange="this.form.submit()" class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                @foreach($gradingPeriods as $period)
                <option value="{{ $period }}" {{ $selectedPeriod === $period ? 'selected' : '' }}>{{ $period }}</option>
                @endforeach
            </select>
        </div>
    </form>

    @if($activeEnrollments->isEmpty())
        <p class="text-sm text-gray-500 text-center py-4">No active students enrolled in this class.</p>
    @else
    <form method="POST" action="{{ route('teacher.grades.store', $class) }}">
        @csrf
        <input type="hidden" name="grading_period" value="{{ $selectedPeriod }}">
        <div class="overflow-x-auto mb-4">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="text-left py-3 px-2 font-medium text-gray-600 w-12">#</th>
                        <th class="text-left py-3 px-2 font-medium text-gray-600">Student</th>
                        <th class="text-left py-3 px-2 font-medium text-gray-600">LRN</th>
                        <th class="text-center py-3 px-2 font-medium text-gray-600 w-28">Final Grade</th>
                        <th class="text-center py-3 px-2 font-medium text-gray-600 w-24">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($activeEnrollments as $idx => $enrollment)
                    @php
                        $grade = $existingGrades->get($enrollment->id);
                        $isSubmitted = $grade && $grade->status === 'Submitted';
                    @endphp
                    <tr class="border-b border-gray-100">
                        <td class="py-2 px-2 text-gray-400">{{ $idx + 1 }}</td>
                        <td class="py-2 px-2">
                            <span class="font-medium text-gray-900">{{ $enrollment->student->first_name }} {{ $enrollment->student->last_name }}</span>
                        </td>
                        <td class="py-2 px-2 text-gray-600">{{ $enrollment->student->student_number ?? 'N/A' }}</td>
                        <td class="py-2 px-2 text-center">
                            @if($isSubmitted)
                                <span class="font-semibold {{ $grade->final_grade >= 75 ? 'text-green-600' : 'text-red-600' }}">{{ number_format($grade->final_grade, 2) }}</span>
                            @else
                                <input type="number" name="grades[{{ $enrollment->id }}]" value="{{ $grade ? $grade->final_grade : '' }}" step="0.01" min="0" max="100" class="w-24 text-center rounded-lg border border-gray-300 px-2 py-1 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                            @endif
                        </td>
                        <td class="py-2 px-2 text-center">
                            @if($grade)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                    {{ $grade->status === 'Submitted' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                                    {{ $grade->status }}
                                </span>
                            @else
                                <span class="text-xs text-gray-400">Not set</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="flex items-center gap-2">
            <button type="submit" class="px-5 py-2 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">Save Grades</button>
            <button type="submit" form="submitForm" class="px-5 py-2 rounded-lg text-sm font-semibold text-white bg-green-600 hover:bg-green-700 transition">Submit All</button>
        </div>
    </form>
    <form id="submitForm" method="POST" action="{{ route('teacher.grades.submit', $class) }}" onsubmit="return confirm('Submit all grades for {{ $selectedPeriod }}? This cannot be undone.')">
        @csrf
        <input type="hidden" name="grading_period" value="{{ $selectedPeriod }}">
    </form>
    @endif
</div>
@endsection
