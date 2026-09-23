@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('principal.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <span class="current">Schedules</span>
@endsection

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-[#E8EAF6]">Class Schedules</h2>
        <p class="text-gray-600 dark:text-[#C1C4DC] mt-1">View and manage class schedules per grade level.</p>
    </div>
    <a href="{{ route('principal.schedules.manage') }}" class="px-4 py-2 rounded-lg text-sm font-semibold text-white" style="background: var(--navy);">Manage Schedules</a>
</div>

@if(session('success'))
    <div class="mb-4 p-4 bg-green-50 dark:bg-[rgba(74,222,128,0.12)] border border-green-200 dark:border-[rgba(74,222,128,0.25)] rounded-lg text-green-800 dark:text-[#4ADE80] text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mb-4 p-4 bg-red-50 dark:bg-[rgba(248,113,113,0.12)] border border-red-200 dark:border-[rgba(248,113,113,0.25)] rounded-lg text-red-800 dark:text-[#F87171] text-sm">{{ session('error') }}</div>
@endif

<div class="mb-4 flex gap-2 flex-wrap">
    @foreach($gradeLevels as $gl)
    <a href="{{ route('principal.schedules', array_filter(['grade_level' => $gl, 'school_year' => request('school_year')])) }}"
       class="px-3 py-1.5 rounded-lg text-sm font-medium transition {{ $selectedGrade === $gl ? 'text-white' : 'text-gray-600 dark:text-[#C1C4DC] bg-gray-100 dark:bg-[#23274C] hover:bg-gray-200 dark:hover:bg-[#2A2F58]' }}"
       style="{{ $selectedGrade === $gl ? 'background: var(--navy);' : '' }}">
        {{ $gl }}
    </a>
    @endforeach
</div>

<p class="text-sm text-gray-500 dark:text-[#8A90B0] mb-4">Schedules are managed via <a href="{{ route('principal.schedules.manage') }}" class="text-blue-600 dark:text-[#60A5FA] underline">Manage Schedules</a> — add, import, or edit there. This page shows the weekly timetable per section.</p>

<div x-data="ajaxTable('{{ route('principal.schedules') }}', { search: '{{ request('search') }}', school_year: '{{ request('school_year') }}', day: '{{ request('day') }}' })">
    <div class="mb-4 flex gap-2 flex-wrap items-center">
        <form method="GET" class="flex gap-2 flex-1 flex-wrap" @submit.prevent="reload()">
            <input type="text" x-model="filters.search" @input.debounce.300ms="reload()"
                   placeholder="Search by subject or teacher..."
                   class="flex-1 min-w-[200px] rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
            <select x-model="filters.school_year" @change="reload()"
                     class="rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                @foreach($schoolYears as $sy)
                    <option value="{{ $sy }}">{{ $sy }}</option>
                @endforeach
            </select>
            <select x-model="filters.day" @change="reload()"
                     class="rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                <option value="">All Days</option>
                @foreach($days as $d)
                    <option value="{{ $d }}">{{ $d }}</option>
                @endforeach
            </select>
            <button type="submit" class="px-4 py-2 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);">Filter</button>
            <button type="button" @click="reset()" class="px-4 py-2 rounded-lg text-sm font-semibold bg-gray-100 dark:bg-[#23274C] text-gray-700 dark:text-[#C1C4DC] hover:bg-gray-200 dark:hover:bg-[#2A2F58] transition">Clear</button>
        </form>
    </div>

    <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] overflow-hidden">
        <div x-show="loading" class="p-4 space-y-3">
            <template x-for="i in 5" :key="i">
                <div class="skelly sk-card">
                    <div class="grid grid-cols-6 gap-4 px-2">
                        <div class="skelly sk-line-md col-span-2"></div>
                        <div class="skelly sk-line-md"></div>
                        <div class="skelly sk-line-sm"></div>
                        <div class="skelly sk-line-sm"></div>
                        <div class="skelly sk-line-sm"></div>
                    </div>
                </div>
            </template>
        </div>

        <div x-show="!loading" x-cloak @click="handlePaginationClick($event)" x-ref="results" x-html="html" class="fade-in"></div>
    </div>
</div>
@endsection
