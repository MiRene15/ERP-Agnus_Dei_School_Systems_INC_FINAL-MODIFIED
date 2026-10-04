@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('admin.system-health') }}" class="hover:underline">System Health</a>
    <span class="mx-1">/</span>
    <span class="current">{{ $detail['title'] ?? '' }}</span>
@endsection

@section('content')
<div class="mb-6 flex items-start justify-between gap-4">
    <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-[#E8EAF6]">{{ $detail['title'] ?? '' }}</h2>
        <p class="text-gray-600 dark:text-[#C1C4DC] mt-1">{{ $detail['label'] ?? '' }} Counts only — what anyone typed is never shown.</p>
    </div>
    <a href="{{ route('admin.system-health') }}" class="px-4 py-2 rounded-lg text-sm font-semibold bg-gray-100 dark:bg-[#23274C] text-gray-700 dark:text-[#C1C4DC] hover:bg-gray-200 dark:hover:bg-[#2A2F58] transition">Back</a>
</div>

@if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }} Acknowledged ✓ — the menu badge updates on next load.</div>
@endif
@if(session('error'))
    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm">{{ session('error') }}</div>
@endif

<div x-data="ajaxTable('{{ route('admin.system-health.show', $detail['type']) }}')">
    <div class="flex justify-end mb-4">
        <button type="button" @click="reload()" class="px-4 py-2 rounded-lg text-sm font-semibold bg-gray-100 dark:bg-[#23274C] text-gray-700 dark:text-[#C1C4DC] hover:bg-gray-200 dark:hover:bg-[#2A2F58] transition">Refresh</button>
    </div>

    <div x-show="loading && !html" class="space-y-4">
        <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-6 space-y-3">
            <div class="skelly sk-line-md w-32"></div>
            <div class="skelly sk-card"></div>
        </div>
        <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-6 space-y-3">
            <div class="skelly sk-line-md w-32"></div>
            <div class="skelly sk-card"></div>
            <div class="skelly sk-card"></div>
        </div>
    </div>

    <div x-show="error" x-cloak class="mb-4 p-3 rounded-lg bg-red-50 border border-red-200 text-sm text-red-700 flex items-center justify-between gap-3"><span>Health data unavailable — Refresh.</span><button type="button" @click="reload()" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-white border border-red-200 hover:bg-red-100">Refresh</button></div>

    <div x-show="html || !loading" x-cloak @click="handlePaginationClick($event)" x-ref="results" x-html="html" class="fade-in"></div>

    <div x-show="!loading && !html && !error">
        @include('portal.admin.partials.system-health-detail-page-results', ['detail' => $detail, 'rows' => $rows ?? ($detail['rows'] ?? []), 'trends' => $trends ?? ['labels' => [], 'abuse' => [], 'slow' => [], 'logins' => [], 'uptime' => []]])
    </div>
</div>
@endsection
