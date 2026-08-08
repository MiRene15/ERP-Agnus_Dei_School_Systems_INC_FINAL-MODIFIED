@if($logs->isEmpty())
    <p class="text-sm text-gray-500 text-center py-8">No activity logs found matching your filters.</p>
@else
<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Time</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">User</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Event</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Description</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @foreach($logs as $log)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 text-sm text-gray-500 whitespace-nowrap">{{ $log->created_at->format('M d, Y g:i:s A') }}</td>
                <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $log->causer?->name ?? 'System' }}</td>
                <td class="px-4 py-3">
                    @php
                        $color = match($log->event) {
                            'Login', 'Logout' => 'bg-blue-100 text-blue-700',
                            'Created' => 'bg-green-100 text-green-700',
                            'Updated' => 'bg-yellow-100 text-yellow-700',
                            'Deleted', 'Archived' => 'bg-red-100 text-red-700',
                            'Payment' => 'bg-emerald-100 text-emerald-700',
                            'Password Changed', 'Password Reset' => 'bg-orange-100 text-orange-700',
                            default => 'bg-gray-100 text-gray-700',
                        };
                    @endphp
                    <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full {{ $color }}">{{ $log->event }}</span>
                </td>
                <td class="px-4 py-3 text-sm text-gray-600">{{ $log->description }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
<div class="px-4 py-3 border-t border-gray-100">
    {{ $logs->links() }}
</div>
@endif
