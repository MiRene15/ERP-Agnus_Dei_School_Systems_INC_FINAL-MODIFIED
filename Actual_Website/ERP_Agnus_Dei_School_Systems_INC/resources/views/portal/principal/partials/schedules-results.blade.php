<h3 class="font-semibold text-gray-900 mb-4">{{ $selectedGrade }} — {{ $selectedYear }}</h3>
<div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-gray-200">
                <th class="text-left py-3 px-2 font-medium text-gray-600">Subject</th>
                <th class="text-left py-3 px-2 font-medium text-gray-600">Teacher</th>
                <th class="text-left py-3 px-2 font-medium text-gray-600">Section</th>
                @foreach($days as $day)
                <th class="text-left py-3 px-2 font-medium text-gray-600">{{ substr($day, 0, 3) }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($classes as $class)
            <tr class="border-b border-gray-100">
                <td class="py-2 px-2 font-medium text-gray-900">{{ $class->subject->name ?? '—' }}</td>
                <td class="py-2 px-2 text-gray-600">{{ $class->teacher->name ?? '—' }}</td>
                <td class="py-2 px-2 text-gray-600">{{ $class->section }}</td>
                @foreach($days as $day)
                @php $slot = $class->schedules->firstWhere('day_of_week', $day); @endphp
                <td class="py-2 px-2 text-gray-600 text-xs">
                    @if($slot)
                        {{ substr($slot->start_time, 0, 5) }}–{{ substr($slot->end_time, 0, 5) }}
                        <br><span class="text-gray-400">{{ $slot->room ?? '' }}</span>
                    @else
                        <span class="text-gray-300">—</span>
                    @endif
                </td>
                @endforeach
            </tr>
            @empty
            <tr>
                <td colspan="{{ 3 + count($days) }}" class="py-6 text-center text-gray-500 text-sm">No classes scheduled for {{ $selectedGrade }}.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
