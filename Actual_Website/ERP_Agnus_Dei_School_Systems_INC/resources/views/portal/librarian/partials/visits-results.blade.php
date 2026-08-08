<div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
                <th class="text-left px-4 py-2 font-semibold text-gray-600">Student</th>
                <th class="text-left px-4 py-2 font-semibold text-gray-600">Time In</th>
                <th class="text-left px-4 py-2 font-semibold text-gray-600">Time Out</th>
                <th class="text-left px-4 py-2 font-semibold text-gray-600">Duration</th>
                <th class="text-left px-4 py-2 font-semibold text-gray-600">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($visits as $visit)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-2 text-gray-900">{{ $visit->student->first_name }} {{ $visit->student->last_name }}</td>
                <td class="px-4 py-2 text-gray-500 text-xs">{{ $visit->time_in?->format('M d, Y h:i A') }}</td>
                <td class="px-4 py-2 text-gray-500 text-xs">{{ $visit->time_out?->format('M d, Y h:i A') ?? '—' }}</td>
                <td class="px-4 py-2 text-gray-500 text-xs">
                    @if($visit->time_out)
                        {{ $visit->time_in->diffForHumans($visit->time_out, true) }}
                    @else
                        <span class="text-green-600 font-medium">In Library</span>
                    @endif
                </td>
                <td class="px-4 py-2">
                    @if(!$visit->time_out)
                    <form method="POST" action="{{ route('librarian.visits.clock-out', $visit) }}" class="inline">
                        @csrf @method('PATCH')
                        <button type="submit" class="text-xs font-semibold text-orange-700 bg-orange-50 hover:bg-orange-100 px-3 py-1 rounded-lg transition">Clock Out</button>
                    </form>
                    @else
                    <span class="text-xs text-gray-400">—</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400">No visits recorded.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="p-4">{{ $visits->links() }}</div>
