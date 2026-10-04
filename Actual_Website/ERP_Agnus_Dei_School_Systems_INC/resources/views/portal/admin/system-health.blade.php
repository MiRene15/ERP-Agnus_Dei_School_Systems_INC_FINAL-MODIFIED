@extends('portal.layouts.app')

@section('breadcrumbs')
    <span class="current">System Health</span>
@endsection

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900 dark:text-[#E8EAF6]">System Health</h2>
    <p class="text-gray-600 dark:text-[#C1C4DC] mt-1">One calm place to see health at a glance. Counts only — what anyone typed is never shown.</p>
</div>

@if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm">{{ session('error') }}</div>
@endif

<div x-data="ajaxTable('{{ route('admin.system-health') }}')">
    <div class="flex justify-end mb-4">
        <button type="button" @click="reload()" class="px-4 py-2 rounded-lg text-sm font-semibold bg-gray-100 dark:bg-[#23274C] text-gray-700 dark:text-[#C1C4DC] hover:bg-gray-200 dark:hover:bg-[#2A2F58] transition">Refresh</button>
    </div>

    <div x-show="loading && !html" class="space-y-4">
        <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-6 space-y-3">
            <div class="skelly sk-line-md w-32"></div>
            <div class="skelly sk-card"></div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
            <template x-for="i in 4" :key="i">
                <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-5">
                    <div class="skelly sk-line-sm w-24 mb-2"></div>
                    <div class="skelly sk-line-md w-16"></div>
                </div>
            </template>
        </div>
    </div>

    <div x-show="error" x-cloak class="mb-4 p-3 rounded-lg bg-red-50 border border-red-200 text-sm text-red-700 flex items-center justify-between gap-3"><span x-text="error"></span><button type="button" @click="reload()" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-white border border-red-200 hover:bg-red-100">Refresh</button></div>

    <div x-show="html || !loading" x-cloak x-ref="results" x-html="html" class="fade-in"></div>

    <div x-show="!loading && !html && !error">
        @include('portal.admin.partials.system-health-overview-results', ['cards' => $cards, 'acknowledged' => $acknowledged ?? [], 'ackInfo' => $ackInfo ?? [], 'trends' => $trends ?? ['labels' => [], 'abuse' => [], 'slow' => [], 'logins' => [], 'uptime' => []]])
    </div>

    @if(!($hasData ?? true))
        <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-6 text-center mt-6">
            <p class="text-sm text-gray-600 dark:text-[#C1C4DC]">All calm — no alerts in the last 24 hours. Refresh to re-check.</p>
        </div>
    @endif
</div>
@endsection
