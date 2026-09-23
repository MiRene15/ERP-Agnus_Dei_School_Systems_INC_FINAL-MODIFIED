@if($logs->isEmpty())
    <p class="text-sm text-gray-500 dark:text-[#8A90B0] text-center py-8">No activity logs found matching your filters.</p>
@else
<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50 dark:bg-[#161A33]">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Time</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">User</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Event</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Description</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-[#2A2F58]">
            @foreach($logs as $log)
            <tr class="hover:bg-gray-50 dark:hover:bg-[#1E2447]">
                <td class="px-4 py-3 text-sm text-gray-500 dark:text-[#8A90B0] whitespace-nowrap">{{ $log->created_at->format('M d, Y g:i:s A') }}</td>
                <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-[#E8EAF6]">{{ $log->causer?->name ?? 'System' }}</td>
                <td class="px-4 py-3">
                    @php
                        $color = match($log->event) {
                            'Login', 'Logout' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
                            'Created', 'Book Created', 'Fee Schedule Created', 'Graduation Fee Created', 'School Year Created' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
                            'Updated', 'Book Updated', 'Fee Schedule Updated', 'Graduation Fee Updated', 'Discount Updated', 'Grades Saved', 'Grade Table Saved', 'Student Assessments Saved', 'Assessments Saved' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300',
                            'Deleted', 'Book Deleted', 'Fee Schedule Deleted', 'Graduation Fee Deleted', 'Archived' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
                            'Payment' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
                            'Password Changed', 'Password Reset' => 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300',
                            'Promoted', 'Retained', 'Graduated' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300',
                            'Transferred', 'Dropped' => 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300',
                            'Grades Submitted', 'Batch Grades Submitted' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300',
                            'Clinic Log Created' => 'bg-pink-100 text-pink-700 dark:bg-pink-900/30 dark:text-pink-300',
                            'Book Borrowed' => 'bg-cyan-100 text-cyan-700 dark:bg-cyan-900/30 dark:text-cyan-300',
                            'Book Returned', 'Returned' => 'bg-teal-100 text-teal-700 dark:bg-teal-900/30 dark:text-teal-300',
                            'Library Clock In', 'Library Clock Out' => 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-300',
                            'Deactivated', 'Reactivated' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
                            'Withdrawal Requested', 'Withdrawal Approved', 'Withdrawal Rejected' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300',
                            'Approved' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
                            'Admission Rejected', 'Requirement Verified', 'All Requirements Verified' => 'bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-300',
                            'Fee Schedule Created', 'Graduation Fee Assigned', 'Graduation Fee Payment Toggled', 'School Year Lock Toggled' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
                            'Status Changed' => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                            default => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                        };
                    @endphp
                    <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full {{ $color }}">{{ $log->event }}</span>
                </td>
                <td class="px-4 py-3 text-sm text-gray-600 dark:text-[#C1C4DC]">{{ $log->description }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
<div class="px-4 py-3 border-t border-gray-100 dark:border-[#2A2F58]">
    {{ $logs->links() }}
</div>
@endif
