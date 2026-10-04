@extends('portal.layouts.app')
@section('breadcrumbs')
    <a href="{{ route('directress.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a><span class="opacity-40"> / </span><span class="current">Reports</span>
@endsection
@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900 dark:text-[#E8EAF6]">Reports</h2>
    <p class="text-gray-600 dark:text-[#C1C4DC] mt-1">Collections, receivables, clinic, library and student statistics.</p>
</div>
<div x-data="{ tab: '{{ in_array($activeTab, ['collections','receivables','clinic','library','students']) ? $activeTab : 'collections' }}' }">
    <div class="flex gap-2 mb-6 border-b border-gray-200 dark:border-[#2A2F58] overflow-x-auto">
        <button @click="tab='collections'" :class="tab==='collections' ? 'border-b-2 border-blue-600 text-blue-700 dark:text-[#60A5FA] font-semibold' : 'text-gray-500 dark:text-[#8A90B0]'" class="px-4 py-2 text-sm whitespace-nowrap">Collections</button>
        <button @click="tab='receivables'" :class="tab==='receivables' ? 'border-b-2 border-blue-600 text-blue-700 dark:text-[#60A5FA] font-semibold' : 'text-gray-500 dark:text-[#8A90B0]'" class="px-4 py-2 text-sm whitespace-nowrap">Receivables</button>
        <button @click="tab='clinic'" :class="tab==='clinic' ? 'border-b-2 border-blue-600 text-blue-700 dark:text-[#60A5FA] font-semibold' : 'text-gray-500 dark:text-[#8A90B0]'" class="px-4 py-2 text-sm whitespace-nowrap">Clinic</button>
        <button @click="tab='library'" :class="tab==='library' ? 'border-b-2 border-blue-600 text-blue-700 dark:text-[#60A5FA] font-semibold' : 'text-gray-500 dark:text-[#8A90B0]'" class="px-4 py-2 text-sm whitespace-nowrap">Library</button>
        <button @click="tab='students'" :class="tab==='students' ? 'border-b-2 border-blue-600 text-blue-700 dark:text-[#60A5FA] font-semibold' : 'text-gray-500 dark:text-[#8A90B0]'" class="px-4 py-2 text-sm whitespace-nowrap">Student Statistics</button>
    </div>

    {{-- Collections --}}
    <div x-show="tab==='collections'" x-cloak>
        <div x-data="ajaxTable('{{ route('directress.reports.collections') }}', { date_from: '{{ now()->startOfMonth()->format('Y-m-d') }}', date_to: '{{ now()->format('Y-m-d') }}' })">
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
                    <a :href="'{{ route('directress.cashier-reports.export') }}?date_from=' + filters.date_from + '&date_to=' + filters.date_to"
                       class="px-4 py-2 rounded-lg text-sm font-semibold bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300 hover:bg-green-100 dark:hover:bg-green-900/30 transition">Export CSV</a>
                    <p x-show="filters.date_from && filters.date_to && filters.date_to < filters.date_from" class="w-full text-red-500 text-xs">End date can't be before start date.</p>
                </form>
            </div>
            <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] overflow-hidden">
                <div x-show="loading" class="p-4 space-y-3">
                    <template x-for="i in 5" :key="i"><div class="skelly sk-card"><div class="grid grid-cols-7 gap-4 px-2"><div class="skelly sk-line-md col-span-2"></div><div class="skelly sk-line-md"></div><div class="skelly sk-line-md"></div><div class="skelly sk-line-md"></div><div class="skelly sk-line-md"></div><div class="skelly sk-line-md"></div><div class="skelly sk-line-sm"></div></div></div></template>
                </div>
                <div x-show="!loading" x-cloak x-ref="results" x-html="html" class="fade-in"></div>
            </div>
        </div>
    </div>

    {{-- Receivables --}}
    <div x-show="tab==='receivables'" x-cloak>
        <div x-data="ajaxTable('{{ route('directress.reports.receivables') }}')">
            <div class="flex justify-end mb-4">
                <a href="{{ route('directress.reports.receivables.export') }}" class="px-4 py-2 rounded-lg text-sm font-semibold bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300 hover:bg-green-100 dark:hover:bg-green-900/30 transition">Export CSV</a>
            </div>
            <div x-show="loading" class="p-4 space-y-3">
                <template x-for="i in 3" :key="i"><div class="skelly sk-card"><div class="skelly sk-line-md"></div></div></template>
            </div>
            <div x-show="!loading" x-cloak x-ref="results" x-html="html" class="fade-in"></div>
        </div>
    </div>

    {{-- Clinic --}}
    <div x-show="tab==='clinic'" x-cloak>
        <div x-data="ajaxTable('{{ route('directress.reports.clinic') }}', { date_from: '{{ now()->startOfMonth()->format('Y-m-d') }}', date_to: '{{ now()->format('Y-m-d') }}' })">
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
                    <a :href="'{{ route('directress.reports.clinic.export') }}?date_from=' + filters.date_from + '&date_to=' + filters.date_to"
                       class="px-4 py-2 rounded-lg text-sm font-semibold bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300 hover:bg-green-100 dark:hover:bg-green-900/30 transition">Export CSV</a>
                    <p x-show="filters.date_from && filters.date_to && filters.date_to < filters.date_from" class="w-full text-red-500 text-xs">End date can't be before start date.</p>
                </form>
            </div>
            <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] overflow-hidden">
                <div x-show="loading" class="p-4 space-y-3">
                    <template x-for="i in 4" :key="i"><div class="skelly sk-card"><div class="skelly sk-line-md"></div></div></template>
                </div>
                <div x-show="!loading" x-cloak x-ref="results" x-html="html" class="fade-in"></div>
            </div>
        </div>
    </div>

    {{-- Library --}}
    <div x-show="tab==='library'" x-cloak>
        <div x-data="ajaxTable('{{ route('directress.reports.library') }}')">
            <div class="flex justify-end mb-4">
                <a href="{{ route('directress.library-reports.export') }}" class="px-4 py-2 rounded-lg text-sm font-semibold bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300 hover:bg-green-100 dark:hover:bg-green-900/30 transition">Export CSV</a>
            </div>
            <div x-show="loading" class="p-4 space-y-3">
                <template x-for="i in 3" :key="i"><div class="skelly sk-card"><div class="skelly sk-line-md"></div></div></template>
            </div>
            <div x-show="!loading" x-cloak x-ref="results" x-html="html" class="fade-in"></div>
        </div>
    </div>

    {{-- Student Statistics --}}
    <div x-show="tab==='students'" x-cloak>
        <div x-data="ajaxTable('{{ route('directress.reports.students') }}')">
            <div class="flex justify-end mb-4">
                <a href="{{ route('directress.reports.students.export') }}" class="px-4 py-2 rounded-lg text-sm font-semibold bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300 hover:bg-green-100 dark:hover:bg-green-900/30 transition">Export CSV</a>
            </div>
            <div x-show="loading" class="p-4 space-y-3">
                <template x-for="i in 3" :key="i"><div class="skelly sk-card"><div class="skelly sk-line-md"></div></div></template>
            </div>
            <div x-show="!loading" x-cloak x-ref="results" x-html="html" class="fade-in"></div>
        </div>
    </div>
</div>
@endsection
