<div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-gray-200">
                <th class="text-left py-3 px-2 font-medium text-gray-600 dark:text-[#C1C4DC]">Date</th>
                <th class="text-left py-3 px-2 font-medium text-gray-600 dark:text-[#C1C4DC]">Student</th>
                <th class="text-left py-3 px-2 font-medium text-gray-600 dark:text-[#C1C4DC]">Complaint</th>
                <th class="text-left py-3 px-2 font-medium text-gray-600 dark:text-[#C1C4DC]">Sickness / Injury</th>
                <th class="text-left py-3 px-2 font-medium text-gray-600 dark:text-[#C1C4DC]">Treatment</th>
                <th class="text-left py-3 px-2 font-medium text-gray-600 dark:text-[#C1C4DC]">Notes</th>
                <th class="text-left py-3 px-2 font-medium text-gray-600 dark:text-[#C1C4DC]">Referred To</th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs as $log)
            <tr class="border-b border-gray-100 dark:border-[#2A2F58]">
                <td class="py-2 px-2 text-gray-900 dark:text-[#E8EAF6] whitespace-nowrap">{{ \Carbon\Carbon::parse($log->incident_date)->format('M d, Y') }}</td>
                <td class="py-2 px-2">
                    <span class="font-medium text-gray-900 dark:text-[#E8EAF6]">{{ $log->student->first_name ?? '' }} {{ $log->student->last_name ?? '' }}</span>
                </td>
                <td class="py-2 px-2 text-gray-600 dark:text-[#C1C4DC]">{{ $log->complaint ?? $log->symptoms ?? 'N/A' }}</td>
                <td class="py-2 px-2 text-gray-600 dark:text-[#C1C4DC]">{{ $log->diagnosis ?? 'N/A' }}</td>
                <td class="py-2 px-2 text-gray-600 dark:text-[#C1C4DC]">{{ $log->treatment ?? 'N/A' }}</td>
                <td class="py-2 px-2 text-gray-600 dark:text-[#C1C4DC] max-w-[200px] truncate" title="{{ $log->notes ?? '' }}">{{ \Illuminate\Support\Str::limit($log->notes ?? '', 50) ?: '—' }}</td>
                <td class="py-2 px-2 text-gray-600 dark:text-[#C1C4DC]">{{ $log->referred_to ?? '—' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="py-6 text-center text-gray-500 dark:text-[#8A90B0] text-sm">No clinic logs found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="p-4">{{ $logs->links() }}</div>
