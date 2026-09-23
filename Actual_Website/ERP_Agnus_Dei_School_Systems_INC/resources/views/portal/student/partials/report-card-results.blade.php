<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <div class="mb-4">
        <p class="text-sm text-gray-500">LRN: <span class="font-medium text-gray-900">{{ $enrollment->student->student_number ?? 'N/A' }}</span></p>
        <p class="text-sm text-gray-500">Adviser: <span class="font-medium text-gray-900">{{ $enrollment->section?->adviser?->name ?? 'N/A' }}</span></p>
        @if($enrollment->strand)
        <p class="text-sm text-gray-500">Elective: <span class="font-medium text-gray-900">{{ $enrollment->strand }}</span></p>
        @endif
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm border-collapse">
            <thead>
                <tr class="bg-gray-50">
                    <th class="text-left py-3 px-3 font-medium text-gray-600 border border-gray-200">Subject</th>
                    <th class="text-left py-3 px-2 font-medium text-gray-600 border border-gray-200">Teacher</th>
                    @foreach($gradingPeriods as $period)
                    <th class="text-center py-3 px-2 font-medium text-gray-600 border border-gray-200">{{ $period }}</th>
                    @endforeach
                    <th class="text-center py-3 px-2 font-medium text-gray-600 border border-gray-200">Final</th>
                    <th class="text-center py-3 px-2 font-medium text-gray-600 border border-gray-200">Remarks</th>
                </tr>
            </thead>
            <tbody>
                @forelse($subjects as $subject)
                <tr class="border-b border-gray-100">
                    <td class="py-3 px-3 font-medium text-gray-900 border border-gray-200">{{ $subject->subject }}</td>
                    <td class="py-3 px-2 text-xs text-gray-600 border border-gray-200">{{ $subject->teachers ?? 'N/A' }}</td>
                    @foreach($gradingPeriods as $period)
                    <td class="py-3 px-2 text-center text-gray-700 border border-gray-200">{{ $subject->{$period} }}</td>
                    @endforeach
                    <td class="py-3 px-2 text-center font-semibold text-gray-900 border border-gray-200">{{ $subject->final }}</td>
                    <td class="py-3 px-2 text-center border border-gray-200">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $subject->remarks === 'Passed' ? 'bg-green-100 text-green-700' : ($subject->remarks === 'Failed' ? 'bg-red-100 text-red-700' : '') }}">
                            {{ $subject->remarks }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="py-6 text-center text-sm text-gray-400 border border-gray-200">No grades available yet.</td>
                </tr>
                @endforelse
            </tbody>
            @if($subjects->isNotEmpty())
            <tfoot>
                <tr class="bg-gray-50 dark:bg-[#23274C] font-semibold border-t-2 border-gray-800 dark:border-[#3B4172]">
                    <td colspan="5" class="py-3 px-3 text-right text-gray-900 dark:text-[#E8EAF6] border border-gray-200 dark:border-[#3B4172]">GENERAL AVERAGE</td>
                    <td class="py-3 px-2 text-center text-gray-900 dark:text-[#E8EAF6] border border-gray-200 dark:border-[#3B4172]">{{ $overallAverage ? number_format($overallAverage, 2) : '—' }}</td>
                    <td class="py-3 px-2 text-center border border-gray-200 dark:border-[#3B4172]">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ ($overallAverage ?? 0) >= 75 ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300' : (($overallAverage ?? 0) > 0 ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300' : 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400') }}">
                            @if(($overallAverage ?? 0) >= 75) Passed @elseif(($overallAverage ?? 0) > 0) Failed @else — @endif
                        </span>
                    </td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>
