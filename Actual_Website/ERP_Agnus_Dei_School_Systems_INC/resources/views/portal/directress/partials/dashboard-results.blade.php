<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <p class="text-sm text-gray-500">Total Enrolled</p>
        <p class="text-2xl font-bold text-gray-900 mt-1">{{ $totalStudents ?? 0 }}</p>
        <p class="text-xs text-gray-400 mt-1">Active enrollments</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <p class="text-sm text-gray-500">Fee Schedules</p>
        <p class="text-2xl font-bold text-gray-900 mt-1">{{ $feeSchedules }}</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <p class="text-sm text-gray-500">Graduation Fees</p>
        <p class="text-2xl font-bold text-gray-900 mt-1">{{ $graduationFees }}</p>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
    <h3 class="font-semibold text-gray-900 mb-3">Demographics</h3>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
        <div>
            <p class="font-medium text-gray-700 mb-1">By Grade</p>
            @forelse(($byGrade ?? collect()) as $grade => $count)
                <div class="flex justify-between py-1 border-b border-gray-50"><span>{{ $grade }}</span><span class="font-semibold">{{ $count }}</span></div>
            @empty
                <p class="text-gray-400 text-xs">No data</p>
            @endforelse
        </div>
        <div>
            <p class="font-medium text-gray-700 mb-1">By Section</p>
            @forelse(($bySection ?? collect()) as $sec => $count)
                <div class="flex justify-between py-1 border-b border-gray-50"><span>{{ $sec }}</span><span class="font-semibold">{{ $count }}</span></div>
            @empty
                <p class="text-gray-400 text-xs">No data</p>
            @endforelse
        </div>
        <div>
            <p class="font-medium text-gray-700 mb-1">By School Year</p>
            @forelse(($byYear ?? collect()) as $year => $count)
                <div class="flex justify-between py-1 border-b border-gray-50"><span>{{ $year }}</span><span class="font-semibold">{{ $count }}</span></div>
            @empty
                <p class="text-gray-400 text-xs">No data</p>
            @endforelse
        </div>
    </div>
</div>
