@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('teacher.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <span class="current">Grade Assessment</span>
@endsection

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">Grade Assessment</h2>
    <p class="text-gray-600 mt-1">Enter scores for Written Work, Quiz, Seatwork, and Exam per student.</p>
</div>

@if(!$selectedClassId)
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6" x-data="{ search: '', grade: '' }">
    <h3 class="font-semibold text-gray-900 mb-4">Select a Class</h3>
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
    <div class="space-y-2">
        @foreach($classes as $cls)
        <a href="{{ route('teacher.grade-assessment') }}?class_id={{ $cls->id }}&grading_period={{ $selectedPeriod }}"
           class="flex items-center justify-between p-3 bg-gray-50 hover:bg-blue-50 rounded-lg transition"
           x-show="(search === '' || '{{ strtolower($cls->subject->name ?? '') }}'.includes(search.toLowerCase())) && (grade === '' || '{{ $cls->grade_level }}' === grade)">
            <div>
                <p class="font-medium text-gray-900">{{ $cls->subject->name ?? 'N/A' }}</p>
                <p class="text-xs text-gray-500">{{ $cls->grade_level }} - {{ $cls->section }}</p>
            </div>
            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </a>
        @endforeach
    </div>
</div>
@else
<div class="mb-4 flex items-center gap-3">
    <a href="{{ route('teacher.grade-assessment') }}" class="text-sm text-blue-600 hover:underline">&larr; Change Class</a>
    <span class="text-gray-300">|</span>
    <h3 class="font-semibold text-gray-900">{{ $class->subject->name ?? 'N/A' }} — {{ $class->grade_level }} {{ $class->section }}</h3>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <div class="flex items-center gap-2 mb-4">
        <label class="text-sm font-medium text-gray-700">Grading Period:</label>
        <div class="flex gap-1">
            @foreach($gradingPeriods as $period)
            <a href="{{ route('teacher.grade-assessment') }}?class_id={{ $selectedClassId }}&grading_period={{ $period }}"
               class="px-3 py-1 rounded-lg text-sm font-medium transition {{ $selectedPeriod === $period ? 'text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}"
               style="{{ $selectedPeriod === $period ? 'background: var(--navy);' : '' }}">
                {{ $period }}
            </a>
            @endforeach
        </div>
    </div>

    @if($activeEnrollments->isEmpty())
    <p class="text-sm text-gray-500">No students enrolled.</p>
    @else
    <p class="text-sm text-gray-500 mb-3">Click a student to enter their assessment scores.</p>
    <div class="space-y-2">
        @foreach($activeEnrollments as $enrollment)
        @php
            $totalRaw = 0;
            $totalMax = 0;
            if(isset($existingAssessments[$enrollment->id])) {
                $totalRaw = $existingAssessments[$enrollment->id]->sum('raw_score');
                $totalMax = $existingAssessments[$enrollment->id]->sum('max_score');
            }
        @endphp
        <a href="{{ route('teacher.grade-assessment.student', [$class, $enrollment->id]) }}?grading_period={{ $selectedPeriod }}"
           class="flex items-center justify-between p-3 bg-gray-50 hover:bg-blue-50 rounded-lg transition">
            <div>
                <p class="font-medium text-gray-900">{{ $enrollment->student->first_name }} {{ $enrollment->student->last_name }}</p>
                <p class="text-xs text-gray-500">{{ $enrollment->student->student_number }}</p>
            </div>
            <div class="text-right">
                @if($totalMax > 0)
                <p class="text-sm font-semibold text-gray-900">{{ round(($totalRaw / $totalMax) * 100, 1) }}%</p>
                @else
                <p class="text-xs text-gray-400">No scores yet</p>
                @endif
            </div>
        </a>
        @endforeach
    </div>
    @endif
</div>
@endif
@endsection
