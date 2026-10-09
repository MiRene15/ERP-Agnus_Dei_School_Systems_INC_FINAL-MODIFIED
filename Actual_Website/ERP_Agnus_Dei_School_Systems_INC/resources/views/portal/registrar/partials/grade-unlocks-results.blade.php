@if($pending->isEmpty())
    <p class="text-sm text-gray-500 py-4 text-center">No pending requests.</p>
@else
<div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-gray-200 dark:border-[#2A2F58]">
                <th class="py-2 px-2 w-8"><input type="checkbox" onclick="toggleAllUnlocks(this)" title="Select all" class="rounded border-gray-300"></th>
                <th class="text-left py-2 px-2 font-medium text-gray-600 dark:text-[#8A90B0]">Class</th>
                <th class="text-left py-2 px-2 font-medium text-gray-600 dark:text-[#8A90B0]">Term</th>
                <th class="text-left py-2 px-2 font-medium text-gray-600 dark:text-[#8A90B0]">Teacher / Reason</th>
                <th class="text-center py-2 px-2 font-medium text-gray-600 dark:text-[#8A90B0]">Decision</th>
            </tr>
        </thead>
        <tbody>
            @foreach($pending as $req)
            <tr class="border-b border-gray-50 dark:border-[#2A2F58]">
                <td class="py-2 px-2 text-center"><input type="checkbox" name="selected[]" value="{{ $req->id }}" class="unlock-select rounded border-gray-300"></td>
                <td class="py-2 px-2 font-medium text-gray-900 dark:text-[#E8EAF6]">{{ $req->schoolClass->subject->name ?? '—' }}<span class="block text-xs text-gray-500 font-normal">{{ $req->schoolClass->grade_level ?? '' }} {{ $req->schoolClass->section ?? '' }}</span></td>
                <td class="py-2 px-2 text-gray-600 dark:text-[#C1C4DC]">{{ $req->grading_period }}</td>
                <td class="py-2 px-2 text-xs text-gray-600 dark:text-[#C1C4DC] max-w-sm">{{ $req->requester?->name ?? '—' }}: {{ $req->reason }}<span class="block text-gray-400 mt-1">{{ $req->created_at->format('M d, Y h:i A') }}</span></td>
                <td class="py-2 px-2 text-center whitespace-nowrap">
                    @php $routePrefix = auth()->user()->role_id === 9 ? 'principal' : 'registrar'; @endphp
                    <form method="POST" action="{{ route("{$routePrefix}.grade-unlocks.approve", $req) }}" class="inline">
                        @csrf
                        <button type="submit" class="px-3 py-1.5 text-xs font-semibold text-white bg-green-600 hover:bg-green-700 rounded-lg">Unlock</button>
                    </form>
                    <form method="POST" action="{{ route("{$routePrefix}.grade-unlocks.reject", $req) }}" onsubmit="return confirm('Reject this unlock request? Grades stay submitted.')" class="inline">
                        @csrf
                        <button type="submit" class="px-3 py-1.5 text-xs font-semibold text-red-600 hover:text-red-800 bg-red-50 hover:bg-red-100 rounded-lg">Reject</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif
