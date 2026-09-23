<h3 class="font-semibold text-gray-900 dark:text-[#E8EAF6] mb-4">{{ $selectedGrade }} — {{ $selectedYear }}</h3>
<div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-gray-200">
                <th class="text-left py-3 px-2 font-medium text-gray-600 dark:text-[#C1C4DC]">Student</th>
                <th class="text-left py-3 px-2 font-medium text-gray-600 dark:text-[#C1C4DC]">Section</th>
                @foreach($subjects as $subject)
                <th class="text-center py-3 px-2 font-medium text-gray-600 dark:text-[#C1C4DC] text-xs whitespace-normal break-words min-w-[90px]" title="{{ $subject->name }}">{{ $subject->name }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($enrollments as $enrollment)
            <tr class="border-b border-gray-100 dark:border-[#2A2F58]">
                <td class="py-2 px-2 font-medium text-gray-900 dark:text-[#E8EAF6]">{{ $enrollment->student->first_name }} {{ $enrollment->student->last_name }}</td>
                <td class="py-2 px-2 text-gray-600 dark:text-[#C1C4DC]">{{ $enrollment->section->section_name ?? '—' }}</td>
                @foreach($subjects as $subject)
                @php $class = $enrollment->subjects->firstWhere('subject_id', $subject->id); @endphp
                @php $grade = $class ? $enrollment->grades->firstWhere('class_id', $class->id) : null; @endphp
                <td class="py-2 px-2 text-center text-gray-700">
                    {{ $grade ? number_format($grade->final_grade, 1) : '—' }}
                </td>
                @endforeach
            </tr>
            @empty
            <tr>
                <td colspan="{{ 2 + $subjects->count() }}" class="py-6 text-center text-gray-500 dark:text-[#8A90B0] text-sm">No students found for {{ $selectedGrade }}.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">
    {{ $enrollments->links() }}
</div>
