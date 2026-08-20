<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">{{ $class->subject->name ?? 'N/A' }}</h2>
    <p class="text-gray-600 mt-1">{{ $class->grade_level }} - {{ $class->section }} &middot; {{ $class->school_year }}</p>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="p-5 border-b border-gray-100">
        <div class="flex items-center justify-between">
            <h3 class="font-semibold text-gray-900">Master List of Students</h3>
            <span class="text-sm text-gray-500">{{ $activeEnrollments->count() }} student(s)</span>
        </div>
    </div>

    @if($activeEnrollments->isEmpty())
    <div class="p-8 text-center">
        <p class="text-sm text-gray-500">No students enrolled in this class.</p>
    </div>
    @else
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
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
                <tr class="border-b border-gray-50 hover:bg-gray-50/50">
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
    </div>
    @endif
</div>
