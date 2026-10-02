<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
    <a href="{{ route('registrar.admissions.index') }}" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition block">
        <h3 class="text-lg font-bold text-gray-900 mb-2">Pending Admissions</h3>
        <p class="text-4xl font-bold text-blue-600">{{ $pendingCount }}</p>
        <p class="text-sm text-gray-500 mt-2">Awaiting document verification</p>
    </a>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-2">Currently Enrolled</h3>
        <p class="text-4xl font-bold text-green-600">{{ $enrolledCount }}</p>
        <p class="text-sm text-gray-500 mt-2">Active enrollments this school year</p>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <a href="{{ route('registrar.withdrawals.index') }}" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition block">
        <h3 class="text-sm font-semibold text-gray-500 mb-1">Pending Withdrawals</h3>
        <p class="text-3xl font-bold {{ ($pendingWithdrawals ?? 0) > 0 ? 'text-amber-600' : 'text-gray-900' }}">{{ $pendingWithdrawals ?? 0 }}</p>
        <p class="text-sm text-gray-500 mt-1">Awaiting review</p>
    </a>
    <a href="{{ route('registrar.grade-unlocks.index') }}" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition block">
        <h3 class="text-sm font-semibold text-gray-500 mb-1">Grade Unlock Reviews</h3>
        <p class="text-3xl font-bold {{ ($pendingUnlocks ?? 0) > 0 ? 'text-amber-600' : 'text-gray-900' }}">{{ $pendingUnlocks ?? 0 }}</p>
        <p class="text-sm text-gray-500 mt-1">Teacher correction requests</p>
    </a>
    <a href="{{ route('registrar.fee-assignment.index') }}" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition block">
        <h3 class="text-sm font-semibold text-gray-500 mb-1">Missing Ledgers</h3>
        <p class="text-3xl font-bold {{ ($missingLedgers ?? 0) > 0 ? 'text-red-600' : 'text-gray-900' }}">{{ $missingLedgers ?? 0 }}</p>
        <p class="text-sm text-gray-500 mt-1">Enrollments without fees</p>
    </a>
</div>

@if($recentAdmissions->isNotEmpty())
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <h3 class="font-semibold text-gray-900 mb-4">Recent Applications</h3>
    <div class="divide-y divide-gray-50">
        @foreach($recentAdmissions as $admission)
        <div class="py-3 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-xs font-bold text-blue-700">
                    {{ substr($admission->student->first_name, 0, 1) }}{{ substr($admission->student->last_name, 0, 1) }}
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-900">{{ $admission->student->first_name }} {{ $admission->student->last_name }}</p>
                    <p class="text-xs text-gray-500">{{ $admission->application_type }} &middot; {{ $admission->grade_level }}{{ $admission->strand ? ' — '.$admission->strand : '' }} &middot; {{ $admission->school_year }} &middot; {{ $admission->created_at->diffForHumans() }}</p>
                </div>
            </div>
            <a href="{{ route('registrar.admissions.show', $admission) }}" class="text-sm text-blue-600 hover:text-blue-800 font-medium">Review</a>
        </div>
        @endforeach
    </div>
    @if($pendingCount > 5)
    <div class="mt-3 text-center">
        <a href="{{ route('registrar.admissions.index') }}" class="text-sm text-blue-600 hover:text-blue-800 font-medium">View all {{ $pendingCount }} pending admissions &rarr;</a>
    </div>
    @endif
</div>
@endif
