@extends('portal.layouts.app')
@section('breadcrumbs')
    <a href="{{ route('cashier.dashboard') }}" style="color: var(--muted);">Dashboard</a><span class="opacity-40">/</span><span class="current">Reports</span>
@endsection
@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900 dark:text-[#E8EAF6]">Reports</h2>
    <p class="text-gray-600 dark:text-[#C1C4DC] mt-1">Collections and receivables.</p>
</div>
<div x-data="{ tab: (new URLSearchParams(window.location.search).get('view') === 'receivables') ? 'receivables' : 'collections', setTab(v) { this.tab = v; const u = new URL(window.location.href); if (v === 'collections') { u.searchParams.delete('view'); } else { u.searchParams.set('view', v); } window.history.replaceState({}, '', u); } }">
    <div class="inline-flex gap-1 mb-6 p-1 rounded-xl bg-gray-100 dark:bg-[#23274C]" role="tablist" aria-label="Report type">
        <button @click="setTab('collections')" :class="tab==='collections' ? 'text-white shadow font-semibold' : 'text-gray-500 dark:text-[#8A90B0]'" :style="tab==='collections' ? 'background: var(--navy);' : ''" class="px-4 py-2 text-sm rounded-lg transition" role="tab" :aria-selected="tab==='collections'">Collections Report</button>
        <button @click="setTab('receivables')" :class="tab==='receivables' ? 'text-white shadow font-semibold' : 'text-gray-500 dark:text-[#8A90B0]'" :style="tab==='receivables' ? 'background: var(--navy);' : ''" class="px-4 py-2 text-sm rounded-lg transition" role="tab" :aria-selected="tab==='receivables'">Receivables Report</button>
    </div>
    <div x-show="tab==='collections'" x-cloak>
        <div x-data="ajaxTable('{{ route('cashier.collections-report') }}', { date_from: '{{ now()->startOfMonth()->format('Y-m-d') }}', date_to: '{{ now()->format('Y-m-d') }}' })">
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
                    <a :href="'{{ route('cashier.collections-report.export') }}?date_from=' + filters.date_from + '&date_to=' + filters.date_to"
                       class="px-4 py-2 rounded-lg text-sm font-semibold bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300 hover:bg-green-100 dark:hover:bg-green-900/30 transition">Export CSV</a>
                    <p x-show="filters.date_from && filters.date_to && filters.date_to < filters.date_from" class="w-full text-red-500 text-xs">End date can't be before start date.</p>
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
        <div x-data="ajaxTable('{{ route('cashier.reports.receivables') }}', { date_from: '{{ now()->startOfMonth()->format('Y-m-d') }}', date_to: '{{ now()->format('Y-m-d') }}' })">
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
                    <a :href="'{{ route('cashier.reports.receivables.export') }}?date_from=' + filters.date_from + '&date_to=' + filters.date_to"
                       class="px-4 py-2 rounded-lg text-sm font-semibold bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300 hover:bg-green-100 dark:hover:bg-green-900/30 transition">Export CSV</a>
                    <p x-show="filters.date_from && filters.date_to && filters.date_to < filters.date_from" class="w-full text-red-500 text-xs">End date can't be before start date.</p>
                </form>
            </div>
            <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] overflow-hidden">
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
</div>
@endsection
