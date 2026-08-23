<table class="w-full text-sm">
    <thead>
        <tr class="border-b border-gray-100 bg-gray-50/50">
            <th class="text-left px-4 py-3 font-semibold text-gray-700">App No.</th>
            <th class="text-left px-4 py-3 font-semibold text-gray-700">Applicant</th>
            <th class="text-left px-4 py-3 font-semibold text-gray-700">Type</th>
            <th class="text-left px-4 py-3 font-semibold text-gray-700">Grade Level</th>
            <th class="text-left px-4 py-3 font-semibold text-gray-700">Strand</th>
            <th class="text-left px-4 py-3 font-semibold text-gray-700">School Year</th>
            <th class="text-left px-4 py-3 font-semibold text-gray-700">Submitted</th>
            <th class="text-left px-4 py-3 font-semibold text-gray-700">Status</th>
            <th class="text-right px-4 py-3 font-semibold text-gray-700">Action</th>
        </tr>
    </thead>
    <tbody class="divide-y divide-gray-50">
        @forelse($admissions as $admission)
        <tr class="hover:bg-gray-50/50 transition">
            <td class="px-4 py-3 font-mono text-xs">{{ $admission->application_number }}</td>
            <td class="px-4 py-3 font-medium text-gray-900">{{ $admission->student->first_name }} {{ $admission->student->last_name }}</td>
            <td class="px-4 py-3">
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                    {{ $admission->application_type === 'New' ? 'bg-blue-100 text-blue-800' : ($admission->application_type === 'Old' ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800') }}">
                    {{ $admission->application_type }}
                </span>
            </td>
            <td class="px-4 py-3 text-gray-700 font-medium">{{ $admission->grade_level }}</td>
            <td class="px-4 py-3 text-gray-600">{{ $admission->strand ?? '—' }}</td>
            <td class="px-4 py-3 text-gray-600">{{ $admission->school_year }}</td>
            <td class="px-4 py-3 text-gray-500 text-xs">{{ $admission->created_at->diffForHumans() }}</td>
            <td class="px-4 py-3">
                @php
                    $statusLower = strtolower($admission->status);
                    $isApproved = str_contains($statusLower, 'approved');
                    $isPending = str_contains($statusLower, 'pending');
                    $isRejected = str_contains($statusLower, 'reject');
                @endphp
                @if($isApproved)
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Approved</span>
                @elseif($isPending)
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Pending</span>
                @elseif($isRejected)
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Rejected</span>
                @else
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">{{ $admission->status }}</span>
                @endif
            </td>
            <td class="px-4 py-3 text-right">
                <a href="{{ route('registrar.admissions.show', $admission) }}"
                   class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium text-blue-700 bg-blue-50 hover:bg-blue-100 transition">
                    Review
                </a>
            </td>
        </tr>
        @empty
        <tr><td colspan="9" class="px-4 py-8 text-center text-gray-400">No admissions found.<br><span class="text-xs">Try adjusting filters or check back later.</span></td></tr>
        @endforelse
    </tbody>
</table>
<div class="p-4">{{ $admissions->links() }}</div>
