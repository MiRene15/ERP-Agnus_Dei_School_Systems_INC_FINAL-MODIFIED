<div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-gray-200">
                <th class="text-left py-3 px-2 font-medium text-gray-600">Student</th>
                <th class="text-left py-3 px-2 font-medium text-gray-600">Section</th>
                <th class="text-left py-3 px-2 font-medium text-gray-600">Reason</th>
                <th class="text-left py-3 px-2 font-medium text-gray-600">Status</th>
                <th class="text-left py-3 px-2 font-medium text-gray-600">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($withdrawals as $w)
            <tr class="border-b border-gray-100">
                <td class="py-3 px-2">
                    <span class="font-medium text-gray-900">{{ $w->student->first_name }} {{ $w->student->last_name }}</span>
                </td>
                <td class="py-3 px-2 text-gray-700">{{ $w->enrollment->section->grade_level }} - {{ $w->enrollment->section->section_name }}</td>
                <td class="py-3 px-2 text-gray-700 max-w-xs truncate">{{ $w->reason }}</td>
                <td class="py-3 px-2">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                        {{ $w->status === 'Pending' ? 'bg-yellow-100 text-yellow-700' : ($w->status === 'Approved' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700') }}">
                        {{ $w->status }}
                    </span>
                </td>
                <td class="py-3 px-2">
                    @if($w->status === 'Pending')
                    <div class="flex gap-1">
                        <form method="POST" action="{{ route('registrar.withdrawals.approve', $w) }}" class="inline">
                            @csrf
                            <button type="submit" class="px-2 py-1 text-xs font-medium text-green-600 hover:text-green-800 bg-green-50 hover:bg-green-100 rounded">Approve</button>
                        </form>
                        <form method="POST" action="{{ route('registrar.withdrawals.reject', $w) }}" class="inline" onsubmit="return confirm('Reject this withdrawal?')">
                            @csrf
                            <input type="text" name="remarks" placeholder="Optional remarks..." class="w-32 px-2 py-1 text-xs border border-gray-200 rounded">
                            <button type="submit" class="px-2 py-1 text-xs font-medium text-red-600 hover:text-red-800 bg-red-50 hover:bg-red-100 rounded">Reject</button>
                        </form>
                    </div>
                    @else
                        <span class="text-xs text-gray-400">{{ $w->status }} by {{ $w->processor?->name ?? 'System' }}</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="5" class="py-8 text-center text-gray-400">No withdrawal requests.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="p-4">{{ $withdrawals->links() }}</div>
