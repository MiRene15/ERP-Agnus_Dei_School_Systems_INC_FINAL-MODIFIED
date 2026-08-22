<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">End-of-Year Promotion</h2>
    <p class="text-gray-600 mt-1">Select an action for each student to process end-of-year promotion, retention, graduation, or transfer.</p>
</div>

@if($enrollments->isEmpty())
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 text-center">
    <p class="text-sm text-gray-500 py-4">No active enrollments found.</p>
</div>
@else
<form method="POST" action="{{ route('admin.promotion.process') }}" onsubmit="return confirm('Process all selected actions? This will create new enrollments and carry over fees.')">
    @csrf
    <div class="mb-4 flex items-center gap-3">
        <label class="text-sm font-medium text-gray-700">New School Year:</label>
        <select name="school_year" required class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
            <option value="">Select school year</option>
            @foreach($schoolYears as $sy)
            <option value="{{ $sy }}">{{ $sy }}</option>
            @endforeach
            <option value="{{ date('Y') . '-' . (date('Y') + 1) }}">{{ date('Y') . '-' . (date('Y') + 1) }} (New)</option>
        </select>
    </div>

    @foreach($enrollments as $gradeLevel => $gradeEnrollments)
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-4">
        <h3 class="font-semibold text-gray-900 mb-3">{{ $gradeLevel }} <span class="text-sm font-normal text-gray-500">({{ $gradeEnrollments->count() }} student(s))</span></h3>
        <div class="overflow-x-auto" x-data="{ filter: 'all' }">
            <p class="text-xs text-gray-400 mb-2">GWA &ge;{{ $passingGrade ?? 75 }} and no failing subject (&lt;{{ $passingGrade ?? 75 }}) = qualified. Failing or no grades = not qualified — review manually.</p>
            <div class="flex gap-1.5 mb-3 flex-wrap">
                <button type="button" @click="filter='all'" :class="filter==='all' ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-600'" class="px-2.5 py-1 rounded-full text-xs font-medium">All</button>
                <button type="button" @click="filter='qualified'" :class="filter==='qualified' ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-600'" class="px-2.5 py-1 rounded-full text-xs font-medium">Qualified</button>
                <button type="button" @click="filter='not'" :class="filter==='not' ? 'bg-red-600 text-white' : 'bg-gray-100 text-gray-600'" class="px-2.5 py-1 rounded-full text-xs font-medium">Not qualified</button>
                <button type="button" @click="filter='none'" :class="filter==='none' ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-600'" class="px-2.5 py-1 rounded-full text-xs font-medium">No grades</button>
                <button type="button" @click="filter='balance'" :class="filter==='balance' ? 'bg-amber-600 text-white' : 'bg-gray-100 text-gray-600'" class="px-2.5 py-1 rounded-full text-xs font-medium">With balance</button>
            </div>
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="text-left py-3 px-2 font-medium text-gray-600">Student</th>
                        <th class="text-left py-3 px-2 font-medium text-gray-600">Section</th>
                        <th class="text-left py-3 px-2 font-medium text-gray-600">GWA</th>
                        <th class="text-left py-3 px-2 font-medium text-gray-600">Qualification</th>
                        <th class="text-left py-3 px-2 font-medium text-gray-600">Balance</th>
                        <th class="text-left py-3 px-2 font-medium text-gray-600">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($gradeEnrollments as $enrollment)
                    @php
                        $isGrade12 = $gradeLevel === 'Grade 12';
                        $passing = $passingGrade ?? 75;
                        $grades = $enrollment->grades ?? collect();
                        $avg = $grades->isNotEmpty() ? round($grades->avg('final_grade'), 2) : null;
                        $failCount = $grades->where('final_grade', '<', $passing)->count();
                        $subjectCount = $grades->count();
                        $qualified = $avg !== null && $avg >= $passing && $failCount === 0;
                        $balVal = $enrollment->student->ledger?->balance ?? 0;
                    @endphp
                    <tr class="border-b border-gray-100" x-show="filter==='all' || (filter==='qualified' && {{ $qualified ? 'true':'false' }}) || (filter==='not' && {{ (!$qualified && $avg!==null) ? 'true':'false' }}) || (filter==='none' && {{ $avg===null ? 'true':'false' }}) || (filter==='balance' && {{ $balVal>0 ? 'true':'false' }})" x-data="{ open: false }">
                        <td class="py-3 px-2">
                            <span class="font-medium text-gray-900">{{ $enrollment->student->first_name }} {{ $enrollment->student->last_name }}</span>
                            <span class="block text-xs text-gray-400">{{ $enrollment->student->student_number }} · {{ $subjectCount }} subject(s)</span>
                            @if($subjectCount>0)
                                <button type="button" @click="open=!open" class="text-xs text-blue-600 hover:underline mt-1" x-text="open ? 'Hide grades' : 'View grades'"></button>
                                <div x-show="open" x-cloak class="mt-1 text-xs bg-gray-50 rounded-lg p-2 space-y-0.5">
                                    @foreach($grades as $g)
                                        <div class="flex justify-between"><span class="text-gray-600">{{ $g->schoolClass->subject->name ?? $g->schoolClass->subject_code ?? 'Subject' }} ({{ $g->grading_period }})</span><span class="{{ $g->final_grade < $passing ? 'text-red-600 font-semibold' : 'text-gray-900' }}">{{ number_format($g->final_grade,2) }}</span></div>
                                    @endforeach
                                </div>
                            @endif
                        </td>
                        <td class="py-3 px-2 text-gray-700">{{ $enrollment->section->section_name ?? 'N/A' }}</td>
                        <td class="py-3 px-2">
                            @if($avg === null)
                                <span class="text-gray-400 text-xs">No grades</span>
                            @else
                                <span class="font-semibold {{ $avg >= $passing ? 'text-gray-900' : 'text-red-600' }}">{{ number_format($avg, 2) }}</span>
                                @if($failCount > 0)
                                    <span class="block text-xs text-red-500">{{ $failCount }} failing</span>
                                @endif
                            @endif
                        </td>
                        <td class="py-3 px-2">
                            @if($avg === null)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">No grades</span>
                            @elseif($qualified)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Qualified</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">Not qualified</span>
                            @endif
                        </td>
                        <td class="py-3 px-2">
                            <span class="{{ $balVal > 0 ? 'text-red-600 font-medium' : 'text-green-600' }}">
                                ₱ {{ number_format($balVal, 2) }}
                            </span>
                        </td>
                        <td class="py-3 px-2">
                            <div class="flex flex-col gap-1" x-data="{ act: '' }" x-init="act = $el.querySelector('select').value" @change="act = $el.querySelector('select').value">
                            <select name="actions[{{ $enrollment->id }}]" required @change="act = $event.target.value" class="rounded-lg border border-gray-300 px-2 py-1 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                                <option value="">Select action</option>
                                @if(!$isGrade12)
                                <option value="promote" @if($qualified) selected @endif>Promote to {{ $gradeLevel === 'Grade 11' ? 'Grade 12' : 'next grade' }}</option>
                                <option value="retain" @if(!$qualified && $avg !== null) selected @endif>Retain in {{ $gradeLevel }}</option>
                                @endif
                                <option value="graduate" {{ $isGrade12 ? 'selected' : '' }}>Graduate</option>
                                <option value="transfer">Transfer Out</option>
                                <option value="dropped">Dropped Out</option>
                            </select>
                            <input type="text" name="reasons[{{ $enrollment->id }}]" placeholder="Reason (required for Transfer/Dropped)" x-show="act === 'transfer' || act === 'dropped'" x-cloak class="rounded-lg border border-gray-300 px-2 py-1 text-xs focus:ring-2 focus:ring-blue-500 outline-none" maxlength="500">
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endforeach

    <div class="flex justify-between items-center mt-4">
        <a href="{{ route('admin.audit-logs', ['event' => 'Promoted']) }}" class="text-xs text-gray-500 hover:text-blue-600 underline">View promotion audit logs &rarr;</a>
        <button type="submit" class="px-6 py-3 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
            Process All Actions
        </button>
    </div>
</form>
@endif
