<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-4">
        <p class="text-xs text-gray-500 dark:text-[#8A90B0] uppercase tracking-wide">Total Visits</p>
        <p class="text-2xl font-bold text-gray-900 dark:text-[#E8EAF6] mt-1">{{ $totalVisits }}</p>
        <p class="text-xs text-gray-400 dark:text-[#8A90B0]">{{ $dateFrom }} &rarr; {{ $dateTo }}</p>
    </div>
    <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-4">
        <p class="text-xs text-gray-500 dark:text-[#8A90B0] uppercase tracking-wide">Unique Patients</p>
        <p class="text-2xl font-bold text-gray-900 dark:text-[#E8EAF6] mt-1">{{ $uniquePatients }}</p>
    </div>
    <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-4">
        <p class="text-xs text-gray-500 dark:text-[#8A90B0] uppercase tracking-wide">Referred Out</p>
        <p class="text-2xl font-bold text-orange-600 mt-1">{{ $referralsOut }}</p>
    </div>
    <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-4">
        <p class="text-xs text-gray-500 dark:text-[#8A90B0] uppercase tracking-wide">Active Days</p>
        <p class="text-2xl font-bold text-gray-900 dark:text-[#E8EAF6] mt-1">{{ $activeDays }}</p>
        <p class="text-xs text-gray-400 dark:text-[#8A90B0]">{{ $activeDays > 0 ? round($totalVisits / $activeDays, 1) : 0 }} visit(s) per active day</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-4">
        <h4 class="text-sm font-semibold text-gray-700 dark:text-[#E8EAF6] mb-3">Visits by Grade Level</h4>
        @forelse($byGrade as $grade => $count)
        <div class="flex justify-between text-sm py-1 border-b border-gray-100 dark:border-[#2A2F58] last:border-0">
            <span class="text-gray-600 dark:text-[#C1C4DC]">{{ $grade }}</span>
            <span class="font-medium text-gray-900 dark:text-[#E8EAF6]">{{ $count }}</span>
        </div>
        @empty
        <p class="text-xs text-gray-400 dark:text-[#8A90B0]">No visits in range.</p>
        @endforelse
    </div>
    <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-4">
        <h4 class="text-sm font-semibold text-gray-700 dark:text-[#E8EAF6] mb-3">Top Symptoms</h4>
        @forelse($topSymptoms as $symptom => $count)
        <div class="flex justify-between text-sm py-1 border-b border-gray-100 dark:border-[#2A2F58] last:border-0 gap-3">
            <span class="text-gray-600 dark:text-[#C1C4DC] truncate">{{ $symptom }}</span>
            <span class="font-medium text-gray-900 dark:text-[#E8EAF6]">{{ $count }}</span>
        </div>
        @empty
        <p class="text-xs text-gray-400 dark:text-[#8A90B0]">No symptoms recorded.</p>
        @endforelse
    </div>
    <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-4">
        <h4 class="text-sm font-semibold text-gray-700 dark:text-[#E8EAF6] mb-3">Open Cases</h4>
        <p class="text-2xl font-bold {{ ($openCases ?? 0) > 0 ? 'text-red-600' : 'text-green-600' }}">{{ $openCases ?? 0 }}</p>
        <p class="text-xs text-gray-400 dark:text-[#8A90B0] mt-1">Cases needing follow-up hold the student’s clearance. Close them in Consultation Logs.</p>
    </div>
</div>

<div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] overflow-hidden">
    <div class="px-4 pt-4">
        <h4 class="text-sm font-semibold text-gray-700 dark:text-[#E8EAF6]">Visit Details</h4>
        <p class="text-xs text-gray-400 dark:text-[#8A90B0] mt-1 mb-3">Same visits you see in Consultation Logs. Visible to clinic staff only.</p>
    </div>
    @if($logs->isEmpty())
        <p class="px-4 pb-4 text-sm text-gray-500 dark:text-[#8A90B0]">Nothing in this period — try wider dates.</p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs text-gray-500 dark:text-[#8A90B0] border-y border-gray-100 dark:border-[#2A2F58]">
                        <th class="px-4 py-2 font-medium">Student</th>
                        <th class="px-4 py-2 font-medium">Visit Date</th>
                        <th class="px-4 py-2 font-medium">Symptoms</th>
                        <th class="px-4 py-2 font-medium">Treatment</th>
                        <th class="px-4 py-2 font-medium">Referred To</th>
                        <th class="px-4 py-2 font-medium">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($logs as $log)
                    <tr class="border-b border-gray-100 dark:border-[#2A2F58] last:border-0">
                        <td class="px-4 py-2 text-gray-900 dark:text-[#E8EAF6]">{{ trim(($log->student->first_name ?? '') . ' ' . ($log->student->last_name ?? '')) }}</td>
                        <td class="px-4 py-2 text-gray-600 dark:text-[#C1C4DC] whitespace-nowrap">{{ $log->visit_date }}</td>
                        <td class="px-4 py-2 text-gray-600 dark:text-[#C1C4DC]">{{ $log->symptoms }}</td>
                        <td class="px-4 py-2 text-gray-600 dark:text-[#C1C4DC]">{{ $log->treatment }}</td>
                        <td class="px-4 py-2 text-gray-600 dark:text-[#C1C4DC]">{{ $log->referred_to ?? '—' }}</td>
                        <td class="px-4 py-2">@if($log->is_open)<span class="text-red-600 font-medium">Open</span>@else<span class="text-green-600">Closed</span>@endif</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
