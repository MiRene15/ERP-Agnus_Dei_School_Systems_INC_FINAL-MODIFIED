@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('registrar.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <span class="current">Reports</span>
@endsection

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900 dark:text-[#E8EAF6]">Student Statistics</h2>
    <p class="text-gray-600 dark:text-[#C1C4DC] mt-1">Live enrollment counts by grade, section and strand.</p>
</div>

<div x-data="ajaxTable('{{ route('registrar.reports') }}')">
    <div class="flex justify-end mb-4">
        <a href="{{ route('registrar.reports.export') }}" class="px-4 py-2 rounded-lg text-sm font-semibold bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300 hover:bg-green-100 dark:hover:bg-green-900/30 transition">Export CSV</a>
    </div>
    <div x-show="loading" class="p-4 space-y-3">
        <template x-for="i in 3" :key="i"><div class="skelly sk-card"><div class="skelly sk-line-md"></div></div></template>
    </div>
    <div x-show="error" x-cloak class="mb-4 p-3 rounded-lg bg-red-50 border border-red-200 text-sm text-red-700 flex items-center justify-between gap-3"><span x-text="error"></span><button type="button" @click="reload()" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-white border border-red-200 hover:bg-red-100">Refresh</button></div>
    <div x-show="!loading" x-cloak x-ref="results" x-html="html" class="fade-in"></div>
</div>
@endsection
