@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('teacher.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <a href="{{ route('teacher.grade-assessment') }}?class_id={{ $class->id }}" class="no-underline" style="color: var(--muted);">Grade Assessment</a>
    <span class="opacity-40">/</span>
    <span class="current">{{ $enrollment->student->first_name }} {{ $enrollment->student->last_name }}</span>
@endsection

@section('content')
<div class="mb-4">
    <a href="{{ route('teacher.grade-assessment') }}?class_id={{ $class->id }}&grading_period={{ $selectedPeriod }}" class="text-sm text-blue-600 hover:underline">&larr; Back to Class</a>
</div>

<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">{{ $enrollment->student->first_name }} {{ $enrollment->student->last_name }}</h2>
    <p class="text-gray-600 mt-1">{{ $class->subject->name ?? 'N/A' }} &middot; {{ $selectedPeriod }}</p>
</div>

<form method="POST" action="{{ route('teacher.grade-assessment.student.store', [$class, $enrollment->id]) }}">
    @csrf
    <input type="hidden" name="grading_period" value="{{ $selectedPeriod }}">

    <div class="flex items-center gap-2 mb-4">
        <label class="text-sm font-medium text-gray-700">Grading Period:</label>
        <div class="flex gap-1">
            @foreach($gradingPeriods as $period)
            <a href="{{ route('teacher.grade-assessment.student', [$class, $enrollment->id]) }}?grading_period={{ $period }}"
               class="px-3 py-1 rounded-lg text-sm font-medium transition {{ $selectedPeriod === $period ? 'text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}"
               style="{{ $selectedPeriod === $period ? 'background: var(--navy);' : '' }}">
                {{ $period }}
            </a>
            @endforeach
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        @foreach($assessmentTypes as $type)
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <h3 class="font-bold text-gray-900 mb-3">{{ $type }}</h3>
            @php
                $typeAssessments = $existingAssessments[$type] ?? collect();
                $count = max($typeAssessments->count(), 3);
            @endphp

            @for($i = 0; $i < $count; $i++)
            @php
                $existing = $typeAssessments->get($i);
            @endphp
            <div class="flex gap-2 mb-2 items-center">
                <span class="text-xs text-gray-400 w-4">{{ $i + 1 }}</span>
                <input type="text" name="assessments[{{ $i }}][title]"
                       value="{{ $existing->title ?? '' }}" placeholder="Title"
                       class="flex-1 px-3 py-1.5 rounded-lg border border-gray-300 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                <input type="hidden" name="assessments[{{ $i }}][type]" value="{{ $type }}">
                <input type="number" name="assessments[{{ $i }}][raw_score]"
                       value="{{ $existing->raw_score ?? '' }}" placeholder="0" step="0.01" min="0"
                       class="w-20 px-2 py-1.5 rounded-lg border border-gray-300 text-sm text-center focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                <span class="text-gray-400">/</span>
                <input type="number" name="assessments[{{ $i }}][max_score]"
                       value="{{ $existing->max_score ?? '' }}" placeholder="0" step="0.01" min="0"
                       class="w-20 px-2 py-1.5 rounded-lg border border-gray-300 text-sm text-center focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
            </div>
            @endfor

            @php
                $totalRaw = $typeAssessments->sum('raw_score');
                $totalMax = $typeAssessments->sum('max_score');
            @endphp
            @if($totalMax > 0)
            <div class="mt-2 pt-2 border-t border-gray-200 text-right">
                <span class="text-sm font-semibold text-gray-700">Subtotal: {{ $totalRaw }} / {{ $totalMax }} ({{ round(($totalRaw / $totalMax) * 100, 1) }}%)</span>
            </div>
            @endif
        </div>
        @endforeach
    </div>

    <div class="mt-6 flex justify-end">
        <button type="submit" class="px-6 py-2.5 rounded-lg text-sm font-semibold text-white transition hover:opacity-90" style="background: var(--navy);">
            Save Scores
        </button>
    </div>
</form>
@endsection
