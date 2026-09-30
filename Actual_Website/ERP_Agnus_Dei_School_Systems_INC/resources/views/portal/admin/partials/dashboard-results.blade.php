<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center text-blue-600 mb-4">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            <span class="ml-2 font-semibold">Total Users</span>
        </div>
        <div class="text-3xl font-bold text-gray-900">{{ number_format($totalUsers) }}</div>
        <div class="text-sm text-gray-500 mt-2 font-medium">Registered accounts</div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center text-purple-600 mb-4">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            <span class="ml-2 font-semibold">Active Departments</span>
        </div>
        <div class="text-3xl font-bold text-gray-900">{{ $activeRoles }}</div>
        <div class="text-sm text-gray-500 mt-2 font-medium">System roles active</div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center text-orange-600 mb-4">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
            <span class="ml-2 font-semibold">Students</span>
        </div>
        <div class="text-3xl font-bold text-gray-900">{{ number_format($totalStudents) }}</div>
        <div class="mt-2">
            <span class="text-sm text-gray-500 font-medium">Active school year: {{ $activeSY }}</span>
        </div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <h3 class="text-lg font-bold text-gray-900 mb-4">Recent Activity Logs</h3>
    @if($recentActivity->isEmpty())
        <p class="text-sm text-gray-500 text-center py-4">No recent activity recorded.</p>
    @else
    <div class="space-y-4">
        @foreach($recentActivity as $log)
        <div class="flex items-center justify-between border-b border-gray-50 pb-3">
            <div>
                <p class="font-medium text-gray-800">{{ $log->causer?->name ?? 'System' }} &mdash; {{ $log->event }}</p>
                <p class="text-sm text-gray-500">{{ $log->description }}</p>
            </div>
            <span class="text-sm text-gray-400">{{ $log->created_at->diffForHumans() }}</span>
        </div>
        @endforeach
    </div>
    @endif
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
    <h3 class="font-semibold text-gray-900 mb-4">Exports</h3>
    <div class="flex flex-wrap gap-3">
        <a href="{{ route('admin.exports.enrollments') }}" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg">Enrollments CSV</a>
        <a href="{{ route('admin.exports.grades') }}" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg">Grades CSV</a>
        <a href="{{ route('admin.exports.collections') }}" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg">Collections CSV</a>
    </div>
</div>
