@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ auth()->user()->role_id === 9 ? route('principal.dashboard') : route('registrar.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <span class="current">Grade Edit</span>
@endsection

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900 dark:text-[#E8EAF6]">Grade Edit</h2>
    <p class="text-gray-600 dark:text-[#C1C4DC] mt-1">Teachers ask to reopen submitted grades for correction. Approving returns those grades to Pending — the teacher corrects and re-submits.</p>
</div>

@if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm">{{ session('error') }}</div>
@endif

<div x-data="ajaxTable('{{ route('registrar.grade-unlocks.index') }}', { search: '{{ request('search') }}', school_year: '{{ request('school_year', active_school_year()) }}' })">
    <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-gray-900 dark:text-[#E8EAF6]">Search Requests</h3>
        </div>
    <div class="flex gap-2 flex-wrap items-center">
        <form method="GET" class="flex gap-2 flex-1 flex-wrap" @submit.prevent="reload()">
            <select name="school_year" x-model="filters.school_year" @change="reload()" class="rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                @foreach($schoolYears as $sy)
                    <option value="{{ $sy }}" {{ $sy === (request('school_year', active_school_year())) ? 'selected' : '' }}>{{ $sy }}</option>
                @endforeach
            </select>
            <input type="text" x-model="filters.search" @input="scheduleReload()"
                   placeholder="Search by class, teacher, or reason..."
                   class="flex-1 min-w-[200px] rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
            <button type="submit" class="px-4 py-2 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);">Search</button>
            <button type="button" @click="reset()" class="px-4 py-2 rounded-lg text-sm font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 transition">Clear</button>
        </form>
    </div>
    </div>

    <form method="POST" action="{{ route('registrar.grade-unlocks.batch-approve') }}" onsubmit="return confirmBatchUnlocks(this);">
        @csrf
        <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-900 dark:text-[#E8EAF6]">Pending</h3>
                <div class="flex gap-2">
                    <button type="submit" class="px-4 py-2 rounded-lg text-sm font-semibold text-white bg-green-600 hover:bg-green-700 transition">Approve Selected</button>
                    <button type="submit" formaction="{{ route('registrar.grade-unlocks.batch-reject') }}" class="px-4 py-2 rounded-lg text-sm font-semibold text-red-600 bg-red-50 hover:bg-red-100 transition">Reject Selected</button>
                </div>
            </div>
            <div x-show="loading && !html" class="space-y-3">
                <template x-for="i in 3" :key="i">
                    <div class="skelly sk-card">
                        <div class="grid grid-cols-4 gap-4 px-2">
                            <div class="skelly sk-line-md col-span-2"></div>
                            <div class="skelly sk-line-md"></div>
                            <div class="skelly sk-line-sm"></div>
                        </div>
                    </div>
                </template>
            </div>
            <div x-show="(loading || isRateLimited) && html" x-cloak class="mb-2 px-3 py-2 rounded-lg bg-amber-50 dark:bg-[rgba(245,158,11,0.12)] border border-amber-200 dark:border-[rgba(245,158,11,0.3)] text-xs text-amber-800 dark:text-[#FCD34D]">Showing results for &quot;<span class="font-semibold" x-text="displayedSearch"></span>&quot; &mdash; searching for &quot;<span class="font-semibold" x-text="filters.search"></span>&quot;&hellip;</div>
        <div x-show="html || !loading" x-cloak x-html="html" :class="((loading || isRateLimited) && html) ? 'opacity-50 transition-opacity' : 'opacity-100 transition-opacity'"></div>
        </div>
    </form>
</div>

<script>
function toggleAllUnlocks(source) {
    document.querySelectorAll('.unlock-select').forEach(function (box) { box.checked = source.checked; });
}
function confirmBatchUnlocks(form) {
    var count = form.querySelectorAll('.unlock-select:checked').length;
    if (count === 0) {
        alert('Select at least one request first.');
        return false;
    }
    var action = (document.activeElement && document.activeElement.getAttribute('formaction')) || form.getAttribute('action');
    if (action && action.indexOf('reject') !== -1) {
        return confirm('Reject ' + count + ' selected request(s)? Grades stay submitted.');
    }
    return confirm('Approve ' + count + ' selected request(s)? Their grades return to Pending for correction.');
}
</script>

<div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-6">
    <h3 class="font-semibold text-gray-900 dark:text-[#E8EAF6] mb-4">Recent Decisions</h3>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200 dark:border-[#2A2F58]">
                    <th class="text-left py-2 px-2 font-medium text-gray-600 dark:text-[#8A90B0]">Class / Term</th>
                    <th class="text-left py-2 px-2 font-medium text-gray-600 dark:text-[#8A90B0]">Teacher</th>
                    <th class="text-left py-2 px-2 font-medium text-gray-600 dark:text-[#8A90B0]">Status</th>
                    <th class="text-left py-2 px-2 font-medium text-gray-600 dark:text-[#8A90B0]">Reviewed</th>
                </tr>
            </thead>
            <tbody>
                @forelse($history as $req)
                <tr class="border-b border-gray-50 dark:border-[#2A2F58]">
                    <td class="py-2 px-2 text-gray-900 dark:text-[#E8EAF6]">{{ $req->schoolClass->subject->name ?? '—' }} <span class="text-xs text-gray-500">· {{ $req->grading_period }}</span></td>
                    <td class="py-2 px-2 text-xs text-gray-500">{{ $req->requester?->name ?? '—' }}</td>
                    <td class="py-2 px-2">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $req->status === 'approved' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">{{ ucfirst($req->status) }}</span>
                    </td>
                    <td class="py-2 px-2 text-xs text-gray-500">{{ $req->reviewer?->name ?? '—' }} · {{ $req->reviewed_at?->format('M d, Y') ?? '' }}</td>
                </tr>
                @empty
                <tr><td colspan="4" class="py-6 text-center text-gray-500 text-sm">No decisions yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
