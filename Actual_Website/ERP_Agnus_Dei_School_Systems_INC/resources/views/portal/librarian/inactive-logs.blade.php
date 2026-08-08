@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('librarian.dashboard') }}" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <span class="current">Inactive Book Logs</span>
@endsection

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">Inactive Book Logs</h2>
    <p class="text-gray-600 mt-1">Books that have been deactivated with reason and audit trail.</p>
</div>

@if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
@endif

<div x-data="ajaxTable('{{ route('librarian.inactive-logs') }}', { search: '{{ request('search') }}' })">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6">
        <form method="GET" class="flex flex-wrap gap-3 items-end" @submit.prevent="reload()">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-medium text-gray-500 mb-1">Search</label>
                <input type="text" x-model="filters.search" @input.debounce.300ms="reload()" placeholder="Title, author, or serial number..."
                       class="w-full rounded-lg border-gray-300 text-sm focus:ring-2 focus:ring-blue-500">
            </div>
            <button type="submit" class="px-4 py-2 rounded-lg text-sm font-semibold text-white" style="background: var(--navy);">Search</button>
            <button type="button" @click="reset()" class="px-4 py-2 rounded-lg text-sm font-semibold bg-gray-100 text-gray-700 hover:bg-gray-200">Clear</button>
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
