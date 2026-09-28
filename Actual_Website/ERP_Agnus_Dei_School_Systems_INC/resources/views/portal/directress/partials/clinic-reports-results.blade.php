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
        <h4 class="text-sm font-semibold text-gray-700 dark:text-[#E8EAF6] mb-3">Top Diagnoses</h4>
        @forelse($topDiagnosis as $diagnosis => $count)
        <div class="flex justify-between text-sm py-1 border-b border-gray-100 dark:border-[#2A2F58] last:border-0 gap-3">
            <span class="text-gray-600 dark:text-[#C1C4DC] truncate">{{ $diagnosis }}</span>
            <span class="font-medium text-gray-900 dark:text-[#E8EAF6]">{{ $count }}</span>
        </div>
        @empty
        <p class="text-xs text-gray-400 dark:text-[#8A90B0]">No diagnoses recorded.</p>
        @endforelse
    </div>
</div>

<div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] overflow-hidden">
    <div class="px-4 py-3 border-b border-gray-200 dark:border-[#2A2F58]">
        <h4 class="text-sm font-semibold text-gray-700 dark:text-[#E8EAF6]">Recent Clinic Visits</h4>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-[#161A33] border-b border-gray-200 dark:border-[#2A2F58]">
                <tr>
                    <th class="text-left px-4 py-2 font-semibold text-gray-600 dark:text-[#C1C4DC]">Visit Date</th>
                    <th class="text-left px-4 py-2 font-semibold text-gray-600 dark:text-[#C1C4DC]">Student</th>
                    <th class="text-left px-4 py-2 font-semibold text-gray-600 dark:text-[#C1C4DC]">Grade</th>
                    <th class="text-left px-4 py-2 font-semibold text-gray-600 dark:text-[#C1C4DC]">Complaint</th>
                    <th class="text-left px-4 py-2 font-semibold text-gray-600 dark:text-[#C1C4DC]">Diagnosis</th>
                    <th class="text-left px-4 py-2 font-semibold text-gray-600 dark:text-[#C1C4DC]">Referred To</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-[#2A2F58]">
                @forelse($recentLogs as $log)
                @php $student = $log->student; $enrollment = $student?->enrollments->where('status','Active')->first(); @endphp
                <tr class="hover:bg-gray-50 dark:hover:bg-[#23274C]">
                    <td class="px-4 py-2 text-gray-900 dark:text-[#E8EAF6]">{{ \Carbon\Carbon::parse($log->visit_date)->format('M d, Y') }}</td>
                    <td class="px-4 py-2 text-gray-900 dark:text-[#E8EAF6]">{{ $student?->first_name }} {{ $student?->last_name }} <span class="text-xs text-gray-400">({{ $student?->student_number }})</span></td>
                    <td class="px-4 py-2 text-gray-600 dark:text-[#C1C4DC] text-xs">{{ $enrollment?->section?->grade_level ?? '—' }}</td>
                    <td class="px-4 py-2 text-gray-600 dark:text-[#C1C4DC] text-xs">{{ $log->complaint ?? '—' }}</td>
                    <td class="px-4 py-2 text-gray-600 dark:text-[#C1C4DC] text-xs">{{ $log->diagnosis ?? '—' }}</td>
                    <td class="px-4 py-2 text-xs">
                        @if($log->referred_to)
                        <span class="px-2 py-0.5 rounded-full bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300">{{ $log->referred_to }}</span>
                        @else
                        <span class="text-gray-400">—</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400 dark:text-[#8A90B0]">No clinic visits in the selected date range.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
