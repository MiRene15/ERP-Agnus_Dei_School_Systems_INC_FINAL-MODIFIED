@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('cashier.dashboard') }}" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <span class="current">Collections Report</span>
@endsection

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">Total Collections Report</h2>
    <p class="text-gray-600 mt-1">View collections by date range.</p>
</div>

<div x-data="ajaxTable('{{ route('cashier.collections-report') }}', { date_from: '{{ request('date_from', now()->startOfMonth()->format('Y-m-d')) }}', date_to: '{{ request('date_to', now()->format('Y-m-d')) }}' })">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6">
        <form class="flex flex-wrap gap-3 items-end" @submit.prevent="if(!(filters.date_from&&filters.date_to&&filters.date_to<filters.date_from))reload()">
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">From</label>
                <input type="date" x-model="filters.date_from" :max="filters.date_to || '{{ date('Y-m-d') }}'" max="{{ date('Y-m-d') }}" min="1987-01-01" @change="if(!(filters.date_from&&filters.date_to&&filters.date_to<filters.date_from))reload()" class="rounded-lg border-gray-300 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">To</label>
                <input type="date" x-model="filters.date_to" :min="filters.date_from || '1987-01-01'" max="{{ date('Y-m-d') }}" @change="if(!(filters.date_from&&filters.date_to&&filters.date_to<filters.date_from))reload()" class="rounded-lg border-gray-300 text-sm">
            </div>
            <button type="submit" class="px-4 py-2 rounded-lg text-sm font-semibold text-white" style="background: var(--navy);">Generate Report</button>
            <button type="button" @click="reset()" class="px-4 py-2 rounded-lg text-sm font-semibold bg-gray-100 text-gray-700 hover:bg-gray-200">Clear</button>
            <a :href="'{{ route('cashier.collections-report.export') }}?date_from=' + filters.date_from + '&date_to=' + filters.date_to"
               class="px-4 py-2 rounded-lg text-sm font-semibold bg-green-50 text-green-700 hover:bg-green-100 transition">Export CSV</a>
            <p x-show="filters.date_from && filters.date_to && filters.date_to < filters.date_from" class="w-full text-red-500 text-xs">End date can't be before start date.</p>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <!-- Skeleton loading -->
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

        <!-- Results injected via AJAX -->
        <div x-show="!loading" x-cloak @click="handlePaginationClick($event)" x-ref="results" x-html="html" class="fade-in"></div>
    </div>
</div>
@endsection
