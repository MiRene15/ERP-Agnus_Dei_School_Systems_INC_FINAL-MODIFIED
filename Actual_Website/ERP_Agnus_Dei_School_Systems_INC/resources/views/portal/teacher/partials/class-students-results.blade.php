<div class="mb-6 flex items-start justify-between gap-4 flex-wrap">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">{{ $class->subject->name ?? 'N/A' }}</h2>
        <p class="text-gray-600 mt-1">{{ $class->grade_level }} - {{ $class->section }} &middot; {{ $class->school_year }}</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('teacher.attendance', $class) }}" class="px-4 py-2 rounded-lg text-sm font-semibold text-white transition hover:opacity-90" style="background: var(--navy);">Attendance</a>
        <a href="{{ route('teacher.assessments', $class) }}" class="px-4 py-2 rounded-lg text-sm font-semibold text-white transition hover:opacity-90" style="background: var(--navy);">Batch Entry</a>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="p-5 border-b border-gray-100">
        <div class="flex items-center justify-between mb-3">
            <h3 class="font-semibold text-gray-900">Master List of Students</h3>
            <span class="text-sm text-gray-500">{{ $activeEnrollments->count() }} student(s)</span>
        </div>
        <input type="text" oninput="filterMasterList(this)" placeholder="Search name or LRN..." class="rounded-lg border border-gray-300 px-3 py-2 text-sm w-full focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" />
    </div>

    @if($activeEnrollments->isEmpty())
    <div class="p-8 text-center">
        <p class="text-sm text-gray-500">No students enrolled in this class.</p>
    </div>
    @else
    <div class="overflow-x-auto">
        <table class="w-full text-sm" data-master-list>
            <thead>
                <tr class="bg-gray-50">
                    <th class="text-left py-3 px-4 font-semibold text-gray-600 border-b">#</th>
                    <th class="text-left py-3 px-4 font-semibold text-gray-600 border-b">Student Name</th>
                    <th class="text-left py-3 px-4 font-semibold text-gray-600 border-b">Student No.</th>
                    <th class="text-left py-3 px-4 font-semibold text-gray-600 border-b">LRN</th>
                    <th class="text-left py-3 px-4 font-semibold text-gray-600 border-b">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($activeEnrollments as $idx => $enrollment)
                <tr class="border-b border-gray-50 hover:bg-gray-50/50" data-filter-text="{{ strtolower($enrollment->student->first_name . ' ' . ($enrollment->student->middle_name ?? '') . ' ' . $enrollment->student->last_name . ' ' . ($enrollment->student->student_number ?? '') . ' ' . ($enrollment->student->legacy_lrn ?? '')) }}">
                    <td class="py-3 px-4 text-gray-500">{{ $idx + 1 }}</td>
                    <td class="py-3 px-4">
                        <p class="font-medium text-gray-900">{{ $enrollment->student->first_name }} {{ $enrollment->student->middle_name ? $enrollment->student->middle_name . ' ' : '' }}{{ $enrollment->student->last_name }}</p>
                    </td>
                    <td class="py-3 px-4 text-gray-600">{{ $enrollment->student->student_number }}</td>
                    <td class="py-3 px-4 text-gray-600">{{ $enrollment->student->legacy_lrn ?? 'N/A' }}</td>
                    <td class="py-3 px-4">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Active</span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <p data-master-list-no-match class="hidden text-sm text-gray-500 text-center py-6">No students match — clear the search.</p>
    </div>
    @endif
</div>
<script>
if (typeof filterMasterList !== 'function') {
    function filterMasterList(input) {
        var q = (input.value || '').toLowerCase();
        var scope = input.closest('div.bg-white');
        var shown = 0;
        scope.querySelectorAll('[data-master-list] [data-filter-text]').forEach(function (row) {
            var hit = row.getAttribute('data-filter-text').includes(q);
            row.style.display = hit ? '' : 'none';
            if (hit) shown++;
        });
        var note = scope.querySelector('[data-master-list-no-match]');
        if (note) note.classList.toggle('hidden', shown > 0);
    }
}
</script>
