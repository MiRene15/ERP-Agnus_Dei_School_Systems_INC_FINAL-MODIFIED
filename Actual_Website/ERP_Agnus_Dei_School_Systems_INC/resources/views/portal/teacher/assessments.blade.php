@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('teacher.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <a href="{{ route('teacher.classes') }}" class="no-underline" style="color: var(--muted);">My Classes</a>
    <span class="opacity-40">/</span>
    <span class="current">{{ $class->subject->name ?? 'Class' }} Assessments</span>
@endsection

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">{{ $class->subject->name ?? 'N/A' }} — Assessments</h2>
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
    <div x-data="{ tab: 'Written Work' }">
        <div class="flex gap-1 mb-6 border-b border-gray-200">
            @foreach($assessmentTypes as $type)
            <button @click="tab = '{{ $type }}'" :class="tab === '{{ $type }}' ? 'border-b-2 border-blue-600 text-blue-700 font-semibold' : 'text-gray-500 hover:text-gray-700'" class="px-4 py-2 text-sm transition outline-none">
                {{ $type }}
            </button>
            @endforeach
        </div>

        <form method="POST" action="{{ route('teacher.assessments.store', $class) }}">
            @csrf
            <input type="hidden" name="grading_period" value="{{ $selectedPeriod }}">

            @foreach($assessmentTypes as $type)
            <div x-show="tab === '{{ $type }}'" x-cloak>
                <div class="overflow-x-auto mb-4">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="text-left py-3 px-2 font-medium text-gray-600 w-12">#</th>
                                <th class="text-left py-3 px-2 font-medium text-gray-600">Student</th>
                                <th class="text-left py-3 px-2 font-medium text-gray-600">LRN</th>
                                <th class="text-center py-3 px-2 font-medium text-gray-600 w-40">Title</th>
                                <th class="text-center py-3 px-2 font-medium text-gray-600 w-28">Raw Score</th>
                                <th class="text-center py-3 px-2 font-medium text-gray-600 w-28">Max Score</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($activeEnrollments as $idx => $enrollment)
                            @php
                                $studentAssessments = $existingAssessments->get($enrollment->id, collect())->where('type', $type);
                            @endphp
                            @if($studentAssessments->isEmpty())
                            <tr class="border-b border-gray-100">
                                <td class="py-2 px-2 text-gray-400">{{ $idx + 1 }}</td>
                                <td class="py-2 px-2">
                                    <span class="font-medium text-gray-900">{{ $enrollment->student->first_name }} {{ $enrollment->student->last_name }}</span>
                                </td>
                                <td class="py-2 px-2 text-gray-600">{{ $enrollment->student->student_number ?? 'N/A' }}</td>
                                <td class="py-2 px-2">
                                    <input type="text" name="assessments[{{ $enrollment->id }}][{{ $type }}][title]" placeholder="Assessment title" class="w-full rounded-lg border border-gray-300 px-2 py-1 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                                </td>
                                <td class="py-2 px-2 text-center">
                                    <input type="number" name="assessments[{{ $enrollment->id }}][{{ $type }}][raw_score]" step="0.01" min="0" placeholder="0" class="w-20 text-center rounded-lg border border-gray-300 px-2 py-1 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                                </td>
                                <td class="py-2 px-2 text-center">
                                    <input type="number" name="assessments[{{ $enrollment->id }}][{{ $type }}][max_score]" step="0.01" min="0" placeholder="100" class="w-20 text-center rounded-lg border border-gray-300 px-2 py-1 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                                </td>
                                <input type="hidden" name="assessments[{{ $enrollment->id }}][{{ $type }}][type]" value="{{ $type }}">
                            </tr>
                            @else
                                @foreach($studentAssessments as $aIdx => $assessment)
                                <tr class="border-b border-gray-100">
                                    <td class="py-2 px-2 text-gray-400">{{ $idx + 1 }}{{ $aIdx > 0 ? '.' . ($aIdx + 1) : '' }}</td>
                                    <td class="py-2 px-2">
                                        <span class="font-medium text-gray-900">{{ $enrollment->student->first_name }} {{ $enrollment->student->last_name }}</span>
                                    </td>
                                    <td class="py-2 px-2 text-gray-600">{{ $enrollment->student->student_number ?? 'N/A' }}</td>
                                    <td class="py-2 px-2">
                                        <input type="text" name="assessments[{{ $enrollment->id }}][{{ $assessment->id }}][title]" value="{{ $assessment->title }}" class="w-full rounded-lg border border-gray-300 px-2 py-1 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                                    </td>
                                    <td class="py-2 px-2 text-center">
                                        <input type="number" name="assessments[{{ $enrollment->id }}][{{ $assessment->id }}][raw_score]" value="{{ $assessment->raw_score }}" step="0.01" min="0" class="w-20 text-center rounded-lg border border-gray-300 px-2 py-1 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                                    </td>
                                    <td class="py-2 px-2 text-center">
                                        <input type="number" name="assessments[{{ $enrollment->id }}][{{ $assessment->id }}][max_score]" value="{{ $assessment->max_score }}" step="0.01" min="0" class="w-20 text-center rounded-lg border border-gray-300 px-2 py-1 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                                    </td>
                                    <input type="hidden" name="assessments[{{ $enrollment->id }}][{{ $assessment->id }}][type]" value="{{ $type }}">
                                </tr>
                                @endforeach
                            @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endforeach

            <div class="flex items-center gap-2 mt-4">
                <button type="submit" class="px-5 py-2 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">Save Assessments</button>
            </div>
        </form>
    </div>
    @endif
</div>
@endsection