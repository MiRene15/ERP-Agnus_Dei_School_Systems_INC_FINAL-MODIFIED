<div x-data='{
    selectedGrade: "all",
    selectedIds: [],
    schoolYear: "",
    boxes(grade) {
        return Array.from(this.$root.querySelectorAll(".promo-checkbox[data-grade=\"" + grade + "\"]"));
    },
    globalBox() { return this.$root.querySelector("[data-global-select]"); },
    scopeBox(grade) { return this.$root.querySelector("[data-scope-header=\"" + grade + "\"]"); },
    setGrade(grade, checked) {
        const ids = this.boxes(grade).map(b => b.value);
        this.selectedIds = checked
            ? Array.from(new Set(this.selectedIds.concat(ids)))
            : this.selectedIds.filter(id => !ids.includes(id));
        this.boxes(grade).forEach(b => { b.checked = checked; });
        this.syncHeaders();
    },
    clearGrade(grade) { this.setGrade(grade, false); },
    selectQualified(grade) {
        const targets = this.boxes(grade).filter(b => b.dataset.qualified === "1");
        this.selectedIds = Array.from(new Set(this.selectedIds.concat(targets.map(b => b.value))));
        targets.forEach(b => { b.checked = true; });
        this.syncHeaders();
    },
    setAll(checked) {
        const all = Array.from(this.$root.querySelectorAll(".promo-checkbox"));
        this.selectedIds = checked ? all.map(b => b.value) : [];
        all.forEach(b => { b.checked = checked; });
        this.syncHeaders();
    },
    selectAllQualified() {
        const targets = Array.from(this.$root.querySelectorAll(".promo-checkbox[data-qualified=\"1\"]"));
        this.selectedIds = Array.from(new Set(targets.map(b => b.value)));
        targets.forEach(b => { b.checked = true; });
        this.syncHeaders();
    },
    clearSelection() {
        this.$root.querySelectorAll(".promo-checkbox").forEach(b => { b.checked = false; });
        this.selectedIds = [];
        this.syncHeaders();
    },
    syncHeaders() {
        const all = Array.from(this.$root.querySelectorAll(".promo-checkbox"));
        const scopes = Array.from(new Set(all.map(b => b.dataset.grade)));
        scopes.forEach(grade => {
            const list = this.boxes(grade);
            const checked = list.filter(b => b.checked).length;
            const box = this.scopeBox(grade);
            if (box) {
                box.checked = list.length > 0 && checked === list.length;
                box.indeterminate = checked > 0 && checked < list.length;
            }
        });
        const checkedAll = all.filter(b => b.checked).length;
        const g = this.globalBox();
        if (g) {
            g.checked = all.length > 0 && checkedAll === all.length;
            g.indeterminate = checkedAll > 0 && checkedAll < all.length;
        }
    }
}'>

<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900 dark:text-[#E8EAF6]">End-of-Year Promotion</h2>
    <p class="text-gray-600 dark:text-[#C1C4DC] mt-1">Select an action for each student to process end-of-year promotion, retention, graduation, or transfer.</p>
</div>

<div class="mb-4">
    <div class="flex gap-1.5 flex-wrap">
        <button type="button" @click="selectedGrade = 'all'" :class="selectedGrade === 'all' ? 'bg-gray-900 dark:bg-[#1A1E3B] text-white border-gray-900' : 'bg-white dark:bg-[#1A1E3B] text-gray-600 dark:text-[#C1C4DC] border-gray-200 dark:border-[#2A2F58] hover:bg-gray-50 dark:hover:bg-[#23274C]'" class="px-3 py-1.5 rounded-full text-xs font-semibold border transition">All Grades</button>
        @foreach($enrollments->keys() as $gl)
        <button type="button" @click="selectedGrade = '{{ $gl }}'" :class="selectedGrade === '{{ $gl }}' ? 'bg-gray-900 dark:bg-[#1A1E3B] text-white border-gray-900' : 'bg-white dark:bg-[#1A1E3B] text-gray-600 dark:text-[#C1C4DC] border-gray-200 dark:border-[#2A2F58] hover:bg-gray-50 dark:hover:bg-[#23274C]'" class="px-3 py-1.5 rounded-full text-xs font-semibold border transition">{{ $gl }}</button>
        @endforeach
    </div>
</div>

<div x-show="selectedIds.length > 0" x-transition class="mb-5 flex items-center justify-between gap-3 flex-wrap p-3 px-4 bg-blue-50 dark:bg-[rgba(96,165,250,0.12)] border border-blue-200 dark:border-[rgba(96,165,250,0.25)] rounded-lg sticky top-2 z-10">
    <div class="flex items-center gap-3">
        <span class="text-sm font-medium text-blue-700 dark:text-[#60A5FA]"><span x-text="selectedIds.length"></span> selected</span>
        <button type="button" @click="clearSelection()" class="text-xs text-blue-600 dark:text-[#60A5FA] hover:underline">Clear selection</button>
    </div>
    <form method="POST" action="{{ route('admin.promotion.batch-promote') }}" onsubmit="return confirm('Batch promote selected qualified students?')" class="flex items-center gap-2">
        @csrf
        <template x-for="id in selectedIds" :key="id">
            <input type="hidden" name="enrollment_ids[]" :value="id">
        </template>
        <input type="hidden" name="school_year" :value="schoolYear">
        <select name="action" required class="rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-2 py-1 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
            <option value="promote">Promote</option>
            <option value="retain">Retain</option>
            <option value="graduate">Graduate</option>
        </select>
        <button type="submit" class="px-4 py-1.5 rounded-lg text-xs font-semibold text-white bg-green-600 hover:bg-green-700">Batch Promote</button>
    </form>
</div>

@if($enrollments->isEmpty())
<div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-6 text-center">
    <p class="text-sm text-gray-500 dark:text-[#8A90B0] py-4">No active enrollments found.</p>
</div>
@else
<form method="POST" action="{{ route('admin.promotion.process') }}" onsubmit="return confirm('Process all selected actions? This will create new enrollments and carry over fees.')">
    @csrf
    <div class="mb-5 flex items-center justify-between gap-4 flex-wrap">
        <div class="flex items-center gap-3">
            <label class="text-sm font-medium text-gray-700 dark:text-[#C1C4DC] whitespace-nowrap">New School Year:</label>
            <select name="school_year" x-model="schoolYear" required class="rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                <option value="">Select school year</option>
                @foreach($schoolYears as $sy)
                <option value="{{ $sy }}">{{ $sy }}</option>
                @endforeach
                <option value="{{ date('Y') . '-' . (date('Y') + 1) }}">{{ date('Y') . '-' . (date('Y') + 1) }} (New)</option>
            </select>
        </div>
        <div class="flex items-center gap-4 flex-wrap">
            <label class="inline-flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-[#C1C4DC] cursor-pointer">
                <input type="checkbox" data-global-select @change="setAll($event.target.checked)" class="rounded border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] text-blue-600 focus:ring-blue-500">
                Select all students (all grade levels)
            </label>
            <button type="button" @click="selectAllQualified()" class="px-2.5 py-1.5 rounded-lg text-xs font-medium bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300 hover:bg-green-100 dark:hover:bg-green-900/30 transition">Qualified only (all)</button>
            <button type="button" @click="clearSelection()" class="px-2.5 py-1.5 rounded-lg text-xs font-medium bg-gray-100 dark:bg-[#23274C] text-gray-600 dark:text-[#C1C4DC] hover:bg-gray-200 dark:hover:bg-[#2A2F58] transition">Clear selection</button>
        </div>
    </div>

    @foreach($enrollments as $gradeLevel => $gradeEnrollments)
    <div x-show="selectedGrade === 'all' || selectedGrade === '{{ $gradeLevel }}'" class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-6 mb-5">
        <div class="flex items-center flex-wrap gap-3 mb-4">
            <h3 class="font-semibold text-gray-900 dark:text-[#E8EAF6]">{{ $gradeLevel }} <span class="text-sm font-normal text-gray-500 dark:text-[#8A90B0]">({{ $gradeEnrollments->count() }} student(s))</span></h3>
            <label class="inline-flex items-center gap-2 text-xs font-medium text-gray-700 dark:text-[#C1C4DC] cursor-pointer">
                <input type="checkbox" data-scope-header="{{ $gradeLevel }}" @change="setGrade('{{ $gradeLevel }}', $event.target.checked)" class="rounded border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] text-blue-600 focus:ring-blue-500">
                Select all in {{ $gradeLevel }}
            </label>
            <button type="button" @click="selectQualified('{{ $gradeLevel }}')" class="text-xs text-blue-600 dark:text-[#60A5FA] hover:underline">Select qualified in {{ $gradeLevel }}</button>
            <button type="button" @click="clearGrade('{{ $gradeLevel }}')" class="text-xs text-gray-500 dark:text-[#8A90B0] hover:underline">Clear {{ $gradeLevel }}</button>
        </div>
        <div class="overflow-x-auto rounded-lg border border-gray-100 dark:border-[#2A2F58]" x-data="{ filter: 'all' }">
            <p class="text-xs text-gray-400 dark:text-[#8A90B0] px-4 py-2 bg-gray-50 dark:bg-[#161A33] rounded-t-lg">GWA &ge;{{ $passingGrade ?? 75 }} and no failing subject (&lt;{{ $passingGrade ?? 75 }}) = qualified. Failing or no grades = not qualified — review manually.</p>
            <div class="flex gap-1.5 my-3 flex-wrap px-1">
                <button type="button" @click="filter='all'" :class="filter==='all' ? 'bg-gray-900 dark:bg-[#1A1E3B] text-white' : 'bg-gray-100 dark:bg-[#23274C] text-gray-600 dark:text-[#C1C4DC] hover:bg-gray-200 dark:hover:bg-[#2A2F58]'" class="px-2.5 py-1 rounded-full text-xs font-medium transition">All</button>
                <button type="button" @click="filter='qualified'" :class="filter==='qualified' ? 'bg-green-600 text-white' : 'bg-gray-100 dark:bg-[#23274C] text-gray-600 dark:text-[#C1C4DC] hover:bg-gray-200 dark:hover:bg-[#2A2F58]'" class="px-2.5 py-1 rounded-full text-xs font-medium transition">Qualified</button>
                <button type="button" @click="filter='not'" :class="filter==='not' ? 'bg-red-600 text-white' : 'bg-gray-100 dark:bg-[#23274C] text-gray-600 dark:text-[#C1C4DC] hover:bg-gray-200 dark:hover:bg-[#2A2F58]'" class="px-2.5 py-1 rounded-full text-xs font-medium transition">Not qualified</button>
                <button type="button" @click="filter='none'" :class="filter==='none' ? 'bg-gray-900 dark:bg-[#1A1E3B] text-white' : 'bg-gray-100 dark:bg-[#23274C] text-gray-600 dark:text-[#C1C4DC] hover:bg-gray-200 dark:hover:bg-[#2A2F58]'" class="px-2.5 py-1 rounded-full text-xs font-medium transition">No grades</button>
                <button type="button" @click="filter='balance'" :class="filter==='balance' ? 'bg-amber-600 text-white' : 'bg-gray-100 dark:bg-[#23274C] text-gray-600 dark:text-[#C1C4DC] hover:bg-gray-200 dark:hover:bg-[#2A2F58]'" class="px-2.5 py-1 rounded-full text-xs font-medium transition">With balance</button>
            </div>
            <table class="w-full min-w-[960px] text-sm">
                <thead>
                    <tr class="border-y border-gray-200 dark:border-[#2A2F58] bg-gray-50 dark:bg-[#161A33]">
                        <th class="w-10 px-4 py-3">
                            <span class="sr-only">Select</span>
                        </th>
                        <th class="text-left py-3 px-4 font-medium text-gray-600 dark:text-[#C1C4DC] whitespace-nowrap">Student</th>
                        <th class="text-left py-3 px-4 font-medium text-gray-600 dark:text-[#C1C4DC] whitespace-nowrap">Section</th>
                        <th class="text-left py-3 px-4 font-medium text-gray-600 dark:text-[#C1C4DC] whitespace-nowrap">GWA</th>
                        <th class="text-left py-3 px-4 font-medium text-gray-600 dark:text-[#C1C4DC] whitespace-nowrap">Qualification</th>
                        <th class="text-left py-3 px-4 font-medium text-gray-600 dark:text-[#C1C4DC] whitespace-nowrap">Balance</th>
                        <th class="text-left py-3 px-4 font-medium text-gray-600 dark:text-[#C1C4DC] whitespace-nowrap w-44">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($gradeEnrollments as $enrollment)
                    @php
                        $isGrade12 = $gradeLevel === 'Grade 12';
                        $passing = $passingGrade ?? 75;
                        $grades = $enrollment->grades ?? collect();
                        // Final per school year per subject: avg of 3 terms per class
                        $grouped = $grades->groupBy('class_id');
                        $finals = $grouped->map(fn($g) => round($g->avg('final_grade'), 2));
                        $avg = $finals->isNotEmpty() ? round($finals->avg(), 2) : null;
                        $failCount = $finals->filter(fn($f) => $f < $passing)->count();
                        $subjectCount = $finals->count();
                        $qualified = $avg !== null && $avg >= $passing && $failCount === 0;
                        $balVal = $enrollment->student->ledger?->balance ?? 0;
                    @endphp
                    <tr data-qualified="{{ $qualified ? '1' : '0' }}" class="border-b border-gray-100 dark:border-[#2A2F58] hover:bg-gray-50 dark:hover:bg-[#1E2447] transition-colors" x-show="filter==='all' || (filter==='qualified' && {{ $qualified ? 'true':'false' }}) || (filter==='not' && {{ (!$qualified && $avg!==null) ? 'true':'false' }}) || (filter==='none' && {{ $avg===null ? 'true':'false' }}) || (filter==='balance' && {{ $balVal>0 ? 'true':'false' }})" x-data="{ open: false }">
                        <td class="py-3 px-4">
                            <input type="checkbox" class="promo-checkbox rounded border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] text-blue-600 focus:ring-blue-500" value="{{ $enrollment->id }}" data-grade="{{ $gradeLevel }}" data-qualified="{{ $qualified ? '1' : '0' }}" x-model="selectedIds" @change="syncHeaders()">
                        </td>
                        <td class="py-3 px-4">
                            <span class="font-medium text-gray-900 dark:text-[#E8EAF6]">{{ $enrollment->student->first_name }} {{ $enrollment->student->last_name }}</span>
                            <span class="block text-xs text-gray-400 dark:text-[#8A90B0]">{{ $enrollment->student->student_number }} · {{ $subjectCount }} subject(s)</span>
                            @if($subjectCount>0)
                                <button type="button" @click="open=!open" class="text-xs text-blue-600 dark:text-[#60A5FA] hover:underline mt-1" x-text="open ? 'Hide grades' : 'View grades'"></button>
                                <div x-show="open" x-cloak class="mt-2 text-xs bg-gray-50 dark:bg-[#161A33] rounded-lg p-3 space-y-1">
                                    @foreach($grouped as $classId => $gGroup)
                                        @php $final = round($gGroup->avg('final_grade'),2); $subj = $gGroup->first()->schoolClass->subject->name ?? $gGroup->first()->schoolClass->subject_code ?? 'Subject'; @endphp
                                        <div class="flex justify-between gap-3"><span class="text-gray-600 dark:text-[#C1C4DC]">{{ $subj }}</span><span class="{{ $final < $passing ? 'text-red-600 font-semibold' : 'text-gray-900 dark:text-[#E8EAF6]' }}">{{ number_format($final,2) }}</span></div>
                                    @endforeach
                                </div>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-gray-700 dark:text-[#C1C4DC] whitespace-nowrap">{{ $enrollment->section->section_name ?? 'N/A' }}</td>
                        <td class="py-3 px-4 whitespace-nowrap">
                            @if($avg === null)
                                <span class="text-gray-400 text-xs">No grades</span>
                            @else
                                <span class="font-semibold {{ $avg >= $passing ? 'text-gray-900 dark:text-[#E8EAF6]' : 'text-red-600' }}">{{ number_format($avg, 2) }}</span>
                                @if($failCount > 0)
                                    <span class="block text-xs text-red-500">{{ $failCount }} failing</span>
                                @endif
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
                        <td class="py-3 px-4 whitespace-nowrap">
                            <span class="{{ $balVal > 0 ? 'text-red-600 font-medium' : 'text-green-600' }}">
                                ₱ {{ number_format($balVal, 2) }}
                            </span>
                        </td>
                        <td class="py-3 px-4">
                            <div class="flex flex-col gap-1.5" x-data="{ act: '' }" x-init="act = $el.querySelector('select').value">
                            <select name="actions[{{ $enrollment->id }}]" required @change="act = $event.target.value" class="w-full rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-2 py-1.5 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                                <option value="">Select action</option>
                                @if(!$isGrade12)
                                <option value="promote" @if($qualified) selected @endif>Promote to {{ $gradeLevel === 'Grade 11' ? 'Grade 12' : 'next grade' }}</option>
                                <option value="retain" @if(!$qualified && $avg !== null) selected @endif>Retain in {{ $gradeLevel }}</option>
                                @endif
                                <option value="graduate" {{ $isGrade12 ? 'selected' : '' }}>Graduate</option>
                                <option value="transfer">Transfer Out</option>
                                <option value="dropped">Dropped Out</option>
                            </select>
                            <input type="text" name="reasons[{{ $enrollment->id }}]" placeholder="Reason (required for Transfer/Dropped)" x-show="act === 'transfer' || act === 'dropped'" x-cloak class="w-full rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-2 py-1.5 text-xs focus:ring-2 focus:ring-blue-500 outline-none" maxlength="500">
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endforeach

    <div class="flex justify-between items-center mt-6 pt-4 border-t border-gray-100 dark:border-[#2A2F58]">
        <a href="{{ route('admin.audit-logs', ['event' => 'Promoted']) }}" class="text-xs text-gray-500 dark:text-[#8A90B0] hover:text-blue-600 dark:hover:text-[#60A5FA] underline">View promotion audit logs &rarr;</a>
        <button type="submit" class="px-6 py-3 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
            Process All Actions
        </button>
    </div>
</form>
@endif
</div>
