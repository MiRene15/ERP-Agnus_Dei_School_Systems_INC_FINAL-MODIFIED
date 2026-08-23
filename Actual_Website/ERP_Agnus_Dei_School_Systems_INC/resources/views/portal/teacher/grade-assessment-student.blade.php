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
        @php
            $typeAssessments = $existingAssessments[$type] ?? collect();
            $initial = $typeAssessments->map(fn($a) => ['title' => $a->title, 'raw_score' => $a->raw_score, 'max_score' => $a->max_score])->values()->toArray();
            if (empty($initial)) $initial = [['title' => '', 'raw_score' => '', 'max_score' => '']];
            $typeIndex = array_search($type, $assessmentTypes);
            $base = $typeIndex * 100;
        @endphp
        <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-5" x-data="{ items: @js($initial), base: {{ $base }} }">
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-bold text-gray-900 dark:text-[#E8EAF6]">{{ $type }}</h3>
                <button type="button" @click="items.push({title:'', raw_score:'', max_score:''})" class="text-xs font-semibold text-blue-600 dark:text-[#60A5FA] hover:underline">+ Add {{ $type }}</button>
            </div>
            <template x-for="(item, idx) in items" :key="idx">
                <div class="flex gap-2 mb-2 items-center">
                    <span class="text-xs text-gray-400 dark:text-[#6A7094] w-4" x-text="idx+1"></span>
                    <input type="text" :name="`assessments[${base + idx}][title]`" x-model="item.title" placeholder="Title"
                           class="flex-1 px-3 py-1.5 rounded-lg border border-gray-300 dark:border-[#3B4172] bg-white dark:bg-[#23274C] text-gray-900 dark:text-[#E8EAF6] text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                    <input type="hidden" :name="`assessments[${base + idx}][type]`" :value="'{{ $type }}'">
                    <input type="number" :name="`assessments[${base + idx}][raw_score]`" x-model="item.raw_score" placeholder="0" step="0.01" min="0"
                           class="w-20 px-2 py-1.5 rounded-lg border border-gray-300 dark:border-[#3B4172] bg-white dark:bg-[#23274C] text-gray-900 dark:text-[#E8EAF6] text-sm text-center focus:ring-2 focus:ring-blue-500 outline-none">
                    <span class="text-gray-400 dark:text-[#6A7094]">/</span>
                    <input type="number" :name="`assessments[${base + idx}][max_score]`" x-model="item.max_score" placeholder="0" step="0.01" min="0"
                           class="w-20 px-2 py-1.5 rounded-lg border border-gray-300 dark:border-[#3B4172] bg-white dark:bg-[#23274C] text-gray-900 dark:text-[#E8EAF6] text-sm text-center focus:ring-2 focus:ring-blue-500 outline-none">
                    <button type="button" @click="items.splice(idx,1)" x-show="items.length > 1" class="text-red-400 hover:text-red-600 dark:text-[#F87171] p-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </template>
            <div class="mt-2 pt-2 border-t border-gray-200 dark:border-[#2A2F58] text-right text-sm font-semibold text-gray-700 dark:text-[#C1C4DC]" x-text="(() => { const raw = items.reduce((s,i)=>s+(parseFloat(i.raw_score)||0),0); const max = items.reduce((s,i)=>s+(parseFloat(i.max_score)||0),0); return max>0 ? `Subtotal: ${raw} / ${max} (${((raw/max)*100).toFixed(1)}%)` : ''; })()"></div>
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
