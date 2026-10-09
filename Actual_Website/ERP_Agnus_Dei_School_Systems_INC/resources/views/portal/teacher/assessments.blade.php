@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('teacher.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <a href="{{ route('teacher.classes') }}" class="no-underline" style="color: var(--muted);">My Classes</a>
    <span class="opacity-40">/</span>
    <span class="current">{{ $class->subject->name ?? 'Class' }} Encode Grades</span>
@endsection

@section('content')
<style>
    .btn-navy { background: var(--navy) !important; color: #fff !important; border: 1.5px solid var(--navy) !important; transition: background .2s ease, transform .1s ease, box-shadow .2s ease; }
    .btn-navy:hover { background: #3B3878 !important; border-color: #3B3878 !important; box-shadow: 0 6px 18px -6px rgba(36,34,92,.5); }
    .btn-navy:active { background: var(--navy-dark, #121034) !important; transform: translateY(1px); }
    .encode-grid { border-collapse: collapse; }
    .encode-grid thead th { border: 1px solid rgba(255,255,255,.45); }
    .encode-grid tbody td, .encode-grid tbody th { border: 1px solid #B9B4EA; }
    .encode-grid tbody tr:nth-child(even) td { background: #F5F4FF; }
    .encode-grid tbody tr:hover td { background: #ECEAFF; }
</style>
@php
    $legacyMap = ['Written Work' => 'Written Works', 'Quiz' => 'Written Works', 'Seatwork' => 'Written Works', 'Exam' => 'Quarterly Assessment'];
    $bucketOrder = ['Written Works', 'Performance Tasks', 'Quarterly Assessment'];
    $oldRows = old('rows');
    if ($oldRows) {
        $sourceList = collect($oldRows)->map(fn($r) => (object)[
            'enrollment_id' => $r['enrollment_id'] ?? null,
            'type' => $r['type'] ?? 'Written Works',
            'title' => $r['title'] ?? '',
            'raw_score' => $r['raw_score'] ?? null,
            'max_score' => $r['max_score'] ?? null,
            'assessment_date' => $r['assessment_date'] ?? date('Y-m-d'),
            'remarks' => $r['remarks'] ?? null,
        ]);
    } else {
        $sourceList = ($existingAssessments ?? collect())->flatten();
    }
    $worksByKey = [];
    foreach ($sourceList as $a) {
        $rawType = is_object($a) ? ($a->type ?? '') : ($a['type'] ?? '');
        $bucket = $legacyMap[$rawType] ?? $rawType;
        if (!in_array($bucket, $bucketOrder, true)) continue;
        $title = trim((string)(is_object($a) ? ($a->title ?? '') : ($a['title'] ?? '')));
        if ($title === '') $title = 'Work';
        $max = (float)(is_object($a) ? ($a->max_score ?? 0) : ($a['max_score'] ?? 0));
        $date = (string)(is_object($a) ? ($a->assessment_date ?? date('Y-m-d')) : ($a['assessment_date'] ?? date('Y-m-d')));
        if ($date === '') $date = date('Y-m-d');
        $key = $bucket . '|' . mb_strtolower($title) . '|' . $max . '|' . $date;
        if (!isset($worksByKey[$key])) {
            $worksByKey[$key] = ['key' => $key, 'bucket' => $bucket, 'title' => $title, 'max' => $max, 'date' => $date];
        }
    }
    $worksList = collect(array_values($worksByKey))->sortBy(fn($w) => sprintf('%02d-%s', (int) array_search($w['bucket'], $bucketOrder), mb_strtolower($w['title'])))->values()->all();
    $studentsList = $activeEnrollments->map(fn($e) => [
        'id' => $e->id,
        'name' => trim(($e->student->first_name ?? '') . ' ' . ($e->student->last_name ?? '')),
        'lrn' => $e->student->student_number ?? 'N/A',
    ])->values()->all();
    $scoresMap = [];
    $remarksMap = [];
    foreach ($activeEnrollments as $e) { $scoresMap[$e->id] = new stdClass; $remarksMap[$e->id] = new stdClass; }
    foreach ($sourceList as $a) {
        $eid = (int)(is_object($a) ? ($a->enrollment_id ?? 0) : ($a['enrollment_id'] ?? 0));
        if (!$eid || !array_key_exists($eid, $scoresMap)) continue;
        $rawType = is_object($a) ? ($a->type ?? '') : ($a['type'] ?? '');
        $bucket = $legacyMap[$rawType] ?? $rawType;
        $title = trim((string)(is_object($a) ? ($a->title ?? '') : ($a['title'] ?? '')));
        if ($title === '') $title = 'Work';
        $max = (float)(is_object($a) ? ($a->max_score ?? 0) : ($a['max_score'] ?? 0));
        $date = (string)(is_object($a) ? ($a->assessment_date ?? date('Y-m-d')) : ($a['assessment_date'] ?? date('Y-m-d')));
        if ($date === '') $date = date('Y-m-d');
        $key = $bucket . '|' . mb_strtolower($title) . '|' . $max . '|' . $date;
        $raw = is_object($a) ? ($a->raw_score ?? null) : ($a['raw_score'] ?? null);
        if ($raw !== null && $raw !== '') { $scoresMap[$eid]->{$key} = (float)$raw; }
        $rem = is_object($a) ? ($a->remarks ?? null) : ($a['remarks'] ?? null);
        if ($rem) { $remarksMap[$eid]->{$key} = (string)$rem; }
    }
    $ww = $resolvedWeights['written_works'] ?? 20;
    $pt = $resolvedWeights['performance_tasks'] ?? 50;
    $qa = $resolvedWeights['quarterly_assessment'] ?? 30;
@endphp
<div class="mb-6 flex items-start justify-between gap-3 flex-wrap">
    <div>
        <h2 class="text-2xl font-bold" style="color: var(--navy);">Encode Grades – DepEd MATATAG K-12</h2>
        <p class="mt-1 text-sm" style="color: var(--muted);">{{ $class->subject->name ?? 'N/A' }} ({{ $class->subject->subject_code ?? '' }}) · {{ $class->grade_level }} – {{ $class->section }} · {{ $selectedPeriod }} · {{ $class->school_year }}</p>
        <p class="mt-1 text-xs font-semibold" style="color: var(--navy);">We use MATATAG (DO 15, s. 2026) — weights auto-applied, teacher never picks weights.</p>
        @if(!empty($resolvedWeights['label']))
        <p class="mt-1 text-xs" style="color: var(--muted);">MATATAG · {{ $resolvedWeights['label'] }}</p>
        @endif
    </div>
    <div class="flex items-center gap-2">
        <details class="relative">
            <summary class="px-3 py-2 rounded-lg text-sm font-semibold cursor-pointer list-none border" style="border-color: var(--lilac); color: var(--navy);">Help</summary>
            <div class="absolute right-0 mt-2 w-72 p-4 rounded-xl shadow-lg text-xs leading-relaxed bg-white border z-10">
                Per card tap <strong>+ Add Assessment</strong>, name the work (e.g. Quiz 1), set total points + date. A new score column appears. Type each student's raw score (never more than the total). Empty stays empty — never zero. Finish with Draft or Save, then Post on Computed Grades.
            </div>
        </details>
        <a href="{{ route('teacher.grade-assessment') }}?class_id={{ $class->id }}&grading_period={{ $selectedPeriod }}" class="px-3 py-2 rounded-lg text-sm font-semibold border" style="border-color: var(--lilac); color: var(--navy);">Back</a>
    </div>
</div>

@if(session('success'))
    <div class="mb-4 p-4 rounded-lg text-sm" style="background: #EEF2FF; border: 1px solid var(--lilac); color: var(--navy);">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mb-4 p-4 rounded-lg text-sm" style="background: #FEF2F2; border: 1px solid #FECACA; color: #991B1B;">{{ session('error') }}</div>
@endif

<div class="rounded-xl shadow-sm border p-4 mb-4 bg-white">
    <form method="GET" class="flex items-center gap-3 flex-wrap">
        <label class="text-sm font-medium" style="color: var(--navy);">Grading Period:</label>
        <select name="grading_period" onchange="this.form.submit()" class="rounded-lg border px-3 py-2 text-sm outline-none" style="border-color: var(--lilac);">
            @foreach($gradingPeriods as $period)
            <option value="{{ $period }}" {{ $selectedPeriod === $period ? 'selected' : '' }}>{{ $period }}</option>
            @endforeach
        </select>
        <span class="text-xs" style="color: var(--muted);">{{ $activeEnrollments->count() }} student(s)</span>
    </form>
</div>

@if($activeEnrollments->isEmpty())
    <div class="rounded-xl border p-6 text-center text-sm bg-white" style="color: var(--muted);">No active students enrolled in this class.</div>
@else
<div x-data="{
    students: @js($studentsList),
    works: @js($worksList),
    scores: @js($scoresMap),
    remarksMap: @js($remarksMap),
    weights: { ww: {{ (int)$ww }}, pt: {{ (int)$pt }}, qa: {{ (int)$qa }} },
    today: '{{ date('Y-m-d') }}',
    showForm: { 'Written Works': false, 'Performance Tasks': false, 'Quarterly Assessment': false },
    draft: { 'Written Works': {title:'', max:'', date:'{{ date('Y-m-d') }}'}, 'Performance Tasks': {title:'', max:'', date:'{{ date('Y-m-d') }}'}, 'Quarterly Assessment': {title:'', max:'', date:'{{ date('Y-m-d') }}'} },
    saveAction: 'save',
    worksFor(b) { return this.works.filter(w => w.bucket === b); },
    addWork(b) {
        const d = this.draft[b];
        const title = (d.title || '').trim();
        const max = parseFloat(d.max);
        if (!title) { alert('Name the work first — e.g. Quiz 1.'); return; }
        if (!(max > 0)) { alert('Total points must be more than 0.'); return; }
        if (d.date > this.today) { alert('Date cannot be in the future.'); return; }
        const key = b + '|' + title.toLowerCase() + '|' + max + '|' + d.date;
        if (this.works.some(w => w.key === key)) { alert('That work already exists in ' + b + '.'); return; }
        this.works.unshift({key, bucket: b, title, max, date: d.date});
        this.students.forEach(s => { if (this.scores[s.id] === undefined) this.scores[s.id] = {}; });
        d.title = ''; d.max = ''; d.date = this.today;
        this.showForm[b] = false;
    },
    removeWork(key, title) {
        const used = this.students.some(s => { const v = this.scores[s.id]?.[key]; return v !== undefined && v !== null && v !== ''; });
        if (!confirm('Remove ' + title + ' from the sheet?' + (used ? ' Scores in this column are deleted when you save.' : ''))) return;
        this.works = this.works.filter(w => w.key !== key);
        this.students.forEach(s => { if (this.scores[s.id]) delete this.scores[s.id][key]; });
    },
    cell(sid, key) { const v = this.scores[sid]?.[key]; return (v === undefined || v === null) ? '' : v; },
    overMax(sid, key, max) { const v = parseFloat(this.cell(sid, key)); return v !== '' && !isNaN(v) && v > parseFloat(max); },
    get flattened() {
        const out = [];
        this.students.forEach(s => {
            this.works.forEach(w => {
                const v = this.cell(s.id, w.key);
                if (v === '' || v === null || v === undefined) return;
                out.push({enrollment_id: s.id, type: w.bucket, title: w.title, assessment_date: w.date, raw_score: v, max_score: w.max, remarks: (this.remarksMap[s.id]?.[w.key] || '')});
            });
        });
        return out;
    },
    stats(sid, bucket) {
        const ws = this.worksFor(bucket);
        let raw = 0, max = 0, n = 0;
        ws.forEach(w => {
            const v = this.cell(sid, w.key);
            if (v === '' || v === null || v === undefined) return;
            raw += parseFloat(v) || 0; max += parseFloat(w.max) || 0; n++;
        });
        const pct = max > 0 ? Math.round((raw / max) * 10000) / 100 : null;
        let wt = 0;
        if (pct !== null) {
            const pctW = bucket === 'Written Works' ? this.weights.ww : (bucket === 'Performance Tasks' ? this.weights.pt : this.weights.qa);
            if (!(bucket === 'Quarterly Assessment' && this.weights.qa <= 0)) wt = Math.round(pct * (pctW / 100) * 100) / 100;
        }
        const skipped = bucket === 'Quarterly Assessment' && this.weights.qa <= 0;
        return {n: ws.length, scored: n, raw, max, pct: skipped ? null : pct, wt: skipped ? 0 : wt, skipped};
    },
    initial(sid) {
        const a = this.stats(sid, 'Written Works'), b = this.stats(sid, 'Performance Tasks'), c = this.stats(sid, 'Quarterly Assessment');
        if (a.pct === null && b.pct === null && c.pct === null) return null;
        return Math.round((a.wt + b.wt + c.wt) * 100) / 100;
    },
    quarterly(sid) {
        const init = this.initial(sid);
        if (init === null) return null;
        const x = Math.max(0, Math.min(100, init));
        let r = x >= 70 ? 75 + (x - 70) * (25 / 30) : 60 + (x / 70) * 15;
        return Math.round(Math.max(60, Math.min(100, r)));
    }
}">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
        <template x-for="b in ['Written Works','Performance Tasks','Quarterly Assessment']" :key="b">
            <div class="bg-white p-5" style="border: 1px solid #E3E1FA; border-radius: 14px; box-shadow: 0 8px 30px -8px rgba(36,34,92,.12);">
                <div class="flex items-start justify-between gap-2 mb-1">
                    <div>
                        <h3 class="font-bold text-[15px]" style="color: var(--navy);" x-text="b"></h3>
                        <p class="text-xs mt-0.5" style="color: var(--muted);" x-text="b === 'Written Works' ? 'Quizzes, Seatworks, Summative Tests, Assignments' : (b === 'Performance Tasks' ? 'Activities, Projects, Presentations' : 'Periodical Exams, Final Tests')"></p>
                    </div>
                    <span class="text-xs font-bold px-2.5 py-1 whitespace-nowrap" style="background: #fff; color: var(--navy); border: 1.5px solid var(--lilac); border-radius: 10px;" x-text="(b === 'Written Works' ? weights.ww : (b === 'Performance Tasks' ? weights.pt : weights.qa)) + '%'"></span>
                </div>
                <p class="text-xs mb-2" style="color: var(--muted);" x-text="worksFor(b).length + ' assessment(s)'"></p>
                <div class="flex flex-wrap gap-1 mb-3">
                    <template x-for="w in worksFor(b)" :key="w.key">
                        <span class="text-[11px] px-2 py-1" style="border: 1px solid var(--lilac); color: var(--navy); border-radius: 999px; background: var(--off-white);" x-text="w.title + ' / ' + w.max"></span>
                    </template>
                    <span x-show="worksFor(b).length === 0" class="text-xs" style="color: var(--muted);">No assessments yet — add your first below.</span>
                </div>
                <button type="button" @click="showForm[b] = !showForm[b]" class="btn-navy w-full px-4 py-2.5 text-sm font-bold" style="border-radius: 10px;">+ Add Assessment</button>
                <div x-show="showForm[b]" x-cloak class="mt-3 p-3" style="background: #fff; border: 1.5px solid var(--lilac); border-radius: 12px; box-shadow: 0 8px 30px -8px rgba(36,34,92,.12); max-width: 100%; overflow: hidden;">
                    <input type="text" x-model="draft[b].title" placeholder="Work name — e.g. Quiz 1" maxlength="255" class="w-full px-3 py-2 text-sm mb-2 outline-none bg-white" style="border: 1.5px solid var(--lilac); border-radius: 10px; color: var(--navy); max-width: 100%; box-sizing: border-box;">
                    <div class="flex gap-2" style="flex-wrap: wrap;">
                        <input type="number" x-model="draft[b].max" step="0.01" min="0" placeholder="Total" class="px-3 py-2 text-sm outline-none bg-white" style="border: 1.5px solid var(--lilac); border-radius: 10px; color: var(--navy); flex: 1 1 100px; min-width: 0; max-width: 100%; box-sizing: border-box;">
                        <input type="date" x-model="draft[b].date" :max="today" class="px-2 py-2 text-sm outline-none bg-white" style="border: 1.5px solid var(--lilac); border-radius: 10px; color: var(--navy); flex: 1 1 130px; min-width: 0; max-width: 100%; box-sizing: border-box;">
                    </div>
                    <button type="button" @click="addWork(b)" class="btn-navy mt-2 w-full px-4 py-2.5 text-sm font-bold text-white" style="border-radius: 10px;">Add column</button>
                </div>
            </div>
        </template>
    </div>

    <div class="rounded-xl border bg-white p-4 mb-4 text-xs leading-relaxed" style="border-color: var(--lilac); color: var(--navy);">
        <strong>DepEd MATATAG Computation Formula (DO 15, s. 2026)</strong> ·
        Step 1: Percentage Score — (Total Raw ÷ Total Possible) × 100 ·
        Step 2: Weighted Score — % × weight (WW {{ (int)$ww }}%, PT {{ (int)$pt }}%, QA {{ (int)$qa }}%) ·
        Step 3: Transmutation — Initial through interim map (70 → 75) = Quarterly Grade. Empty shows – and adds nothing.
    </div>

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

    <form method="POST" action="{{ route('teacher.assessments.store', $class) }}" @submit="if (flattened.length === 0) { alert('Nothing to save — add an assessment and type at least one score.'); $event.preventDefault(); return; } let bad = []; students.forEach(s => { works.forEach(w => { if (overMax(s.id, w.key, w.max)) bad.push(s.name + ' — ' + w.title); }); }); if (bad.length) { alert('Raw score exceeds total: ' + bad.join('; ')); $event.preventDefault(); return; } if (this.dataset.submitted === '1') { $event.preventDefault(); return; } this.dataset.submitted = '1';">
        @csrf
        <input type="hidden" name="grading_period" value="{{ $selectedPeriod }}">
        <input type="hidden" name="save_action" :value="saveAction">
        <template x-for="(r, idx) in flattened" :key="idx">
            <span>
                <input type="hidden" :name="`rows[${idx}][enrollment_id]`" :value="r.enrollment_id">
                <input type="hidden" :name="`rows[${idx}][type]`" :value="r.type">
                <input type="hidden" :name="`rows[${idx}][title]`" :value="r.title">
                <input type="hidden" :name="`rows[${idx}][assessment_date]`" :value="r.assessment_date">
                <input type="hidden" :name="`rows[${idx}][raw_score]`" :value="r.raw_score">
                <input type="hidden" :name="`rows[${idx}][max_score]`" :value="r.max_score">
                <input type="hidden" :name="`rows[${idx}][remarks]`" :value="r.remarks">
            </span>
        </template>

        <div class="rounded-xl border bg-white shadow-sm overflow-hidden mb-4">
            <div class="px-4 py-3 flex items-center gap-2 flex-wrap border-b" style="border-color: #EEF0FA;">
                <span class="text-sm font-bold" style="color: var(--navy);">Encode scores</span>
                <span class="text-xs" style="color: var(--muted);" x-text="students.length + ' student(s) · ' + works.length + ' work column(s) · ' + flattened.length + ' score(s) to save'"></span>
                <a href="{{ route('teacher.computed-grades') }}?class_id={{ $class->id }}&grading_period={{ $selectedPeriod }}" class="btn-navy ml-auto px-4 py-2 text-sm font-bold" style="border-radius: 10px; text-decoration: none;">Review Computed &amp; Post →</a>
            </div>
            <div class="overflow-x-auto" style="max-width: 100%; -webkit-overflow-scrolling: touch;">
                <table class="w-full text-sm encode-grid" :style="`min-width: ${Math.max(900, 220 + works.length * 150)}px`">
                    <thead>
                        <tr style="background: var(--navy); color: #fff;">
                            <th class="text-left py-3 px-3 font-semibold sticky left-0 z-10" style="background: var(--navy); min-width: 200px;" rowspan="2">Student Name</th>
                            <th class="text-center py-2 px-2 font-semibold" :colspan="Math.max(1, worksFor('Written Works').length)">Written Works</th>
                            <th class="text-center py-2 px-2 font-semibold" :colspan="Math.max(1, worksFor('Performance Tasks').length)" style="border-left: 1px solid rgba(255,255,255,.25);">Performance Tasks</th>
                            <th class="text-center py-2 px-2 font-semibold" :colspan="Math.max(1, worksFor('Quarterly Assessment').length)" style="border-left: 1px solid rgba(255,255,255,.25);">Quarterly Assessment</th>
                        </tr>
                        <tr style="background: var(--navy); color: #fff;">
                            <template x-for="w in worksFor('Written Works')" :key="'h-' + w.key">
                                <th class="text-center py-2 px-2 font-semibold" style="min-width: 130px;">
                                    <div class="truncate" x-text="w.title"></div>
                                    <div class="text-[11px] font-normal opacity-80" x-text="'/ ' + w.max + ' · ' + w.date"></div>
                                    <button type="button" @click="removeWork(w.key, w.title)" class="text-[11px] underline opacity-70 hover:opacity-100">remove</button>
                                </th>
                            </template>
                            <th x-show="worksFor('Written Works').length === 0" class="text-center py-2 px-2 font-normal text-[11px] opacity-70" style="min-width: 150px;">No WW yet —<br>+ Add Assessment above</th>
                            <template x-for="w in worksFor('Performance Tasks')" :key="'h-' + w.key">
                                <th class="text-center py-2 px-2 font-semibold" style="min-width: 130px; border-left: 1px solid rgba(255,255,255,.25);">
                                    <div class="truncate" x-text="w.title"></div>
                                    <div class="text-[11px] font-normal opacity-80" x-text="'/ ' + w.max + ' · ' + w.date"></div>
                                    <button type="button" @click="removeWork(w.key, w.title)" class="text-[11px] underline opacity-70 hover:opacity-100">remove</button>
                                </th>
                            </template>
                            <th x-show="worksFor('Performance Tasks').length === 0" class="text-center py-2 px-2 font-normal text-[11px] opacity-70" style="min-width: 150px; border-left: 1px solid rgba(255,255,255,.25);">No PT yet —<br>+ Add Assessment above</th>
                            <template x-for="w in worksFor('Quarterly Assessment')" :key="'h-' + w.key">
                                <th class="text-center py-2 px-2 font-semibold" style="min-width: 130px; border-left: 1px solid rgba(255,255,255,.25);">
                                    <div class="truncate" x-text="w.title"></div>
                                    <div class="text-[11px] font-normal opacity-80" x-text="'/ ' + w.max + ' · ' + w.date"></div>
                                    <button type="button" @click="removeWork(w.key, w.title)" class="text-[11px] underline opacity-70 hover:opacity-100">remove</button>
                                </th>
                            </template>
                            <th x-show="worksFor('Quarterly Assessment').length === 0" class="text-center py-2 px-2 font-normal text-[11px] opacity-70" style="min-width: 150px; border-left: 1px solid rgba(255,255,255,.25);">No QA yet —<br>+ Add Assessment above</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(s, si) in students" :key="s.id">
                            <tr class="border-b" style="border-color: #F1F2FA;">
                                <td class="py-2 px-3 sticky left-0 bg-white">
                                    <p class="font-semibold" style="color: var(--navy);" x-text="s.name"></p>
                                    <p class="text-[11px]" style="color: var(--muted);" x-text="s.lrn"></p>
                                </td>
                                <template x-for="w in worksFor('Written Works')" :key="s.id + '-' + w.key">
                                    <td class="py-2 px-2 text-center">
                                        <input type="number" step="0.01" min="0" :max="w.max" :placeholder="'/ ' + w.max"
                                            :value="cell(s.id, w.key)"
                                            @input="if (!scores[s.id]) scores[s.id] = {}; scores[s.id][w.key] = $event.target.value"
                                            :class="overMax(s.id, w.key, w.max) ? 'border-red-500 bg-red-50' : ''"
                                            class="w-24 text-center rounded-lg border px-2 py-1.5 text-sm outline-none" style="border-color: var(--lilac);">
                                    </td>
                                </template>
                                <td x-show="worksFor('Written Works').length === 0" class="py-2 px-2 text-center text-xs" style="color: var(--muted);">–</td>
                                <template x-for="w in worksFor('Performance Tasks')" :key="s.id + '-' + w.key">
                                    <td class="py-2 px-2 text-center" style="border-left: 1px solid #F1F2FA;">
                                        <input type="number" step="0.01" min="0" :max="w.max" :placeholder="'/ ' + w.max"
                                            :value="cell(s.id, w.key)"
                                            @input="if (!scores[s.id]) scores[s.id] = {}; scores[s.id][w.key] = $event.target.value"
                                            :class="overMax(s.id, w.key, w.max) ? 'border-red-500 bg-red-50' : ''"
                                            class="w-24 text-center rounded-lg border px-2 py-1.5 text-sm outline-none" style="border-color: var(--lilac);">
                                    </td>
                                </template>
                                <td x-show="worksFor('Performance Tasks').length === 0" class="py-2 px-2 text-center text-xs" style="color: var(--muted); border-left: 1px solid #F1F2FA;">–</td>
                                <template x-for="w in worksFor('Quarterly Assessment')" :key="s.id + '-' + w.key">
                                    <td class="py-2 px-2 text-center" style="border-left: 1px solid #F1F2FA;">
                                        <input type="number" step="0.01" min="0" :max="w.max" :placeholder="'/ ' + w.max"
                                            :value="cell(s.id, w.key)"
                                            @input="if (!scores[s.id]) scores[s.id] = {}; scores[s.id][w.key] = $event.target.value"
                                            :class="overMax(s.id, w.key, w.max) ? 'border-red-500 bg-red-50' : ''"
                                            class="w-24 text-center rounded-lg border px-2 py-1.5 text-sm outline-none" style="border-color: var(--lilac);">
                                    </td>
                                </template>
                                <td x-show="worksFor('Quarterly Assessment').length === 0" class="py-2 px-2 text-center text-xs" style="color: var(--muted); border-left: 1px solid #F1F2FA;">–</td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-xl border bg-white mb-4" style="border-color: #E3E1FA; box-shadow: 0 8px 30px -8px rgba(36,34,92,.12); overflow: visible;">
            <div class="px-4 py-3 border-b text-sm font-bold" style="color: var(--navy); border-color: #EEF0FA;">Computed preview (live) — MATATAG</div>
            <div>
                <table class="w-full text-xs" style="table-layout: fixed;">
                    <thead>
                        <tr class="text-xs" style="color: var(--muted);">
                            <th class="text-left py-2 px-3">Student Name</th>
                            <th class="text-center" colspan="4">Written Works (<span x-text="weights.ww"></span>%)</th>
                            <th class="text-center" colspan="4">Performance Tasks (<span x-text="weights.pt"></span>%)</th>
                            <th class="text-center" colspan="4">Quarterly Assessment (<span x-text="weights.qa > 0 ? weights.qa + '%' : 'skipped'"></span>)</th>
                            <th class="text-center">Initial</th>
                            <th class="text-center">Quarterly</th>
                        </tr>
                        <tr class="text-[10px]" style="color: var(--muted);">
                            <th></th>
                            <th class="font-normal">works</th><th class="font-normal">total</th><th class="font-normal">%</th><th class="font-normal">wt</th>
                            <th class="font-normal">works</th><th class="font-normal">total</th><th class="font-normal">%</th><th class="font-normal">wt</th>
                            <th class="font-normal">works</th><th class="font-normal">total</th><th class="font-normal">%</th><th class="font-normal">wt</th>
                            <th></th><th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="s in students" :key="'c-' + s.id">
                            <tr class="border-t" style="border-color: #F1F2FA;">
                                <td class="py-2 px-3 font-medium" style="color: var(--navy); word-break: break-word;" x-text="s.name"></td>
                                <td class="text-center" x-text="stats(s.id, 'Written Works').scored + '/' + stats(s.id, 'Written Works').n"></td>
                                <td class="text-center" style="word-break: break-word;" x-text="stats(s.id, 'Written Works').max > 0 ? (Math.round(stats(s.id, 'Written Works').raw * 100) / 100) + '/' + stats(s.id, 'Written Works').max : '–'"></td>
                                <td class="text-center" x-text="stats(s.id, 'Written Works').pct !== null ? stats(s.id, 'Written Works').pct + '%' : '–'"></td>
                                <td class="text-center" x-text="stats(s.id, 'Written Works').pct !== null ? stats(s.id, 'Written Works').wt : '–'"></td>
                                <td class="text-center" x-text="stats(s.id, 'Performance Tasks').scored + '/' + stats(s.id, 'Performance Tasks').n"></td>
                                <td class="text-center" style="word-break: break-word;" x-text="stats(s.id, 'Performance Tasks').max > 0 ? (Math.round(stats(s.id, 'Performance Tasks').raw * 100) / 100) + '/' + stats(s.id, 'Performance Tasks').max : '–'"></td>
                                <td class="text-center" x-text="stats(s.id, 'Performance Tasks').pct !== null ? stats(s.id, 'Performance Tasks').pct + '%' : '–'"></td>
                                <td class="text-center" x-text="stats(s.id, 'Performance Tasks').pct !== null ? stats(s.id, 'Performance Tasks').wt : '–'"></td>
                                <td class="text-center" x-text="stats(s.id, 'Quarterly Assessment').scored + '/' + stats(s.id, 'Quarterly Assessment').n"></td>
                                <td class="text-center" style="word-break: break-word;" x-text="stats(s.id, 'Quarterly Assessment').max > 0 ? (Math.round(stats(s.id, 'Quarterly Assessment').raw * 100) / 100) + '/' + stats(s.id, 'Quarterly Assessment').max : '–'"></td>
                                <td class="text-center" x-text="stats(s.id, 'Quarterly Assessment').pct !== null ? stats(s.id, 'Quarterly Assessment').pct + '%' : '–'"></td>
                                <td class="text-center" x-text="stats(s.id, 'Quarterly Assessment').pct !== null ? stats(s.id, 'Quarterly Assessment').wt : '–'"></td>
                                <td class="text-center" x-text="initial(s.id) !== null ? initial(s.id) : '–'"></td>
                                <td class="text-center font-bold" style="color: var(--navy);" x-text="quarterly(s.id) !== null ? quarterly(s.id) : '–'"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="flex items-center gap-2 justify-end flex-wrap">
            <button type="submit" @click="saveAction = 'draft'" class="px-5 py-2.5 text-sm font-bold transition hover:opacity-90" style="border: 1.5px solid var(--navy); color: var(--navy); border-radius: 10px; background: #fff;">Save as Draft</button>
            <button type="submit" @click="saveAction = 'save'" class="btn-navy px-5 py-2.5 text-sm font-bold text-white" style="border-radius: 10px;">Save Grades</button>
        </div>
        <p class="text-xs mt-2 text-right" style="color: var(--muted);">Draft keeps everything editable. Save stores this quarter. Locking happens on Computed Grades → Post Grades.</p>
    </form>
</div>
@endif
@endsection
