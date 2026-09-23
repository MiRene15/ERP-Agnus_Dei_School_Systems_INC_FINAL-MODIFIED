@extends('portal.layouts.app')
@section('breadcrumbs')
    <a href="{{ route('directress.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a><span class="opacity-40"> / </span><span class="current">Cashier Reports</span>
@endsection
@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">Cashier Reports</h2>
    <p class="text-gray-600 mt-1">Financial overview and collection summaries.</p>
</div>
<div x-data="ajaxTable('{{ route('directress.cashier-reports') }}', { date_from: '{{ $dateFrom }}', date_to: '{{ $dateTo }}' })">
    <div class="mb-4 flex gap-2 flex-wrap items-center">
        <form method="GET" class="flex gap-2 flex-1 flex-wrap" @submit.prevent="reload()">
            <label class="text-sm text-gray-600 self-center">From:</label>
            <input type="date" name="date_from" x-model="filters.date_from" @change="reload()" value="{{ $dateFrom }}" class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
            <label class="text-sm text-gray-600 self-center">To:</label>
            <input type="date" name="date_to" x-model="filters.date_to" @change="reload()" value="{{ $dateTo }}" class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
            <button type="submit" class="px-4 py-2 rounded-lg text-sm font-semibold text-white" style="background: var(--navy);">Filter</button>
        </form>
    </div>
    <div x-show="loading" class="p-4 space-y-3">
        <template x-for="i in 3" :key="i"><div class="skelly sk-card"><div class="skelly sk-line-md"></div></div></template>
    </div>
    <div x-show="!loading" x-cloak x-ref="results" x-html="html" class="fade-in"></div>
</div>
@endsection
