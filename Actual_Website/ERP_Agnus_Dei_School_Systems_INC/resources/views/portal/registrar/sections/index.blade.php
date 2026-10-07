@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('registrar.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a><span class="opacity-40"> / </span><span class="current">Sections</span>
@endsection

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-[#E8EAF6]">Sections</h2>
        <p class="text-gray-600 dark:text-[#C1C4DC] mt-1">Manage sections/classrooms per grade level.</p>
    </div>
    <a href="{{ route('registrar.sections.create') }}" class="px-4 py-2 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);">+ Add Section</a>
</div>

@if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm">{{ session('error') }}</div>
@endif

<div x-data="ajaxTable('{{ route('registrar.sections.index') }}', { search: '{{ request('search') }}', grade_level: '{{ request('grade_level') }}' })">
    <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-gray-900 dark:text-[#E8EAF6]">Search Sections</h3>
        </div>
    <div class="flex gap-2 flex-wrap items-center">
        <form method="GET" class="flex gap-2 flex-1 flex-wrap" @submit.prevent="reload()">
            <input type="text" x-model="filters.search" @input="scheduleReload()"
                   placeholder="Search by section name..."
                   class="flex-1 min-w-[200px] rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
            <select x-model="filters.grade_level" @change="reload()" class="rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                <option value="All">All Grade Levels</option>
                @foreach($gradeLevels as $gl)
                    @if($gl !== 'All')
                    <option value="{{ $gl }}">{{ $gl }}</option>
                    @endif
                @endforeach
            </select>
            <button type="submit" class="px-4 py-2 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);">Search</button>
            <button type="button" @click="reset()" class="px-4 py-2 rounded-lg text-sm font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 transition">Clear</button>
        </form>
    </div>
    </div>

    <!-- Skeleton loading -->
    <div x-show="loading && !html" class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-4 space-y-3">
        <template x-for="i in 5" :key="i">
            <div class="skelly sk-card">
                <div class="grid grid-cols-4 gap-4 px-2">
                    <div class="skelly sk-line-md col-span-2"></div>
                    <div class="skelly sk-line-md"></div>
                    <div class="skelly sk-line-sm"></div>
                    <div class="skelly sk-line-sm"></div>
                </div>
            </div>
        </template>
    </div>

    <!-- Results injected via AJAX -->
    <div x-show="error" x-cloak class="m-4 p-3 rounded-lg bg-red-50 border border-red-200 text-sm text-red-700 flex items-center justify-between gap-3"><span x-text="error"></span><button type="button" @click="reload()" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-white border border-red-200 hover:bg-red-100">Refresh</button></div>
    <div x-show="(loading || isRateLimited) && html" x-cloak class="px-3 py-2 rounded-lg bg-amber-50 dark:bg-[rgba(245,158,11,0.12)] border border-amber-200 dark:border-[rgba(245,158,11,0.3)] text-xs text-amber-800 dark:text-[#FCD34D]">Showing results for &quot;<span class="font-semibold" x-text="displayedSearch"></span>&quot; &mdash; searching for &quot;<span class="font-semibold" x-text="filters.search"></span>&quot;&hellip;</div>
    <div x-show="html || !loading" x-cloak @click="handlePaginationClick($event)" x-ref="results" x-html="html" class="fade-in" :class="((loading || isRateLimited) && html) ? 'opacity-50 transition-opacity' : 'opacity-100 transition-opacity'"></div>
</div>

<div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-6 mt-6">
    <h3 class="font-semibold text-gray-900 dark:text-[#E8EAF6] mb-1">Bulk Assign Students to Section</h3>
    <p class="text-xs text-gray-500 dark:text-[#8A90B0] mb-4">Active enrollments for {{ $assignYear }}. Already-assigned students will be reassigned — grade mismatch, inactive enrollments, and locked years are skipped.</p>
    @if($assignEnrollments->isEmpty())
        <p class="text-sm text-gray-500 py-4 text-center">No active enrollments for {{ $assignYear }}.</p>
    @else
    <form method="POST" action="{{ route('registrar.sections.bulk-assign') }}" onsubmit="return confirmBulkAssign(this);">
        @csrf
        <div class="flex gap-2 flex-wrap items-center mb-4">
            <select name="section_id" id="bulk-target-section" required class="rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                <option value="">Choose target section…</option>
                @foreach($assignSections as $target)
                    <option value="{{ $target->id }}">{{ $target->grade_level }} — {{ $target->section_name }}</option>
                @endforeach
            </select>
            <button type="submit" class="px-4 py-2 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);">Assign Selected</button>
        </div>
        <div class="overflow-x-auto max-h-96 overflow-y-auto">
            <table class="w-full text-sm">
                <thead class="sticky top-0 bg-white dark:bg-[#1A1E3B]">
                    <tr class="border-b border-gray-200 dark:border-[#2A2F58]">
                        <th class="py-2 px-2 w-8"><input type="checkbox" onclick="toggleAllAssign(this)" title="Select all" class="rounded border-gray-300"></th>
                        <th class="text-left py-2 px-2 font-medium text-gray-600 dark:text-[#8A90B0]">Student</th>
                        <th class="text-left py-2 px-2 font-medium text-gray-600 dark:text-[#8A90B0]">Current Section</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($assignEnrollments as $enrollment)
                    <tr class="border-b border-gray-50 dark:border-[#2A2F58]">
                        <td class="py-2 px-2 text-center"><input type="checkbox" name="enrollment_ids[]" value="{{ $enrollment->id }}" class="assign-select rounded border-gray-300"></td>
                        <td class="py-2 px-2 font-medium text-gray-900 dark:text-[#E8EAF6]">{{ $enrollment->student->first_name }} {{ $enrollment->student->last_name }}<span class="block text-xs text-gray-400 font-normal">{{ $enrollment->student->student_number ?? '' }}</span></td>
                        <td class="py-2 px-2 text-gray-600 dark:text-[#C1C4DC] text-xs">{{ $enrollment->section ? $enrollment->section->grade_level . ' — ' . $enrollment->section->section_name : 'Unassigned' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </form>
    @endif
</div>

<script>
function toggleAllAssign(source) {
    document.querySelectorAll('.assign-select').forEach(function (box) { box.checked = source.checked; });
}
function confirmBulkAssign(form) {
    var count = form.querySelectorAll('.assign-select:checked').length;
    if (count === 0) {
        alert('Select at least one student first.');
        return false;
    }
    var target = form.querySelector('#bulk-target-section');
    if (!target || !target.value) {
        alert('Choose a target section first.');
        return false;
    }
    var label = target.options[target.selectedIndex].text;
    return confirm('Assign ' + count + ' student(s) to ' + label + '? Already-assigned students will be reassigned.');
}
</script>
@endsection
