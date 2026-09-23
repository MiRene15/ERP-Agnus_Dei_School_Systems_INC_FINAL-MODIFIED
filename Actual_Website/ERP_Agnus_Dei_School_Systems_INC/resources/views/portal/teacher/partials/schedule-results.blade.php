<div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-6 overflow-x-auto">
    <div class="grid grid-cols-5 gap-3 min-w-[700px]">
        @foreach($weekDays as $day)
        <div>
            <div class="text-center font-bold text-sm text-gray-700 dark:text-[#C1C4DC] bg-gray-50 dark:bg-[#161A33] rounded-lg py-2 mb-2 border border-gray-100 dark:border-[#2A2F58]">{{ $day }}</div>
            <div class="space-y-2">
                @forelse($schedulesByDay[$day] as $slot)
                <div class="p-3 rounded-lg border border-blue-100 bg-blue-50 text-xs">
                    <p class="font-semibold text-gray-900 dark:text-[#E8EAF6]">{{ \Carbon\Carbon::parse($slot->start_time)->format('h:i A') }} - {{ \Carbon\Carbon::parse($slot->end_time)->format('h:i A') }}</p>
                    <p class="text-gray-700 dark:text-[#C1C4DC] mt-1">{{ $slot->schoolClass->subject->name ?? 'N/A' }}</p>
                    <p class="text-gray-500 dark:text-[#8A90B0]">{{ $slot->schoolClass->grade_level }} - {{ $slot->schoolClass->section }}</p>
                    <p class="text-gray-400 dark:text-[#8A90B0]">{{ $slot->room ?? $slot->schoolClass->room ?? 'N/A' }}</p>
                </div>
                @empty
                <div class="p-3 rounded-lg bg-gray-50 dark:bg-[#161A33] border border-dashed border-gray-200 dark:border-[#2A2F58] text-center">
                    <p class="text-xs text-gray-400 dark:text-[#8A90B0]">No class</p>
                </div>
                @endforelse
            </div>
        </div>
        @endforeach
    </div>
</div>
