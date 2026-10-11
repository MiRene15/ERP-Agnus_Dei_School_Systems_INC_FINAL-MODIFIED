@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('registrar.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <span class="current">End-of-Year Promotion</span>
@endsection

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900 dark:text-[#E8EAF6]">End-of-Year Promotion — Prepare Proposals</h2>
    <p class="text-gray-600 dark:text-[#C1C4DC] mt-1">You propose an action per student. The Principal approves, then the Directress signs off and the system moves the students. Nothing executes from this page.</p>
</div>

@if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm">
        <ul class="list-disc list-inside">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif

<form method="GET" action="{{ route('registrar.promotion.index') }}" class="mb-4 flex items-center gap-3 flex-wrap bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-4">
    <label class="text-sm font-medium text-gray-700 dark:text-[#C1C4DC] whitespace-nowrap">School Year:</label>
    <select name="source_year" class="rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
        @foreach($schoolYears as $sy)
        <option value="{{ $sy }}" {{ ($sourceYear ?? '') === $sy ? 'selected' : '' }}>{{ $sy }}</option>
        @endforeach
    </select>
    <label class="text-sm font-medium text-gray-700 dark:text-[#C1C4DC] whitespace-nowrap">Grade:</label>
    <select name="grade_level" class="rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
        <option value="All" {{ ($gradeFilter ?? 'All') === 'All' ? 'selected' : '' }}>All grades</option>
        @foreach(($gradeCounts ?? collect()) as $g => $c)
        <option value="{{ $g }}" {{ ($gradeFilter ?? '') === $g ? 'selected' : '' }}>{{ $g }} ({{ $c }})</option>
        @endforeach
    </select>
    <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Search name or student no." class="rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none w-56" maxlength="100">
    <button type="submit" class="px-5 py-2 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);">Filter</button>
</form>

<div class="mb-4 flex items-center gap-2 flex-wrap text-sm text-gray-600 dark:text-[#C1C4DC]">
    @forelse(($gradeCounts ?? collect()) as $g => $c)
    <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-gray-100 dark:bg-[#23274C] font-medium">{{ $g }}: {{ $c }} student(s)</span>
    @empty
    <span class="text-gray-400">No counts — no active enrollments for {{ $sourceYear ?? '' }}.</span>
    @endforelse
</div>

@if(!empty($duplicatePairs))
<div class="mb-4 bg-amber-50 dark:bg-[rgba(251,191,36,0.08)] border border-amber-200 dark:border-[rgba(251,191,36,0.25)] rounded-xl p-4">
    <h3 class="font-semibold text-amber-900 dark:text-[#FCD34D] text-sm mb-1">Possible duplicates ({{ count($duplicatePairs) }}) — pick the survivor</h3>
    <p class="text-xs text-amber-800 dark:text-[#FCD34D] mb-3">Same name, side by side with LRN, birthdate and section. Grades, attendance and history follow the survivor; the loser is archived, never deleted.</p>
    @foreach($duplicatePairs as $pair)
    <form method="POST" action="{{ route('registrar.promotion.merge-duplicate') }}" onsubmit="return confirm('Merge into the selected survivor? Grades, attendance and history follow the survivor; the loser is archived, never deleted.')" class="mb-3 bg-white dark:bg-[#1A1E3B] rounded-lg border border-amber-100 dark:border-[#2A2F58] p-3">
        @csrf
        <div class="flex items-stretch gap-3 flex-wrap">
            @foreach($pair['students'] as $enr)
            <label class="flex-1 min-w-[220px] flex items-start gap-2 p-2 rounded-lg border border-gray-100 dark:border-[#2A2F58]">
                <input type="radio" name="survivor_id" value="{{ $enr->student->id }}" required class="mt-1 text-blue-600 focus:ring-blue-500">
                <span>
                    <span class="block font-medium text-gray-900 dark:text-[#E8EAF6] text-sm">{{ $enr->student->first_name }} {{ $enr->student->last_name }}</span>
                    <span class="block text-xs text-gray-500">No: {{ $enr->student->student_number }} · LRN: {{ $enr->student->legacy_lrn ?? '—' }} · Born: {{ $enr->student->date_of_birth ?? '—' }}</span>
                    <span class="block text-xs text-gray-400">{{ $enr->section->grade_level ?? '' }} {{ $enr->section->section_name ?? '' }}</span>
                </span>
            </label>
            @endforeach
        </div>
        <div class="mt-2 flex items-center gap-2">
            <select name="loser_id" required class="rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-2 py-1.5 text-xs">
                <option value="">Archive as duplicate…</option>
                @foreach($pair['students'] as $enr)
                <option value="{{ $enr->student->id }}">{{ $enr->student->first_name }} {{ $enr->student->last_name }} ({{ $enr->student->student_number }})</option>
                @endforeach
            </select>
            <button type="submit" class="px-4 py-1.5 rounded-lg text-xs font-semibold text-white" style="background: var(--navy);">Merge into survivor</button>
        </div>
    </form>
    @endforeach
</div>
@endif

@if($enrollments->isEmpty())
<div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-6 text-center">
    <p class="text-sm text-gray-500 dark:text-[#8A90B0] py-4">No students in this grade.</p>
</div>
@else
<form method="POST" action="{{ route('registrar.promotion.propose') }}" id="promoForm">
    @csrf
    <div class="mb-5 flex items-center gap-3 flex-wrap bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-4">
        <label class="inline-flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-[#C1C4DC] whitespace-nowrap">
            <input type="checkbox" id="selectAllGrades" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
            All grades — all students (<span id="allGradesCount">{{ ($page ?? null) ? $page->total() : $enrollments->flatten()->count() }}</span>)
        </label>
        <label class="text-sm font-medium text-gray-700 dark:text-[#C1C4DC] whitespace-nowrap">New School Year:</label>
        <select name="school_year" required class="rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
            <option value="">Select school year</option>
            @foreach($schoolYears as $sy)
            <option value="{{ $sy }}">{{ $sy }}</option>
            @endforeach
            <option value="{{ date('Y') . '-' . (date('Y') + 1) }}">{{ date('Y') . '-' . (date('Y') + 1) }} (New)</option>
        </select>
        <button type="submit" onclick="return confirm('Send the selected proposals to the Principal for approval?')" class="px-6 py-2.5 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">Send Proposals to Principal</button>
        <button type="submit" formaction="{{ route('registrar.promotion.batch-qualified') }}" onclick="return confirm('Propose promotion for all qualified students? Unqualified students and open proposals will be skipped.')" class="px-6 py-2.5 rounded-lg text-sm font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 transition">Batch Qualified — Level Up Only</button>
    </div>
    <div id="promoPreview" class="mb-5 hidden p-4 bg-blue-50 dark:bg-[#1A1E3B] border border-blue-200 dark:border-[#2A2F58] rounded-xl text-sm text-blue-900 dark:text-[#C1C4DC]"></div>

    @foreach($enrollments as $gradeLevel => $gradeEnrollments)
    <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-6 mb-5">
        <div class="flex items-center gap-3 mb-1 flex-wrap">
            <h3 class="font-semibold text-gray-900 dark:text-[#E8EAF6]">{{ $gradeLevel }} <span class="text-sm font-normal text-gray-500 dark:text-[#8A90B0]">({{ $gradeEnrollments->count() }} student(s))</span></h3>
            <label class="inline-flex items-center gap-2 text-xs font-medium text-gray-600 dark:text-[#C1C4DC]">
                <input type="checkbox" class="grade-select-all rounded border-gray-300 text-blue-600 focus:ring-blue-500" data-grade="{{ $gradeLevel }}">
                Select all in {{ $gradeLevel }}
            </label>
        </div>
        <p class="text-xs text-gray-400 dark:text-[#8A90B0] mb-4">GWA ≥ {{ $passingGrade ?? 75 }} and no failing subject (&lt;{{ $passingGrade ?? 75 }}) = qualified. Leave the action blank to skip a student.</p>
        @php
            $secGroups = $gradeEnrollments->groupBy(fn($e) => $e->section->section_name ?? 'N/A');
        @endphp
        <div class="flex items-center gap-2 flex-wrap text-xs mb-4">
            @foreach($secGroups as $secName => $secRows)
            @php
                $secId = $secRows->first()->section_id;
                $occ = ($sectionOccupancy ?? [])[$secId] ?? $secRows->count();
                $locked = $occ >= \App\Services\PromotionService::SECTION_CAP;
            @endphp
            <span class="inline-flex items-center px-2 py-0.5 rounded-full font-medium {{ $locked ? 'bg-gray-200 text-gray-600 dark:bg-[#23274C] dark:text-[#8A90B0]' : 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300' }}">{{ $secName }} {{ $occ }}/{{ \App\Services\PromotionService::SECTION_CAP }}{{ $locked ? ' Locked' : '' }}</span>
            @endforeach
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px] text-sm">
                <thead>
                    <tr class="border-y border-gray-200 dark:border-[#2A2F58] bg-gray-50 dark:bg-[#161A33]">
                        <th class="py-3 px-4 w-10"><span class="sr-only">Select</span></th>
                        <th class="text-left py-3 px-4 font-medium text-gray-600 dark:text-[#C1C4DC]">Student</th>
                        <th class="text-left py-3 px-4 font-medium text-gray-600 dark:text-[#C1C4DC]">Section</th>
                        <th class="text-left py-3 px-4 font-medium text-gray-600 dark:text-[#C1C4DC]">GWA</th>
                        <th class="text-left py-3 px-4 font-medium text-gray-600 dark:text-[#C1C4DC]">Qualification</th>
                        <th class="text-left py-3 px-4 font-medium text-gray-600 dark:text-[#C1C4DC]">Proposal Status</th>
                        <th class="text-left py-3 px-4 font-medium text-gray-600 dark:text-[#C1C4DC] w-56">Proposed Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($gradeEnrollments as $enrollment)
                    @php
                        $isGrade12 = $gradeLevel === 'Grade 12';
                        $passing = $passingGrade ?? 75;
                        $grades = $enrollment->grades ?? collect();
                        $grouped = $grades->groupBy('class_id');
                        $finals = $grouped->map(fn($g) => round($g->avg('final_grade'), 2));
                        $avg = $finals->isNotEmpty() ? round($finals->avg(), 2) : null;
                        $failCount = $finals->filter(fn($f) => $f < $passing)->count();
                        $qualified = $avg !== null && $avg >= $passing && $failCount === 0;
                        $open = $openProposals[$enrollment->id] ?? null;
                    @endphp
                    <tr class="border-b border-gray-100 dark:border-[#2A2F58] hover:bg-gray-50 dark:hover:bg-[#1E2447]" data-grade="{{ $gradeLevel }}">
                        <td class="py-3 px-4">
                            @if(!$open)
                            <input type="checkbox" class="promo-row rounded border-gray-300 text-blue-600 focus:ring-blue-500" data-grade="{{ $gradeLevel }}" value="{{ $enrollment->id }}">
                            @endif
                        </td>
                        <td class="py-3 px-4">
                            <span class="font-medium text-gray-900 dark:text-[#E8EAF6]">{{ $enrollment->student->first_name }} {{ $enrollment->student->last_name }}</span>
                            <span class="block text-xs text-gray-400 dark:text-[#8A90B0]">{{ $enrollment->student->student_number }}</span>
                        </td>
                        <td class="py-3 px-4 text-gray-700 dark:text-[#C1C4DC]">{{ $enrollment->section->section_name ?? 'N/A' }}</td>
                        <td class="py-3 px-4">
                            @if($avg === null)
                                <span class="text-gray-400 text-xs">No grades</span>
                            @else
                                <span class="font-semibold {{ $avg >= $passing ? 'text-gray-900 dark:text-[#E8EAF6]' : 'text-red-600' }}">{{ number_format($avg, 2) }}</span>
                                @if($failCount > 0)<span class="block text-xs text-red-500">{{ $failCount }} failing</span>@endif
                            @endif
                        </td>
                        <td class="py-3 px-4">
                            @if($avg === null)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600 dark:bg-[#23274C] dark:text-[#C1C4DC]">No grades</span>
                            @elseif($qualified)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300">Qualified</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300">Not qualified</span>
                            @endif
                        </td>
                        <td class="py-3 px-4">
                            @if($open)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700">{{ ucfirst(str_replace('_', ' ', $open->status)) }}</span>
                            @else
                                <span class="text-xs text-gray-400">—</span>
                            @endif
                            @if(!empty($holdMap[$enrollment->student_id] ?? []))
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300 mt-1" title="@foreach($holdMap[$enrollment->student_id] as $h)[{{ ucfirst($h['source']) }}] {{ $h['reason'] }}&#10;@endforeach">HOLD ({{ count($holdMap[$enrollment->student_id]) }})</span>
                            @endif
                        </td>
                        <td class="py-3 px-4">
                            @if($open)
                                <span class="text-xs text-gray-400">Locked by open proposal</span>
                            @else
                                <div class="flex flex-col gap-1.5" x-data="{ act: '' }">
                                <select name="actions[{{ $enrollment->id }}]" @change="act = $event.target.value" class="w-full rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-2 py-1.5 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                                    <option value="">Skip</option>
                                    @if(!$isGrade12)
                                    <option value="promote" @if($qualified) selected @endif>Promote</option>
                                    <option value="retain" @if(!$qualified && $avg !== null) selected @endif>Retain</option>
                                    @endif
                                    <option value="graduate" {{ $isGrade12 ? 'selected' : '' }}>Graduate</option>
                                    <option value="transfer">Transfer Out</option>
                                    <option value="dropped">Dropped Out</option>
                                </select>
                                <input type="text" name="reasons[{{ $enrollment->id }}]" placeholder="Reason (required for Transfer/Dropped)" x-show="act === 'transfer' || act === 'dropped'" x-cloak class="w-full rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-2 py-1.5 text-xs focus:ring-2 focus:ring-blue-500 outline-none" maxlength="500">
                                </div>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endforeach
</form>
<div class="mt-4">{{ ($page ?? null)?->links() }}</div>
<p class="mt-2 text-xs text-gray-400 dark:text-[#8A90B0]">Counts show first; rows stream 100 per page — still loading rows never blanks the counts.</p>
@endif
<script>
(function () {
    var form = document.getElementById('promoForm');
    if (!form) return;
    var preview = document.getElementById('promoPreview');
    var globalBox = document.getElementById('selectAllGrades');

    function rows() { return Array.prototype.slice.call(form.querySelectorAll('.promo-row')); }
    function actionFor(row) {
        var tr = row.closest('tr');
        var sel = tr ? tr.querySelector('select[name^="actions["]') : null;
        return sel ? sel.value : '';
    }
    function updatePreview() {
        var counts = {};
        rows().forEach(function (r) { if (r.checked) { var a = actionFor(r); if (a) counts[a] = (counts[a] || 0) + 1; } });
        var parts = Object.keys(counts).map(function (k) { return counts[k] + ' ' + k; });
        if (!parts.length) { preview.classList.add('hidden'); preview.textContent = ''; return; }
        preview.classList.remove('hidden');
        preview.textContent = parts.join(' · ') + ' → Send Proposals to Principal (execution locked until Principal approval). Section split preview arrives with Slice 3.';
    }
    document.querySelectorAll('.grade-select-all').forEach(function (box) {
        box.addEventListener('change', function () {
            rows().forEach(function (r) { if (r.getAttribute('data-grade') === box.getAttribute('data-grade')) r.checked = box.checked; });
            updatePreview();
        });
    });
    if (globalBox) {
        globalBox.addEventListener('change', function () {
            if (globalBox.checked) {
                var total = document.getElementById('allGradesCount');
                var n = total ? total.textContent : String(rows().length);
                if (!confirm('Select all ' + n + ' student(s) across all grades?')) { globalBox.checked = false; return; }
            }
            rows().forEach(function (r) { r.checked = globalBox.checked; });
            document.querySelectorAll('.grade-select-all').forEach(function (b) { b.checked = globalBox.checked; });
            updatePreview();
        });
    }
    form.addEventListener('change', function (e) {
        if (e.target && (e.target.classList.contains('promo-row') || (e.target.name && e.target.name.indexOf('actions[') === 0))) updatePreview();
    });
    form.addEventListener('submit', function (e) {
        if (e.submitter && e.submitter.getAttribute('formaction')) return; // batch-qualified path untouched
        rows().forEach(function (r) {
            if (!r.checked) {
                var tr = r.closest('tr');
                if (tr) tr.querySelectorAll('select[name^="actions["], input[name^="reasons["]').forEach(function (el) { el.disabled = true; });
            }
        });
        var anyAction = rows().some(function (r) { return r.checked && actionFor(r); });
        if (!anyAction) { e.preventDefault(); alert('Nothing proposed — tick at least one student and pick an action.'); rows().forEach(function (r) {
            var tr = r.closest('tr');
            if (tr) tr.querySelectorAll('select[name^="actions["], input[name^="reasons["]').forEach(function (el) { el.disabled = false; });
        }); }
    });
})();
</script>
@endsection
