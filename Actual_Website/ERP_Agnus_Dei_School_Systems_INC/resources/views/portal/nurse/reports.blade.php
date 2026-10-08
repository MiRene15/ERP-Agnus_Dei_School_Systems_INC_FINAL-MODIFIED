@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('nurse.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <span class="current">Reports</span>
@endsection

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900 dark:text-[#E8EAF6]">Clinic Reports</h2>
    <p class="text-gray-600 dark:text-[#C1C4DC] mt-1">Visits, totals and trends for your clinic.</p>
</div>

<div x-data="ajaxTable('{{ route('nurse.reports') }}', { date_from: '{{ $dateFrom }}', date_to: '{{ $dateTo }}' })">
    <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-4 mb-6">
        <form class="flex flex-wrap gap-3 items-end" @submit.prevent="if(!(filters.date_from&&filters.date_to&&filters.date_to<filters.date_from))reload()">
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-[#8A90B0] mb-1">From</label>
                <input type="date" x-model="filters.date_from" :max="filters.date_to || '{{ date('Y-m-d') }}'" max="{{ date('Y-m-d') }}" min="1987-01-01" @change="if(!(filters.date_from&&filters.date_to&&filters.date_to<filters.date_from))reload()" class="rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-[#8A90B0] mb-1">To</label>
                <input type="date" x-model="filters.date_to" :min="filters.date_from || '1987-01-01'" max="{{ date('Y-m-d') }}" @change="if(!(filters.date_from&&filters.date_to&&filters.date_to<filters.date_from))reload()" class="rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] text-sm">
            </div>
            <button type="submit" class="px-4 py-2 rounded-lg text-sm font-semibold text-white" style="background: var(--navy);">Generate Report</button>
            <button type="button" @click="reset()" class="px-4 py-2 rounded-lg text-sm font-semibold bg-gray-100 dark:bg-[#23274C] text-gray-700 dark:text-[#C1C4DC] hover:bg-gray-200 dark:hover:bg-[#2A2F58]">Clear</button>
            <a :href="'{{ route('nurse.reports.export') }}?date_from=' + filters.date_from + '&date_to=' + filters.date_to"
               class="px-4 py-2 rounded-lg text-sm font-semibold bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300 hover:bg-green-100 dark:hover:bg-green-900/30 transition">Export CSV</a>
            <p x-show="filters.date_from && filters.date_to && filters.date_to < filters.date_from" class="w-full text-red-500 text-xs">End date can't be before start date.</p>
        </form>
    </div>
    <div x-show="loading" class="p-4 space-y-3">
        <template x-for="i in 4" :key="i"><div class="skelly sk-card"><div class="skelly sk-line-md"></div></div></template>
    </div>
    <div x-show="error" x-cloak class="mb-4 p-3 rounded-lg bg-red-50 border border-red-200 text-sm text-red-700 flex items-center justify-between gap-3"><span x-text="error"></span><button type="button" @click="reload()" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-white border border-red-200 hover:bg-red-100">Refresh</button></div>
    <div x-show="!loading" x-cloak x-ref="results" x-html="html" class="fade-in"></div>
</div>
@endsection
