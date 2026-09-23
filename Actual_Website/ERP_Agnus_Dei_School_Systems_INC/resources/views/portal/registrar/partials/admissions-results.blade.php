<table class="w-full text-sm">
    <thead>
        <tr class="border-b border-gray-100 dark:border-[#2A2F58] bg-gray-50 dark:bg-[#161A33]/50">
            <th class="text-left px-4 py-3 font-semibold text-gray-700">App No.</th>
            <th class="text-left px-4 py-3 font-semibold text-gray-700">Applicant</th>
            <th class="text-left px-4 py-3 font-semibold text-gray-700">Type</th>
            <th class="text-left px-4 py-3 font-semibold text-gray-700">Grade Level</th>
            <th class="text-left px-4 py-3 font-semibold text-gray-700">Elective</th>
            <th class="text-left px-4 py-3 font-semibold text-gray-700">School Year</th>
            <th class="text-left px-4 py-3 font-semibold text-gray-700">Submitted</th>
            <th class="text-left px-4 py-3 font-semibold text-gray-700">Status</th>
            <th class="text-right px-4 py-3 font-semibold text-gray-700">Action</th>
        </tr>
    </thead>
    <tbody class="divide-y divide-gray-50">
        @forelse($admissions as $admission)
        <tr class="hover:bg-gray-50 dark:hover:bg-[#161A33]/50 transition">
            <td class="px-4 py-3 font-mono text-xs">{{ $admission->application_number }}</td>
            <td class="px-4 py-3 font-medium text-gray-900 dark:text-[#E8EAF6]">{{ $admission->student->first_name }} {{ $admission->student->last_name }}</td>
            <td class="px-4 py-3">
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                    {{ $admission->application_type === 'New' ? 'bg-blue-100 text-blue-800' : ($admission->application_type === 'Old' ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800') }}">
                    {{ $admission->application_type }}
                </span>
            </td>
            <td class="px-4 py-3 text-gray-700 font-medium">{{ $admission->grade_level }}</td>
            <td class="px-4 py-3 text-gray-600 dark:text-[#C1C4DC]">{{ $admission->strand ?? '—' }}</td>
            <td class="px-4 py-3 text-gray-600 dark:text-[#C1C4DC]">{{ $admission->school_year }}</td>
            <td class="px-4 py-3 text-gray-500 dark:text-[#8A90B0] text-xs">{{ $admission->created_at->diffForHumans() }}</td>
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
        <tr>
            <td colspan="9" class="px-4 py-12 text-center">
                <svg class="w-10 h-10 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                <p class="text-sm font-medium text-gray-500 dark:text-[#8A90B0]">No admissions found.</p>
                <p class="text-xs text-gray-400 mt-1">Try adjusting your filters or check back later.</p>
            </td>
        </tr>
        @endforelse
    </tbody>
</table>
<div class="p-4">{{ $admissions->links() }}</div>
