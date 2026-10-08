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
        <h2 class="text-2xl font-bold text-gray-900 dark:text-[#E8EAF6]">{{ $class->subject->name ?? 'N/A' }} — Assessments</h2>
        <p class="text-gray-600 dark:text-[#C1C4DC] mt-1">{{ $class->grade_level }} - {{ $class->section }} | {{ $class->subject->subject_code ?? '' }}</p>
    </div>
    <div class="text-sm text-gray-500 dark:text-[#8A90B0] bg-gray-50 dark:bg-[#161A33] px-3 py-2 rounded-lg">
        {{ $activeEnrollments->count() }} student(s)
    </div>
</div>

@if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
@endif

<div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-6">
    <form method="GET" class="mb-6">
        <div class="flex items-center gap-3">
            <label class="text-sm font-medium text-gray-700 dark:text-[#C1C4DC]">Grading Period:</label>
            <select name="grading_period" onchange="this.form.submit()" class="rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                @foreach($gradingPeriods as $period)
                <option value="{{ $period }}" {{ $selectedPeriod === $period ? 'selected' : '' }}>{{ $period }}</option>
                @endforeach
            </select>
        </div>
    </form>

    @if($activeEnrollments->isEmpty())
        <p class="text-sm text-gray-500 dark:text-[#8A90B0] text-center py-4">No active students enrolled in this class.</p>
    @else
    @php
        $studentOptions = $activeEnrollments->map(fn($e) => [
            'id' => $e->id,
            'name' => trim($e->student->first_name . ' ' . $e->student->last_name),
        ])->values();
        $lrnByEnrollment = $activeEnrollments->mapWithKeys(fn($e) => [$e->id => ($e->student->student_number ?? 'N/A')]);
        $existingRows = $existingAssessments->flatten()->map(fn($a) => [
            'enrollment_id' => $a->enrollment_id,
            'type' => $a->type,
            'title' => $a->title ?? '',
            'assessment_date' => $a->assessment_date ?? date('Y-m-d'),
            'raw_score' => $a->raw_score,
            'max_score' => $a->max_score,
            'remarks' => $a->remarks ?? '',
        ])->values();
        $oldRows = old('rows');
        $initialRows = $oldRows
            ? collect($oldRows)->values()
            : ($existingRows->isNotEmpty() ? $existingRows : collect([['enrollment_id' => '', 'type' => $assessmentTypes[0], 'title' => '', 'assessment_date' => date('Y-m-d'), 'raw_score' => '', 'max_score' => '', 'remarks' => '']]));
    @endphp
    <div x-data="{ rows: @js($initialRows), students: @js($studentOptions), types: @js($assessmentTypes), lrns: @js($lrnByEnrollment), today: '{{ date('Y-m-d') }}', addRow() { this.rows.unshift({enrollment_id: '', type: this.types[0], title: '', assessment_date: this.today, raw_score: '', max_score: '', remarks: ''}); }, removeRow(i) { if (this.rows.length > 1 && confirm('Remove this row from the sheet?') && confirm('Removed rows are deleted when you save. Remove this row?')) { this.rows.splice(i, 1); } }, lrnFor(id) { return this.lrns[id] || '—'; } }">
        <form method="POST" action="{{ route('teacher.assessments.store', $class) }}">
            @csrf
            <input type="hidden" name="grading_period" value="{{ $selectedPeriod }}">

            @if($errors->any())
                <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm">
                    <p class="font-semibold mb-1">Fix the flagged rows, then save again — nothing you typed was lost.</p>
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="flex items-center gap-2 mb-4 flex-wrap">
                <button type="button" @click="addRow()" class="px-4 py-2 rounded-lg text-sm font-semibold text-blue-700 dark:text-[#93C5FD] border border-blue-300 dark:border-[#3B4172] hover:bg-blue-50 dark:hover:bg-[#23274C] transition">+ Add Row</button>
                <button type="submit" class="px-5 py-2 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">Save Assessments</button>
                <span class="text-xs text-gray-400 dark:text-[#8A90B0]" x-text="rows.length + ' row(s)'"></span>
            </div>

            <div class="overflow-x-auto mb-4">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-[#2A2F58]">
                            <th class="text-left py-3 px-2 font-medium text-gray-600 dark:text-[#C1C4DC] w-12">#</th>
                            <th class="text-left py-3 px-2 font-medium text-gray-600 dark:text-[#C1C4DC] w-32">Date</th>
                            <th class="text-left py-3 px-2 font-medium text-gray-600 dark:text-[#C1C4DC]">LRN</th>
                            <th class="text-left py-3 px-2 font-medium text-gray-600 dark:text-[#C1C4DC]">Student Name</th>
                            <th class="text-left py-3 px-2 font-medium text-gray-600 dark:text-[#C1C4DC]">Type</th>
                            <th class="text-center py-3 px-2 font-medium text-gray-600 dark:text-[#C1C4DC] w-28">Score</th>
                            <th class="text-left py-3 px-2 font-medium text-gray-600 dark:text-[#C1C4DC]">Remarks / Notes</th>
                            <th class="w-10"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(row, idx) in rows" :key="idx">
                            <tr class="border-b border-gray-100 dark:border-[#2A2F58]">
                                <td class="py-2 px-2 text-gray-400 dark:text-[#8A90B0]" x-text="idx + 1"></td>
                                <td class="py-2 px-2">
                                    <input type="date" :name="`rows[${idx}][assessment_date]`" x-model="row.assessment_date" :max="today" class="w-full rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-2 py-1 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                                </td>
                                <td class="py-2 px-2 text-gray-600 dark:text-[#C1C4DC] whitespace-nowrap" x-text="lrnFor(row.enrollment_id)"></td>
                                <td class="py-2 px-2">
                                    <select :name="`rows[${idx}][enrollment_id]`" x-model="row.enrollment_id" class="w-full rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-2 py-1 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                                        <option value="">Select student…</option>
                                        <template x-for="s in students" :key="s.id">
                                            <option :value="s.id" x-text="s.name"></option>
                                        </template>
                                    </select>
                                    <input type="hidden" :name="`rows[${idx}][title]`" x-model="row.title">
                                </td>
                                <td class="py-2 px-2">
                                    <select :name="`rows[${idx}][type]`" x-model="row.type" class="w-full rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-2 py-1 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                                        <template x-for="t in types" :key="t">
                                            <option :value="t" x-text="t"></option>
                                        </template>
                                    </select>
                                </td>
                                <td class="py-2 px-2 text-center whitespace-nowrap">
                                    <input type="number" :name="`rows[${idx}][raw_score]`" x-model="row.raw_score" step="0.01" min="0" placeholder="0" class="w-20 text-center rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-2 py-1 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                                    <span class="text-gray-400 dark:text-[#6A7094]">/</span>
                                    <input type="number" :name="`rows[${idx}][max_score]`" x-model="row.max_score" step="0.01" min="0" placeholder="100" class="w-20 text-center rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-2 py-1 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                                </td>
                                <td class="py-2 px-2">
                                    <input type="text" :name="`rows[${idx}][remarks]`" x-model="row.remarks" placeholder="Remarks" class="w-full rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-2 py-1 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                                </td>
                                <td class="py-2 px-2 text-center">
                                    <button type="button" @click="removeRow(idx)" x-show="rows.length > 1" class="text-red-400 hover:text-red-600 dark:text-[#F87171] p-1" title="Remove row">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div class="flex items-center gap-2 mt-4">
                <button type="button" @click="addRow()" class="px-4 py-2 rounded-lg text-sm font-semibold text-blue-700 dark:text-[#93C5FD] border border-blue-300 dark:border-[#3B4172] hover:bg-blue-50 dark:hover:bg-[#23274C] transition">+ Add Row</button>
                <button type="submit" class="px-5 py-2 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">Save Assessments</button>
            </div>
        </form>
    </div>
    @endif
</div>
@endsection
