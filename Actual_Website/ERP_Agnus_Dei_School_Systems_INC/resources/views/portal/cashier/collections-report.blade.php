@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('cashier.dashboard') }}" style="color: var(--muted);">Cashier Dashboard</a>
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
        <form class="flex flex-wrap gap-3 items-end" @submit.prevent="reload()">
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">From</label>
                <input type="date" x-model="filters.date_from" @change="reload()" class="rounded-lg border-gray-300 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">To</label>
                <input type="date" x-model="filters.date_to" @change="reload()" class="rounded-lg border-gray-300 text-sm">
            </div>
            <button type="submit" class="px-4 py-2 rounded-lg text-sm font-semibold text-white" style="background: var(--navy);">Generate Report</button>
            <button type="button" @click="reset()" class="px-4 py-2 rounded-lg text-sm font-semibold bg-gray-100 text-gray-700 hover:bg-gray-200">Clear</button>
            <a :href="'{{ route('cashier.collections-report.export') }}?date_from=' + filters.date_from + '&date_to=' + filters.date_to"
               class="px-4 py-2 rounded-lg text-sm font-semibold bg-green-50 text-green-700 hover:bg-green-100 transition">Export CSV</a>
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
