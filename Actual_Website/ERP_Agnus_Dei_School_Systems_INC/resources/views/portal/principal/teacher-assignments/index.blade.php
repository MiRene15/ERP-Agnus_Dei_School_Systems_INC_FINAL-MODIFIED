@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('principal.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <span class="current">Teacher Assignments</span>
@endsection

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">Teacher Assignments</h2>
    <p class="text-gray-600 mt-1">You own who teaches what. Assigning a teacher here also powers schedule conflict detection for their classes.</p>
</div>

@if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm">{{ session('error') }}</div>
@endif

<div x-data="ajaxTable('{{ route('principal.teacher-assignments.index') }}', { search: '{{ request('search') }}', grade_level: '{{ $selectedGrade === 'All' ? '' : $selectedGrade }}' }, { cache: true })">
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6">
    <form method="GET" class="flex items-center gap-2 flex-wrap flex-1" @submit.prevent="reload()">
        <input type="text" x-model="filters.search" @input="scheduleReload()"
               placeholder="Search subject, teacher, or section..."
               class="flex-1 min-w-[200px] rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
        <label class="text-sm font-medium text-gray-700">Grade Level:</label>
        <select x-model="filters.grade_level" @change="reload()" class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
            <option value="">All Grades</option>
            @foreach($gradeLevels as $gl)
            <option value="{{ $gl }}">{{ $gl }}</option>
            @endforeach
        </select>
        <button type="submit" class="px-4 py-2 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);">Search</button>
        <button type="button" @click="reset()" class="px-4 py-2 rounded-lg text-sm font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 transition">Clear</button>
    </form>
    <p class="text-xs text-gray-500 mt-2">{{ $totalCount }} class(es){{ $unassignedCount > 0 ? ' — ' . $unassignedCount . ' unassigned' : '' }}</p>
</div>

<div x-show="loading && !html" class="space-y-3">
    <template x-for="i in 3" :key="i"><div class="skelly sk-card"><div class="px-2 py-4"><div class="skelly sk-line-md"></div></div></div></template>
</div>
<div x-show="error" x-cloak class="mb-4 p-3 rounded-lg bg-red-50 border border-red-200 text-sm text-red-700 flex items-center justify-between gap-3"><span x-text="error"></span><button type="button" @click="reload()" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-white border border-red-200 hover:bg-red-100">Refresh</button></div>
<div x-show="(loading || isRateLimited) && html" x-cloak class="mb-4 px-3 py-2 rounded-lg bg-amber-50 border border-amber-200 text-xs text-amber-800">Showing last list &mdash; updating&hellip;</div>
<div x-show="cacheLabel" x-cloak class="mb-4 px-3 py-2 rounded-lg bg-blue-50 border border-blue-200 text-xs text-blue-800 flex items-center justify-between gap-3"><span x-text="cacheLabel"></span><button type="button" @click="reload()" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-white border border-blue-200 hover:bg-blue-100">Refresh</button></div>
<div x-show="actionsLocked && html" x-cloak class="mb-4 px-3 py-2 rounded-lg bg-amber-50 border border-amber-200 text-xs text-amber-800">Updating — actions unlock when fresh.</div>
<div x-show="html || !loading" x-cloak x-ref="results" x-html="html" class="fade-in"></div>
</div>
@endsection
