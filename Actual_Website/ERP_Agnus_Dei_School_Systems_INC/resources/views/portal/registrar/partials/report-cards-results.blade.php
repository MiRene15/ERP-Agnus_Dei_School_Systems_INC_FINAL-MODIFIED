@forelse($enrollments as $grade => $group)
<div class="p-6 border-b border-gray-100 last:border-b-0">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">Grade {{ $grade }}</h3>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Student</th>
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Section</th>
                    <th class="text-left py-3 px-2 font-medium text-gray-600">LRN</th>
                    <th class="text-right py-3 px-2 font-medium text-gray-600">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($group as $enrollment)
                <tr class="border-b border-gray-100 hover:bg-gray-50">
                    <td class="py-3 px-2">
                        <span class="font-medium text-gray-900">{{ $enrollment->student->first_name }} {{ $enrollment->student->last_name }}</span>
                    </td>
                    <td class="py-3 px-2 text-gray-700">{{ $enrollment->section->section_name ?? 'N/A' }}</td>
                    <td class="py-3 px-2 text-gray-700">{{ $enrollment->student->student_number ?? 'N/A' }}</td>
                    <td class="py-3 px-2 text-right">
                        <a href="{{ route('registrar.report-cards.show', $enrollment) }}" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-indigo-600 bg-indigo-50 hover:bg-indigo-100 rounded-lg mr-1">View</a>
                        <a href="{{ route('registrar.report-cards.print', $enrollment) }}" target="_blank" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg">Print PDF</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@empty
<div class="p-6">
    <p class="text-sm text-gray-500 text-center py-4">No active enrollments found for this school year.</p>
</div>
@endforelse
