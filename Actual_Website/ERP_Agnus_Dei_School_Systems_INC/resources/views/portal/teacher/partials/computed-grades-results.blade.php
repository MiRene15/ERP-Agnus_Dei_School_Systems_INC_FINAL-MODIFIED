@if(!$selectedClassId)
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <h3 class="font-semibold text-gray-900 mb-4">Select a Class</h3>
    @if($classes->isEmpty())
    <p class="text-sm text-gray-500">No classes assigned.</p>
    @else
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
<div class="mb-4 flex items-center gap-3">
    <a href="{{ route('teacher.computed-grades') }}" class="text-sm text-blue-600 hover:underline">&larr; Change Class</a>
    <span class="text-gray-300">|</span>
    <h3 class="font-semibold text-gray-900">{{ $class->subject->name ?? 'N/A' }} — {{ $class->grade_level }} {{ $class->section }}</h3>
</div>

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

<form method="POST" action="{{ route('teacher.computed-grades.batch-submit') }}">
    @csrf
    <input type="hidden" name="class_id" value="{{ $selectedClassId }}">
    <input type="hidden" name="grading_period" value="{{ $selectedPeriod }}">

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="text-left py-3 px-3 font-semibold text-gray-600 border-b">#</th>
                        <th class="text-left py-3 px-3 font-semibold text-gray-600 border-b">Student</th>
                        <th class="text-center py-3 px-2 font-semibold text-gray-600 border-b">Written Work<br><span class="text-[10px] font-normal text-gray-400">20%</span></th>
                        <th class="text-center py-3 px-2 font-semibold text-gray-600 border-b">Quiz<br><span class="text-[10px] font-normal text-gray-400">20%</span></th>
                        <th class="text-center py-3 px-2 font-semibold text-gray-600 border-b">Seatwork<br><span class="text-[10px] font-normal text-gray-400">20%</span></th>
                        <th class="text-center py-3 px-2 font-semibold text-gray-600 border-b">Exam<br><span class="text-[10px] font-normal text-gray-400">40%</span></th>
                        <th class="text-center py-3 px-2 font-semibold text-gray-600 border-b">Computed</th>
                        <th class="text-center py-3 px-3 font-semibold text-gray-600 border-b">Final Grade</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($computedGrades as $idx => $cg)
                    <tr class="border-b border-gray-50 hover:bg-gray-50/50">
                        <td class="py-2 px-3 text-gray-500">{{ $idx + 1 }}</td>
                        <td class="py-2 px-3">
                            <p class="font-medium text-gray-900">{{ $cg['student']->first_name }} {{ $cg['student']->last_name }}</p>
                            <p class="text-[10px] text-gray-400">{{ $cg['student']->student_number }}</p>
                        </td>
                        @foreach(['Written Work', 'Quiz', 'Seatwork', 'Exam'] as $type)
                        <td class="py-2 px-2 text-center">
                            @if($cg['categories'][$type]['max'] > 0)
                            <span class="text-xs text-gray-700">{{ $cg['categories'][$type]['raw'] }}/{{ $cg['categories'][$type]['max'] }}</span>
                            <span class="block text-[10px] text-gray-400">{{ $cg['categories'][$type]['percentage'] }}%</span>
                            @else
                            <span class="text-xs text-gray-300">—</span>
                            @endif
                        </td>
                        @endforeach
                        <td class="py-2 px-2 text-center">
                            <span class="text-sm font-semibold {{ $cg['computed_grade'] >= 75 ? 'text-green-600' : 'text-red-500' }}">
                                {{ $cg['computed_grade'] }}%
                            </span>
                        </td>
                        <td class="py-2 px-3 text-center">
                            @if($cg['status'] === 'Submitted')
                                <span class="text-sm font-bold {{ $cg['final_grade'] >= 75 ? 'text-green-600' : 'text-red-500' }}">
                                    {{ $cg['final_grade'] }}
                                </span>
                                <span class="block text-[10px] text-green-600">Submitted</span>
                            @else
                                <input type="number" name="grades[{{ $cg['enrollment_id'] }}][enrollment_id]" value="{{ $cg['enrollment_id'] }}" hidden>
                                <input type="number" name="grades[{{ $cg['enrollment_id'] }}][final_grade]"
                                       value="{{ $cg['final_grade'] ?? $cg['computed_grade'] }}"
                                       step="0.01" min="0" max="100"
                                       class="w-20 px-2 py-1 rounded-lg border border-gray-300 text-sm text-center focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="py-6 text-center text-gray-400">No students to display.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($computedGrades->isNotEmpty())
    <div class="mt-4 flex justify-end">
        <button type="submit" class="px-6 py-2.5 rounded-lg text-sm font-semibold text-white transition hover:opacity-90" style="background: var(--navy);">
            Batch Save Final Grades
        </button>
    </div>
    @endif
</form>
@endif
