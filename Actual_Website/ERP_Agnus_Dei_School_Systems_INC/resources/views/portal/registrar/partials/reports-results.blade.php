@if($total === 0)
<div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-6 text-center">
    <p class="text-sm font-semibold text-gray-900 dark:text-[#E8EAF6]">No active enrollments</p>
    <p class="text-xs text-gray-500 dark:text-[#8A90B0] mt-1">There is nothing to count yet. New admissions will appear here once enrolled.</p>
</div>
@else
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-4">
        <p class="text-xs text-gray-500 dark:text-[#8A90B0] uppercase tracking-wide">Total Enrolled</p>
        <p class="text-2xl font-bold text-gray-900 dark:text-[#E8EAF6] mt-1">{{ $total }}</p>
        <p class="text-xs text-gray-400 dark:text-[#8A90B0]">active enrollments</p>
    </div>
    <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-4">
        <p class="text-xs text-gray-500 dark:text-[#8A90B0] uppercase tracking-wide">Grade Levels</p>
        <p class="text-2xl font-bold text-gray-900 dark:text-[#E8EAF6] mt-1">{{ $byGrade->count() }}</p>
        <p class="text-xs text-gray-400 dark:text-[#8A90B0]">with active students</p>
    </div>
    <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-4">
        <p class="text-xs text-gray-500 dark:text-[#8A90B0] uppercase tracking-wide">Sections</p>
        <p class="text-2xl font-bold text-gray-900 dark:text-[#E8EAF6] mt-1">{{ $bySection->count() }}</p>
        <p class="text-xs text-gray-400 dark:text-[#8A90B0]">occupied</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] overflow-hidden">
        <div class="px-4 py-3 bg-gray-50 dark:bg-[#161A33] border-b border-gray-200 dark:border-[#2A2F58]">
            <h4 class="text-sm font-semibold text-gray-700 dark:text-[#E8EAF6]">By Grade Level</h4>
        </div>
        <div class="p-4">
            @foreach($byGrade as $grade => $count)
            <div class="flex justify-between text-sm py-1.5 border-b border-gray-100 dark:border-[#2A2F58] last:border-0">
                <span class="text-gray-600 dark:text-[#C1C4DC]">{{ $grade }}</span>
                <span class="font-medium text-gray-900 dark:text-[#E8EAF6]">{{ $count }}</span>
            </div>
            @endforeach
        </div>
    </div>

    <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] overflow-hidden">
        <div class="px-4 py-3 bg-gray-50 dark:bg-[#161A33] border-b border-gray-200 dark:border-[#2A2F58]">
            <h4 class="text-sm font-semibold text-gray-700 dark:text-[#E8EAF6]">By Section</h4>
        </div>
        <div class="p-4">
            @foreach($bySection as $section => $count)
            <div class="flex justify-between text-sm py-1.5 border-b border-gray-100 dark:border-[#2A2F58] last:border-0">
                <span class="text-gray-600 dark:text-[#C1C4DC]">{{ $section }}</span>
                <span class="font-medium text-gray-900 dark:text-[#E8EAF6]">{{ $count }}</span>
            </div>
            @endforeach
        </div>
    </div>

    <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] overflow-hidden">
        <div class="px-4 py-3 bg-gray-50 dark:bg-[#161A33] border-b border-gray-200 dark:border-[#2A2F58]">
            <h4 class="text-sm font-semibold text-gray-700 dark:text-[#E8EAF6]">By School Year / Gender / Strand</h4>
        </div>
        <div class="p-4 space-y-4">
            <div>
                <p class="text-xs font-semibold text-gray-500 dark:text-[#8A90B0] uppercase mb-1">School Year</p>
                @foreach($byYear as $year => $count)
                <div class="flex justify-between text-sm py-1 border-b border-gray-100 dark:border-[#2A2F58] last:border-0">
                    <span class="text-gray-600 dark:text-[#C1C4DC]">{{ $year }}</span>
                    <span class="font-medium text-gray-900 dark:text-[#E8EAF6]">{{ $count }}</span>
                </div>
                @endforeach
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-500 dark:text-[#8A90B0] uppercase mb-1">Gender</p>
                @foreach($byGender->except('Unknown') as $gender => $count)
                <div class="flex justify-between text-sm py-1 border-b border-gray-100 dark:border-[#2A2F58] last:border-0">
                    <span class="text-gray-600 dark:text-[#C1C4DC]">{{ $gender }}</span>
                    <span class="font-medium text-gray-900 dark:text-[#E8EAF6]">{{ $count }}</span>
                </div>
                @endforeach
                @if(($byGender->get('Unknown', 0)) > 0)
                <div class="flex justify-between text-sm py-1 border-b border-gray-100 dark:border-[#2A2F58] last:border-0">
                    <span class="text-gray-600 dark:text-[#C1C4DC]">Unknown</span>
                    <span class="font-medium text-gray-900 dark:text-[#E8EAF6]">{{ $byGender->get('Unknown') }}</span>
                </div>
                <a href="{{ route('registrar.admissions.index') }}" class="text-xs text-blue-600 hover:text-blue-800 font-medium">Fix missing gender in the admission queue &rarr;</a>
                @endif
            </div>
            @if($byStrand->isNotEmpty())
            <div>
                <p class="text-xs font-semibold text-gray-500 dark:text-[#8A90B0] uppercase mb-1">Elective / Strand</p>
                @foreach($byStrand as $strand => $count)
                <div class="flex justify-between text-sm py-1 border-b border-gray-100 dark:border-[#2A2F58] last:border-0">
                    <span class="text-gray-600 dark:text-[#C1C4DC]">{{ $strand }}</span>
                    <span class="font-medium text-gray-900 dark:text-[#E8EAF6]">{{ $count }}</span>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>

    <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] overflow-hidden">
        <div class="px-4 py-3 bg-gray-50 dark:bg-[#161A33] border-b border-gray-200 dark:border-[#2A2F58]">
            <h4 class="text-sm font-semibold text-gray-700 dark:text-[#E8EAF6]">Enrollment Distribution (by grade)</h4>
        </div>
        <div class="p-4 space-y-2">
            @php $max = max(1, $byGrade->max()); @endphp
            @foreach($byGrade as $grade => $count)
            <div>
                <div class="flex justify-between text-xs text-gray-600 dark:text-[#C1C4DC] mb-1">
                    <span>{{ $grade }}</span>
                    <span class="font-medium text-gray-900 dark:text-[#E8EAF6]">{{ $count }}</span>
                </div>
                <div class="w-full h-2 bg-gray-100 dark:bg-[#23274C] rounded-full overflow-hidden">
                    <div class="h-2 bg-blue-500 rounded-full" style="width: {{ round($count / $max * 100) }}%"></div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif
