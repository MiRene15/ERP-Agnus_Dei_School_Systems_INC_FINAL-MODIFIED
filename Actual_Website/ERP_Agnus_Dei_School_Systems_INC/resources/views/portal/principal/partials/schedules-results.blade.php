<h3 class="font-semibold text-gray-900 dark:text-[#E8EAF6] mb-4">{{ $selectedGrade }} — {{ $selectedYear }}</h3>
@php $grouped = $classes->groupBy('section'); @endphp
@forelse($grouped as $sectionName => $sectionClasses)
    <h4 class="font-medium text-gray-800 dark:text-[#C1C4DC] mt-6 mb-2">Section {{ $sectionName }} <span class="text-xs font-normal text-gray-400 dark:text-[#8A90B0]">({{ $sectionClasses->count() }} subjects)</span></h4>
    <div class="overflow-x-auto mb-6">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200 dark:border-[#2A2F58]">
                    <th class="text-left py-3 px-2 font-medium text-gray-600 dark:text-[#8A90B0]">Subject</th>
                    <th class="text-left py-3 px-2 font-medium text-gray-600 dark:text-[#8A90B0]">Teacher</th>
                    @foreach($days as $day)
                    <th class="text-left py-3 px-2 font-medium text-gray-600 dark:text-[#8A90B0]">{{ substr($day, 0, 3) }}</th>
                    @endforeach
                    <th class="text-left py-3 px-2 font-medium text-gray-600 dark:text-[#8A90B0]">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sectionClasses->unique('subject_id') as $class)
                <tr class="border-b border-gray-100 dark:border-[#2A2F58] hover:bg-blue-50/30 dark:hover:bg-[rgba(59,130,246,0.08)]">
                    <td class="py-2 px-2 font-medium text-gray-900 dark:text-[#E8EAF6]">{{ $class->subject->name ?? '—' }}</td>
                    <td class="py-2 px-2 text-gray-600 dark:text-[#C1C4DC]">{{ $class->teacher->name ?? '—' }}</td>
                    @foreach($days as $day)
                    @php $slot = $class->schedules->firstWhere('day_of_week', $day); @endphp
                    <td class="py-2 px-2 text-gray-600 dark:text-[#C1C4DC] text-xs">
                        @if($slot)
                            <a href="{{ route('principal.schedules.edit', $slot) }}" class="hover:text-blue-600 dark:hover:text-[#60A5FA] hover:underline" title="Edit schedule">
                                {{ substr($slot->start_time, 0, 5) }}–{{ substr($slot->end_time, 0, 5) }}
                            </a>
                            <br><span class="text-gray-400 dark:text-[#8A90B0]">{{ $slot->room ?? '' }}</span>
                        @else
                            <span class="text-gray-300 dark:text-[#3B4172]">—</span>
                        @endif
                    </td>
                    @endforeach
                    <td class="py-2 px-2">
                        @php $anySlot = $class->schedules->first(); @endphp
                        @if($anySlot)
                        <form method="POST" action="{{ route('principal.schedules.destroy', $anySlot) }}" onsubmit="return confirm('Delete schedule for {{ $class->subject->name ?? '' }} on {{ $anySlot->day_of_week }} {{ substr($anySlot->start_time,0,5) }}-{{ substr($anySlot->end_time,0,5) }}? This will be logged.');" class="inline">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-500 hover:text-red-700 dark:text-[#F87171] text-xs underline">Delete</button>
                        </form>
                        @else
                        <span class="text-gray-300 dark:text-[#3B4172] text-xs">—</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@empty
    <div class="py-6 text-center text-gray-500 dark:text-[#8A90B0] text-sm">No classes scheduled for {{ $selectedGrade }}.</div>
@endforelse
