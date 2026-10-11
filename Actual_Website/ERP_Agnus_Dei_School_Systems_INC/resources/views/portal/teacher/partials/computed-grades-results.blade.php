@if(!$selectedClassId)
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <h3 class="font-semibold text-gray-900 mb-4">Select a Class</h3>
    @if($classes->isEmpty())
    <p class="text-sm text-gray-500">No classes assigned.</p>
    @endif
    @if($classes->isEmpty() && (request('grade_level') || request('section')))
    <p class="text-sm text-gray-500 text-center py-4">No classes match — clear the filters.</p>
    @endif
    @if($classes->isNotEmpty())
    <div class="space-y-2">
        @foreach($classes as $cls)
        <a href="{{ route('teacher.computed-grades') }}?class_id={{ $cls->id }}&grading_period={{ $selectedPeriod }}"
           class="flex items-center justify-between p-3 bg-gray-50 hover:bg-blue-50 rounded-lg transition">
            <div>
                <p class="font-medium text-gray-900">{{ $cls->subject->name ?? 'N/A' }}</p>
                <p class="text-xs text-gray-500">{{ $cls->grade_level }} - {{ $cls->section }}</p>
            </div>
            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </a>
        @endforeach
    </div>
    @endif
</div>
@else
<style>
    .encode-grid { border-collapse: collapse; }
    .encode-grid thead th { border: 1px solid rgba(255,255,255,.45); }
    .encode-grid tbody td, .encode-grid tbody th { border: 1px solid #B9B4EA; }
    .encode-grid tbody tr:nth-child(even) td { background: #F5F4FF; }
    .encode-grid tbody tr:hover td { background: #ECEAFF; }
</style>
<div class="mb-4 flex items-center gap-3 flex-wrap">
    <a href="{{ route('teacher.computed-grades') }}" class="text-sm text-blue-600 hover:underline">&larr; Change Class</a>
    <span class="text-gray-300">|</span>
    <h3 class="font-semibold text-gray-900">{{ $class->subject->name ?? 'N/A' }} — {{ $class->grade_level }} {{ $class->section }}</h3>
</div>

@if(session('success'))
    <div class="mb-4 p-4 rounded-lg text-sm" style="background: #EEF2FF; border: 1px solid var(--lilac); color: var(--navy);">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mb-4 p-4 rounded-lg text-sm" style="background: #FEF2F2; border: 1px solid #FECACA; color: #991B1B;">{{ session('error') }}</div>
@endif
@if($errors->any())
    <div class="mb-4 p-4 rounded-lg text-sm" style="background: #FEF2F2; border: 1px solid #FECACA; color: #991B1B;">
        <p class="font-semibold mb-1">Could not post — nothing you typed was lost. Fix and try again.</p>
        <ul class="list-disc list-inside">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if(!empty($resolvedWeights['label']))
<p class="text-xs mb-3" style="color: var(--navy);"><strong>We use MATATAG (DO 15, s. 2026)</strong> · {{ $resolvedWeights['label'] }}</p>
@endif

<div class="mb-4 flex items-center gap-2">
    <label class="text-sm font-medium text-gray-700">Grading Period:</label>
    <div class="flex gap-1">
        @foreach($gradingPeriods as $period)
        <a href="{{ route('teacher.computed-grades') }}?class_id={{ $selectedClassId }}&grading_period={{ $period }}"
           class="px-3 py-1 rounded-lg text-sm font-medium transition {{ $selectedPeriod === $period ? 'text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}"
           style="{{ $selectedPeriod === $period ? 'background: var(--navy);' : '' }}">
            {{ $period }}
        </a>
        @endforeach
    </div>
</div>

<form method="POST" action="{{ route('teacher.computed-grades.batch-submit') }}" onsubmit="if (this.dataset.submitted === '1') { return false; } const _btn = this.querySelector('[data-post-btn]'); if (_btn && _btn.dataset.confirm && !confirm(_btn.dataset.confirm)) { return false; } this.dataset.submitted = '1'; if (_btn) { _btn.disabled = true; _btn.textContent = 'Posting…'; } return true;">
    @csrf
    <input type="hidden" name="class_id" value="{{ $selectedClassId }}">
    <input type="hidden" name="grading_period" value="{{ $selectedPeriod }}">
    <input type="hidden" name="action" value="post">

    @php
        $lockedCount = $computedGrades->where('status', 'Submitted')->count();
    @endphp
    @if($lockedCount > 0 && $lockedCount === $computedGrades->count())
    <div class="mb-4 p-4 rounded-lg text-sm font-semibold" style="background: #ECFDF5; border: 1px solid #A7F3D0; color: #15803D;">Posted — locked; request a correction unlock to change.</div>
    @elseif($lockedCount > 0)
    <div class="mb-4 p-4 rounded-lg text-sm" style="background: #EEF2FF; border: 1px solid var(--lilac); color: var(--navy);">{{ $lockedCount }} of {{ $computedGrades->count() }} posted — locked. The rest are on hold for verification.</div>
    @elseif($computedGrades->isNotEmpty())
    <div class="mb-4 p-4 rounded-lg text-sm" style="background: #FFFBEB; border: 1px solid #FDE68A; color: #92400E;">On hold for verification — review the computed breakdown, then Post to lock.</div>
    @endif

    <div class="rounded-xl border bg-white shadow-sm overflow-hidden mb-4">
        <div class="px-4 py-3 flex items-center gap-2 flex-wrap border-b" style="border-color: #EEF0FA;">
            <span class="text-sm font-bold" style="color: var(--navy);">Review computed grades</span>
            <span class="text-xs" style="color: var(--muted);">{{ $computedGrades->count() }} student(s) · {{ $selectedPeriod }}</span>
        </div>
        <div class="overflow-x-auto" style="max-width: 100%; -webkit-overflow-scrolling: touch;">
            <table class="w-full text-sm encode-grid">
                <thead>
                    <tr style="background: var(--navy); color: #fff;">
                        <th class="text-left py-3 px-3 font-semibold sticky left-0" style="background: var(--navy); min-width: 48px;">#</th>
                        <th class="text-left py-3 px-3 font-semibold sticky left-10" style="background: var(--navy); min-width: 200px;">Student Name</th>
                        <th class="text-center py-3 px-2 font-semibold" colspan="4">Written Works<br><span class="text-[10px] font-normal opacity-80">{{ $resolvedWeights['written_works'] ?? '' }}%</span></th>
                        <th class="text-center py-3 px-2 font-semibold" colspan="4">Performance Tasks<br><span class="text-[10px] font-normal opacity-80">{{ $resolvedWeights['performance_tasks'] ?? '' }}%</span></th>
                        <th class="text-center py-3 px-2 font-semibold" colspan="4">Quarterly Assessment<br><span class="text-[10px] font-normal opacity-80">@if(($resolvedWeights['quarterly_assessment'] ?? 0) > 0){{ $resolvedWeights['quarterly_assessment'] }}%@else skipped @endif</span></th>
                        <th class="text-center py-3 px-3 font-semibold">Initial Grade</th>
                        <th class="text-center py-3 px-3 font-semibold sticky right-24" style="background: var(--navy);">Quarterly Grade</th>
                        <th class="text-center py-3 px-3 font-semibold sticky right-0" style="background: var(--navy);">Final Grade</th>
                    </tr>
                    <tr style="background: var(--navy); color: #fff;">
                        <th></th>
                        <th></th>
                        @for($i = 0; $i < 3; $i++)
                        <th class="text-center py-1 px-1 text-[10px] font-medium">works</th>
                        <th class="text-center py-1 px-1 text-[10px] font-medium">total</th>
                        <th class="text-center py-1 px-1 text-[10px] font-medium">%</th>
                        <th class="text-center py-1 px-1 text-[10px] font-medium">weighted</th>
                        @endfor
                        <th></th>
                        <th></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($computedGrades as $idx => $cg)
                    @php
                        $ww = $cg['categories']['Written Works'] ?? ['count' => 0, 'raw' => 0, 'max' => 0, 'percentage' => null, 'weighted' => 0];
                        $pt = $cg['categories']['Performance Tasks'] ?? ['count' => 0, 'raw' => 0, 'max' => 0, 'percentage' => null, 'weighted' => 0];
                        $qa = $cg['categories']['Quarterly Assessment'] ?? ['count' => 0, 'raw' => 0, 'max' => 0, 'percentage' => null, 'weighted' => 0];
                        $isLocked = ($cg['status'] ?? null) === 'Submitted';
                        $isDraft = ($cg['status'] ?? null) === 'Draft';
                        $suggested = $cg['quarterly_grade'] ?? $cg['initial_grade'] ?? $cg['computed_grade'] ?? null;
                    @endphp
                    <tr class="border-b" style="border-color: #F1F2FA;">
                        <td class="py-2 px-3 sticky left-0 bg-white" style="color: var(--muted);">{{ $idx + 1 }}</td>
                        <td class="py-2 px-3 sticky left-10 bg-white">
                            <p class="font-semibold" style="color: var(--navy);">{{ $cg['student']->first_name }} {{ $cg['student']->last_name }}</p>
                            <p class="text-[10px]" style="color: var(--muted);">{{ $cg['student']->student_number }}</p>
                            @if($isLocked)
                            <span class="block text-[10px]" style="color: var(--muted);">Locked — <a href="{{ route('teacher.grade-unlocks.index') }}" class="underline">request unlock to change</a></span>
                            @elseif($isDraft)
                            <span class="block text-[10px]" style="color: var(--muted);">Draft</span>
                            @endif
                        </td>
                        @foreach([$ww, $pt, $qa] as $bucket)
                        <td class="py-2 px-1 text-center text-xs" style="color: var(--muted);">{{ $bucket['count'] }}</td>
                        <td class="py-2 px-1 text-center text-xs" style="color: var(--navy);">@if(($bucket['max'] ?? 0) > 0){{ rtrim(rtrim(number_format((float) $bucket['raw'], 2), '0'), '.') }}/{{ rtrim(rtrim(number_format((float) $bucket['max'], 2), '0'), '.') }}@else<span style="color: #B9B4EA;">–</span>@endif</td>
                        <td class="py-2 px-1 text-center text-xs" style="color: var(--navy);">@if($bucket['percentage'] !== null){{ $bucket['percentage'] }}%@else<span style="color: #B9B4EA;">–</span>@endif</td>
                        <td class="py-2 px-1 text-center text-xs" style="color: var(--navy);">@if($bucket['percentage'] !== null){{ $bucket['weighted'] }}@else<span style="color: #B9B4EA;">–</span>@endif</td>
                        @endforeach
                        <td class="py-2 px-3 text-center text-sm" style="color: var(--navy);">@if($cg['initial_grade'] !== null){{ $cg['initial_grade'] }}@else<span style="color: #B9B4EA;">–</span>@endif</td>
                        <td class="py-2 px-3 text-center sticky right-24 bg-white"><span class="text-sm font-bold" style="color: var(--navy);">@if($cg['quarterly_grade'] !== null){{ $cg['quarterly_grade'] }}@else<span class="font-normal" style="color: #B9B4EA;">–</span>@endif</span></td>
                        <td class="py-2 px-3 text-center sticky right-0 bg-white">
                            @if($isLocked)
                                <span class="text-sm font-bold {{ ($cg['final_grade'] ?? 0) >= 75 ? 'text-green-600' : 'text-red-500' }}">
                                    {{ $cg['final_grade'] }}
                                </span>
                                <span class="block mx-auto mt-1 text-[10px] font-bold px-2 py-0.5" style="display: inline-block; background: #ECFDF5; border: 1px solid #A7F3D0; color: #15803D; border-radius: 999px;">Posted</span>
                            @else
                                <span class="block mx-auto mb-1 text-[10px] font-bold px-2 py-0.5" style="display: inline-block; background: #FFFBEB; border: 1px solid #FDE68A; color: #B45309; border-radius: 999px;">On hold</span>
                                <input type="number" name="grades[{{ $cg['enrollment_id'] }}][enrollment_id]" value="{{ $cg['enrollment_id'] }}" hidden>
                                <input type="number" name="grades[{{ $cg['enrollment_id'] }}][final_grade]"
                                       value="{{ $cg['final_grade'] ?? $suggested }}"
                                       step="0.01" min="0" max="100"
                                       class="w-20 px-2 py-1 rounded-lg border border-gray-300 text-sm text-center focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                                <span class="block text-[10px] text-gray-400">suggested {{ $suggested ?? '–' }}</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="16" class="py-6 text-center text-gray-400">No students to display. Enroll students first, then add your first assessment from Batch Entry.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @php
        $editableCount = $computedGrades->whereNotIn('status', ['Submitted'])->count();
    @endphp
    @if($editableCount > 0)
    <div class="mt-4 flex justify-end">
        <button type="submit" data-post-btn data-confirm="Post these grades? Locked quarters need an unlock request to change." class="px-6 py-2.5 text-sm font-bold text-white" style="background: var(--navy); border: 1.5px solid var(--navy); border-radius: 10px;" onmouseover="this.style.background='#3B3878';this.style.borderColor='#3B3878'" onmouseout="this.style.background='var(--navy)';this.style.borderColor='var(--navy)'">
            Post Grades
        </button>
    </div>
    @endif
</form>
@endif
