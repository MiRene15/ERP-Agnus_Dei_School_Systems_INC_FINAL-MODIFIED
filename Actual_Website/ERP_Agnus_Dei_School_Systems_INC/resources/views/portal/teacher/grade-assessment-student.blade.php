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
    <p class="text-gray-600 mt-1">{{ $class->subject->name ?? 'N/A' }} &middot; {{ $selectedPeriod }} &middot; S.Y. {{ $schoolYear ?? $class->school_year }}</p>
    <div class="mt-2 flex items-center gap-2 flex-wrap">
        <span class="px-3 py-1 rounded-lg text-xs font-bold text-white" style="background: var(--navy);">S.Y. {{ $schoolYear ?? $class->school_year }}</span>
        @if(!empty($schoolYears) && count($schoolYears) > 1)
        <label class="text-xs text-gray-500" for="ea-year-picker">Year:</label>
        <select id="ea-year-picker" onchange="window.location.href='{{ route('teacher.grade-assessment') }}?school_year='+this.value+'&grading_period={{ $selectedPeriod }}&class_id={{ $class->id }}'" class="rounded-lg border border-gray-300 px-2 py-1 text-xs focus:ring-2 focus:ring-blue-500 outline-none">
            @foreach($schoolYears as $yr)
            <option value="{{ $yr }}" {{ ($schoolYear ?? $class->school_year) === $yr ? 'selected' : '' }}>{{ $yr }}</option>
            @endforeach
        </select>
        @endif
        @if(!empty($schoolYearLocked))
        <span class="text-xs font-semibold" style="color: #991B1B;">Locked — read-only. Request a correction unlock to change.</span>
        @endif
    </div>
    <div class="mt-3 rounded-xl border bg-white p-3 flex items-center gap-2 flex-wrap" style="border-color: var(--lilac);">
        <span class="text-sm font-bold" style="color: var(--navy);" data-ea-label>View mode</span>
        <span class="text-xs text-gray-500" data-ea-hint>Scores are read-only — tap Edit to change.</span>
        <button type="button" data-ea-toggle class="ml-auto px-4 py-2 text-sm font-bold text-white transition hover:opacity-90" style="background: var(--navy); border-radius: 10px;">Edit</button>
    </div>
</div>

<form id="ea-score-form" method="POST" action="{{ route('teacher.grade-assessment.student.store', [$class, $enrollment->id]) }}" onsubmit="if (this.dataset.submitted === '1') { return false; } this.dataset.submitted = '1'; const _btn = this.querySelector('[data-save-btn]'); if (_btn) { _btn.disabled = true; _btn.textContent = 'Saving…'; } return true;">
    @csrf
    <input type="hidden" name="grading_period" value="{{ $selectedPeriod }}">
    <input type="hidden" name="school_year" value="{{ $schoolYear ?? $class->school_year }}">

    @if(session('success'))
        <div class="mb-4 p-4 rounded-lg text-sm" style="background: #EEF2FF; border: 1px solid var(--lilac); color: var(--navy);">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 p-4 rounded-lg text-sm" style="background: #FEF2F2; border: 1px solid #FECACA; color: #991B1B;">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="mb-4 p-4 rounded-lg text-sm" style="background: #FEF2F2; border: 1px solid #FECACA; color: #991B1B;">
            <p class="font-semibold mb-1">Fix the flagged rows, then save again — nothing you typed was lost.</p>
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

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
            $oldByType = collect(old('assessments', []))->where('type', $type)->sortKeys()->map(fn($a) => ['title' => $a['title'] ?? '', 'raw_score' => $a['raw_score'] ?? '', 'max_score' => $a['max_score'] ?? '', 'assessment_date' => $a['assessment_date'] ?? '', 'remarks' => $a['remarks'] ?? ''])->values()->toArray();
            $typeAssessments = $existingAssessments[$type] ?? collect();
            $initial = !empty($oldByType) ? $oldByType : $typeAssessments->map(fn($a) => ['title' => $a->title, 'raw_score' => $a->raw_score, 'max_score' => $a->max_score, 'assessment_date' => $a->assessment_date ?? '', 'remarks' => $a->remarks ?? ''])->values()->toArray();
            if (empty($initial)) $initial = [['title' => '', 'raw_score' => '', 'max_score' => '', 'assessment_date' => '', 'remarks' => '']];
            $typeIndex = array_search($type, $assessmentTypes);
            $base = $typeIndex * 100;
        @endphp
        <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-5" x-data="{ items: @js($initial), base: {{ $base }} }">
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-bold text-gray-900 dark:text-[#E8EAF6]">{{ $type }}</h3>
                <button type="button" data-edit-only @click="items.push({title:'', raw_score:'', max_score:'', assessment_date:'', remarks:''})" class="text-xs font-semibold text-blue-600 dark:text-[#60A5FA] hover:underline">+ Add {{ $type }}</button>
            </div>
            <template x-for="(item, idx) in items" :key="idx">
                <div class="mb-3 pb-3 border-b border-gray-100 dark:border-[#2A2F58]">
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
                    <button type="button" data-edit-disable @click="items.splice(idx,1)" x-show="items.length > 1" class="text-red-400 hover:text-red-600 dark:text-[#F87171] p-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="flex gap-2 items-center pl-6">
                    <input type="date" :name="`assessments[${base + idx}][assessment_date]`" x-model="item.assessment_date" max="{{ date('Y-m-d') }}"
                           class="px-2 py-1.5 rounded-lg border border-gray-300 dark:border-[#3B4172] bg-white dark:bg-[#23274C] text-gray-900 dark:text-[#E8EAF6] text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                    <input type="text" :name="`assessments[${base + idx}][remarks]`" x-model="item.remarks" placeholder="Remarks (optional)" maxlength="1000"
                           class="flex-1 px-3 py-1.5 rounded-lg border border-gray-300 dark:border-[#3B4172] bg-white dark:bg-[#23274C] text-gray-900 dark:text-[#E8EAF6] text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                </div>
            </template>
            <div class="mt-2 pt-2 border-t border-gray-200 dark:border-[#2A2F58] text-right text-sm font-semibold text-gray-700 dark:text-[#C1C4DC]" x-text="(() => { const raw = items.reduce((s,i)=>s+(parseFloat(i.raw_score)||0),0); const max = items.reduce((s,i)=>s+(parseFloat(i.max_score)||0),0); return max>0 ? `Subtotal: ${raw} / ${max} (${((raw/max)*100).toFixed(1)}%)` : ''; })()"></div>
        </div>
        @endforeach
    </div>

    <div class="mt-6 flex justify-end" data-edit-only>
        <button type="submit" data-save-btn class="px-6 py-2.5 rounded-lg text-sm font-semibold text-white transition hover:opacity-90" style="background: var(--navy);">
            Save Scores
        </button>
    </div>
</form>
<script>
// List-search-filters spec §4.7: View/Edit toggle with a dirty-check. View is
// the default (matches the batch grid); Edit enables every field. Flipping
// to View with unsaved changes asks "Discard changes?" — confirming reloads
// last-saved from the server (the only true discard here, since the rows
// below are Alpine-owned); cancel keeps the draft in place.
(function () {
    var form = document.getElementById('ea-score-form');
    if (!form) return;
    var toggle = document.querySelector('[data-ea-toggle]');
    var label = document.querySelector('[data-ea-label]');
    var hint = document.querySelector('[data-ea-hint]');
    var startEdit = @json($errors->any() || !empty(old('assessments')));
    var mode = 'view';
    var dirty = false;

    function fields() {
        return form.querySelectorAll('input:not([type="hidden"]), select, textarea');
    }
    function setMode(next) {
        mode = next;
        var viewing = next === 'view';
        fields().forEach(function (el) { el.disabled = viewing; });
        // +Add buttons carry no Alpine visibility binding, so hiding is safe.
        // Remove buttons are Alpine x-show-bound, so they are disabled rather
        // than hidden — hiding would fight Alpine's own display control.
        document.querySelectorAll('[data-edit-only]').forEach(function (el) {
            el.style.display = viewing ? 'none' : '';
        });
        document.querySelectorAll('[data-edit-disable]').forEach(function (el) {
            el.disabled = viewing;
        });
        if (toggle) toggle.textContent = viewing ? 'Edit' : 'View';
        if (label) label.textContent = viewing ? 'View mode' : 'Edit mode';
        if (hint) hint.textContent = viewing
            ? 'Scores are read-only — tap Edit to change.'
            : 'Type scores, then Save Scores.';
        if (viewing) dirty = false;
    }
    form.addEventListener('input', function () { if (mode === 'edit') dirty = true; });
    form.addEventListener('change', function () { if (mode === 'edit') dirty = true; });
    if (toggle) toggle.addEventListener('click', function () {
        if (mode === 'view') { setMode('edit'); return; }
        if (dirty && !confirm('Discard changes?')) return;
        if (dirty) { window.location.reload(); return; }
        setMode('view');
    });
    setMode(startEdit ? 'edit' : 'view');
})();
</script>
@endsection
