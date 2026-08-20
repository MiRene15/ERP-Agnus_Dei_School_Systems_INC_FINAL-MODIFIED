<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
            <svg class="w-5 h-5 text-blue-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9.5a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
            Clinic Overview
        </h3>
        <div class="grid grid-cols-2 gap-4">
            <div class="p-4 bg-blue-50 rounded-lg text-center">
                <p class="text-2xl font-bold text-blue-700">{{ $todayVisits }}</p>
                <p class="text-sm text-gray-600">Today's Visits</p>
            </div>
            <div class="p-4 bg-green-50 rounded-lg text-center">
                <p class="text-2xl font-bold text-green-700">{{ $thisWeekVisits }}</p>
                <p class="text-sm text-gray-600">This Week</p>
            </div>
            <div class="p-4 bg-yellow-50 rounded-lg text-center">
                <p class="text-2xl font-bold text-yellow-700">{{ $followUps }}</p>
                <p class="text-sm text-gray-600">Follow-ups (7d)</p>
            </div>
            <div class="p-4 bg-red-50 rounded-lg text-center">
                <p class="text-2xl font-bold text-red-700">{{ $referralsCount }}</p>
                <p class="text-sm text-gray-600">Referrals</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
            <svg class="w-5 h-5 text-purple-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            Recent Consultations
        </h3>
        @if($recentLogs->isEmpty())
        <div class="p-4 bg-gray-50 rounded-lg text-center">
            <p class="text-gray-500 text-sm">No consultations recorded today.</p>
        </div>
        @else
        <div class="space-y-2">
            @foreach($recentLogs as $log)
            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                <div>
                    <p class="font-medium text-sm text-gray-900">{{ $log->student->first_name ?? '' }} {{ $log->student->last_name ?? '' }}</p>
                    <p class="text-xs text-gray-500">{{ $log->complaint ?? $log->symptoms ?? 'N/A' }}</p>
                </div>
                <span class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($log->incident_date)->format('M d') }}</span>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>
