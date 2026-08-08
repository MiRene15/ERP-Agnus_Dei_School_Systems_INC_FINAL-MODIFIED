<div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-gray-200">
                <th class="text-left py-3 px-2 font-medium text-gray-600">Date</th>
                <th class="text-left py-3 px-2 font-medium text-gray-600">Student</th>
                <th class="text-left py-3 px-2 font-medium text-gray-600">Complaint</th>
                <th class="text-left py-3 px-2 font-medium text-gray-600">Diagnosis</th>
                <th class="text-left py-3 px-2 font-medium text-gray-600">Treatment</th>
                <th class="text-left py-3 px-2 font-medium text-gray-600">Referred To</th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs as $log)
            <tr class="border-b border-gray-100">
                <td class="py-2 px-2 text-gray-900 whitespace-nowrap">{{ \Carbon\Carbon::parse($log->incident_date)->format('M d, Y') }}</td>
                <td class="py-2 px-2">
                    <span class="font-medium text-gray-900">{{ $log->student->first_name ?? '' }} {{ $log->student->last_name ?? '' }}</span>
                </td>
                <td class="py-2 px-2 text-gray-600">{{ $log->complaint ?? $log->symptoms ?? 'N/A' }}</td>
                <td class="py-2 px-2 text-gray-600">{{ $log->diagnosis ?? 'N/A' }}</td>
                <td class="py-2 px-2 text-gray-600">{{ $log->treatment ?? 'N/A' }}</td>
                <td class="py-2 px-2 text-gray-600">{{ $log->referred_to ?? '—' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="py-6 text-center text-gray-500 text-sm">No clinic logs found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="p-4">{{ $logs->links() }}</div>
