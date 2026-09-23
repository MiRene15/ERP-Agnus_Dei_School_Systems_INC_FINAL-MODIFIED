@extends('portal.layouts.app')
@section('breadcrumbs')
    <a href="{{ route('cashier.dashboard') }}" style="color: var(--muted);">Cashier Dashboard</a><span class="opacity-40">/</span><span class="current">Reports</span>
@endsection
@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900 dark:text-[#E8EAF6]">Reports</h2>
    <p class="text-gray-600 dark:text-[#C1C4DC] mt-1">Collections and receivables.</p>
</div>
<div x-data="{ tab: 'collections' }">
    <div class="flex gap-2 mb-6 border-b border-gray-200 dark:border-[#2A2F58]">
        <button @click="tab='collections'" :class="tab==='collections' ? 'border-b-2 border-blue-600 text-blue-700 dark:text-[#60A5FA] font-semibold' : 'text-gray-500 dark:text-[#8A90B0]'" class="px-4 py-2 text-sm">Collections Report</button>
        <button @click="tab='receivables'" :class="tab==='receivables' ? 'border-b-2 border-blue-600 text-blue-700 dark:text-[#60A5FA] font-semibold' : 'text-gray-500 dark:text-[#8A90B0]'" class="px-4 py-2 text-sm">Receivables Report</button>
    </div>
    <div x-show="tab==='collections'" x-cloak>
        <div x-data="ajaxTable('{{ route('cashier.collections-report') }}', { date_from: '{{ now()->startOfMonth()->format('Y-m-d') }}', date_to: '{{ now()->format('Y-m-d') }}' })">
            <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-4 mb-6">
                <form class="flex flex-wrap gap-3 items-end" @submit.prevent="reload()">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-[#8A90B0] mb-1">From</label>
                        <input type="date" x-model="filters.date_from" @change="reload()" class="rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-[#8A90B0] mb-1">To</label>
                        <input type="date" x-model="filters.date_to" @change="reload()" class="rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] text-sm">
                    </div>
                    <button type="submit" class="px-4 py-2 rounded-lg text-sm font-semibold text-white" style="background: var(--navy);">Generate Report</button>
                    <button type="button" @click="reset()" class="px-4 py-2 rounded-lg text-sm font-semibold bg-gray-100 dark:bg-[#23274C] text-gray-700 dark:text-[#C1C4DC] hover:bg-gray-200 dark:hover:bg-[#2A2F58]">Clear</button>
                </form>
            </div>
            <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] overflow-hidden">
                <div x-show="loading" class="p-4 space-y-3">
                    <template x-for="i in 5" :key="i">
                        <div class="skelly sk-card">
                            <div class="grid grid-cols-7 gap-4 px-2">
                                <div class="skelly sk-line-md col-span-2"></div>
                                <div class="skelly sk-line-md"></div>
                                <div class="skelly sk-line-md"></div>
                                <div class="skelly sk-line-md"></div>
                                <div class="skelly sk-line-md"></div>
                                <div class="skelly sk-line-md"></div>
                                <div class="skelly sk-line-sm"></div>
                            </div>
                        </div>
                    </template>
                </div>
                <div x-show="!loading" x-cloak @click="handlePaginationClick($event)" x-ref="results" x-html="html" class="fade-in"></div>
            </div>
        </div>
    </div>
    <div x-show="tab==='receivables'" x-cloak>
        <div x-data="ajaxTable('{{ route('cashier.reports.receivables') }}')">
            <div x-show="loading" class="p-4">
                <div class="space-y-3">
                    <div class="skelly sk-line-md"></div>
                    <div class="skelly sk-line-lg"></div>
                    <div class="skelly sk-line-md"></div>
                </div>
            </div>
            <div x-show="!loading" x-cloak x-ref="results" x-html="html" class="fade-in"></div>
        </div>
    </div>
</div>
@endsection
