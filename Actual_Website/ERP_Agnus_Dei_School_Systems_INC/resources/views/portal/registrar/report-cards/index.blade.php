@extends('portal.layouts.app')
@section('breadcrumbs')
    <a href="{{ route('registrar.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <span class="current">Report Cards</span>
@endsection

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900 dark:text-[#E8EAF6]">Report Cards</h2>
    <p class="text-gray-600 dark:text-[#C1C4DC] mt-1">View and print student report cards for {{ active_school_year() }}.</p>
</div>
@if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
@endif
<div x-data="ajaxTable('{{ route('registrar.report-cards.index') }}', { search: '{{ request('search') }}', grade_level: '{{ request('grade_level') }}', section_id: '{{ request('section_id') }}', school_year: '{{ request('school_year', active_school_year()) }}' })">
    <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-gray-900 dark:text-[#E8EAF6]">Search Report Cards</h3>
        </div>
    <div class="flex gap-2 flex-wrap items-center">
        <form method="GET" class="flex gap-2 flex-1 flex-wrap" @submit.prevent="reload()">
            <select name="school_year" x-model="filters.school_year" @change="reload()" class="rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                @foreach($schoolYears as $sy)
                    <option value="{{ $sy }}" {{ $sy === (request('school_year', active_school_year())) ? 'selected' : '' }}>{{ $sy }}</option>
                @endforeach
            </select>
            <input type="text" x-model="filters.search" @input="scheduleReload()"
                   placeholder="Search by student name or section..."
                   class="flex-1 min-w-[200px] rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
            <select name="grade_level" x-model="filters.grade_level" @change="reload()" class="rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                <option value="All">All Grade Levels</option>
                @foreach(['Kinder', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'] as $gl)
                    <option value="{{ $gl }}" {{ request('grade_level') === $gl ? 'selected' : '' }}>{{ $gl }}</option>
                @endforeach
            </select>
            <select name="section_id" x-model="filters.section_id" @change="reload()" class="rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                <option value="All">All Sections</option>
                @foreach($sections as $section)
                    <option value="{{ $section->id }}" {{ (string) request('section_id') === (string) $section->id ? 'selected' : '' }}>{{ $section->grade_level }} — {{ $section->section_name }}</option>
                @endforeach
            </select>
            <button type="submit" class="px-4 py-2 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);">Search</button>
            <button type="button" @click="reset()" class="px-4 py-2 rounded-lg text-sm font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 transition">Clear</button>
        </form>
    </div>
    </div>

    <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] overflow-hidden">
        <div x-show="loading && !html" class="p-4 space-y-3">
            <template x-for="i in 5" :key="i">
                <div class="skelly sk-card">
                    <div class="grid grid-cols-4 gap-4 px-2">
                        <div class="skelly sk-line-md col-span-2"></div>
                        <div class="skelly sk-line-md"></div>
                        <div class="skelly sk-line-sm"></div>
                    </div>
                </div>
            </template>
        </div>

        <div x-show="error" x-cloak class="m-4 p-3 rounded-lg bg-red-50 border border-red-200 text-sm text-red-700 flex items-center justify-between gap-3"><span x-text="error"></span><button type="button" @click="reload()" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-white border border-red-200 hover:bg-red-100">Refresh</button></div>
        <div x-show="(loading || isRateLimited) && html" x-cloak class="m-4 px-3 py-2 rounded-lg bg-amber-50 dark:bg-[rgba(245,158,11,0.12)] border border-amber-200 dark:border-[rgba(245,158,11,0.3)] text-xs text-amber-800 dark:text-[#FCD34D]">Showing results for &quot;<span class="font-semibold" x-text="displayedSearch"></span>&quot; &mdash; searching for &quot;<span class="font-semibold" x-text="filters.search"></span>&quot;&hellip;</div>
        <div x-show="html || !loading" x-cloak @click="handlePaginationClick($event)" x-ref="results" x-html="html" class="fade-in" :class="((loading || isRateLimited) && html) ? 'opacity-50 transition-opacity' : 'opacity-100 transition-opacity'"></div>
    </div>
</div>
@endsection
