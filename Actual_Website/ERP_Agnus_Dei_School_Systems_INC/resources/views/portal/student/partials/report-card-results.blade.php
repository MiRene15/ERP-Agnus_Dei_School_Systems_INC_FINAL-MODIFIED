<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <div class="mb-4">
        <p class="text-sm text-gray-500">LRN: <span class="font-medium text-gray-900">{{ $enrollment->student->student_number ?? 'N/A' }}</span></p>
        <p class="text-sm text-gray-500">Adviser: <span class="font-medium text-gray-900">{{ $enrollment->section?->adviser?->name ?? 'N/A' }}</span></p>
        @if($enrollment->strand)
        <p class="text-sm text-gray-500">Strand: <span class="font-medium text-gray-900">{{ $enrollment->strand }}</span></p>
        @endif
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm border-collapse">
            <thead>
                <tr class="bg-gray-50">
                    <th class="text-left py-3 px-3 font-medium text-gray-600 border border-gray-200">Subject</th>
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
                    <td colspan="6" class="py-6 text-center text-sm text-gray-400 border border-gray-200">No grades available yet.</td>
                </tr>
                @endforelse
            </tbody>
            @if($subjects->isNotEmpty())
            <tfoot>
                <tr class="bg-gray-50 font-semibold">
                    <td class="py-3 px-3 text-gray-900 border border-gray-200">General Average</td>
                    <td colspan="{{ count($gradingPeriods) }}" class="py-3 px-2 text-center text-gray-900 border border-gray-200"></td>
                    <td class="py-3 px-2 text-center text-gray-900 border border-gray-200">{{ $overallAverage ? number_format($overallAverage, 2) : '—' }}</td>
                    <td class="py-3 px-2 text-center border border-gray-200">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ ($overallAverage ?? 0) >= 75 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                            {{ ($overallAverage ?? 0) >= 75 ? 'Passed' : 'Failed' }}
                        </span>
                    </td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>
