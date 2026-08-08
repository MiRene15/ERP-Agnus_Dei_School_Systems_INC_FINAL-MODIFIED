<h3 class="font-semibold text-gray-900 mb-4">{{ $selectedGrade }} — {{ $selectedYear }}</h3>
<div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-gray-200">
                <th class="text-left py-3 px-2 font-medium text-gray-600">Student</th>
                <th class="text-left py-3 px-2 font-medium text-gray-600">Section</th>
                @foreach($subjects as $subject)
                <th class="text-center py-3 px-2 font-medium text-gray-600 text-xs">{{ Str::limit($subject->name, 12) }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($enrollments as $enrollment)
            <tr class="border-b border-gray-100">
                <td class="py-2 px-2 font-medium text-gray-900">{{ $enrollment->student->first_name }} {{ $enrollment->student->last_name }}</td>
                <td class="py-2 px-2 text-gray-600">{{ $enrollment->section->section_name ?? '—' }}</td>
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
                <td colspan="{{ 2 + $subjects->count() }}" class="py-6 text-center text-gray-500 text-sm">No students found for {{ $selectedGrade }}.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">
    {{ $enrollments->links() }}
</div>
