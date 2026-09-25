@extends('portal.layouts.app')

@section('breadcrumbs')
    <span class="current">Audit Logs</span>
@endsection

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900 dark:text-[#E8EAF6]">Audit Logs</h2>
    <p class="text-gray-600 dark:text-[#C1C4DC] mt-1">Track all user activity across the system.</p>
</div>

<div x-data="ajaxTable('{{ route('admin.audit-logs') }}', @js([
        'user_id' => (string) request('user_id'),
        'event' => (string) request('event'),
        'date_from' => (string) request('date_from'),
        'date_to' => (string) request('date_to'),
        'search' => (string) request('search'),
    ]))">
    <!-- Basic Filters -->
    <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-6 mb-6">
        <form @submit.prevent="reload()">
            <div class="flex gap-2 items-center flex-wrap">
                <input type="text" x-model="filters.search" @input.debounce.300ms="reload()" placeholder="Search description or event..."
                       class="flex-1 min-w-[200px] rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                <button type="button" @click="showAdvanced = !showAdvanced" class="inline-flex items-center gap-1 px-3 py-2 rounded-lg text-sm font-medium text-gray-600 dark:text-[#C1C4DC] bg-gray-100 dark:bg-[#23274C] hover:bg-gray-200 dark:hover:bg-[#2A2F58] transition">
                    <svg class="w-4 h-4 transition-transform duration-200" :class="showAdvanced ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    Filters
                </button>
                <button type="button" @click="reset()" class="px-3 py-2 rounded-lg text-sm font-medium text-gray-600 dark:text-[#C1C4DC] bg-gray-100 dark:bg-[#23274C] hover:bg-gray-200 dark:hover:bg-[#2A2F58] transition">Clear</button>
            </div>

            <!-- Advanced Filters (collapsible) -->
            <div x-show="showAdvanced" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2" class="mt-3 pt-3 border-t border-gray-100 dark:border-[#2A2F58]" style="display: none;">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 dark:text-[#8A90B0] uppercase mb-1">User</label>
                        <select x-model="filters.user_id" @change="reload()" class="w-full border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500 outline-none">
                            <option value="">All Users</option>
                            <option value="system">System (no user)</option>
                            @foreach($users as $u)
                                <option value="{{ $u->id }}">{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 dark:text-[#8A90B0] uppercase mb-1">Event</label>
                        <select x-model="filters.event" @change="reload()" class="w-full border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500 outline-none">
                            <option value="">All Events</option>
                            @foreach($events as $ev)
                                <option value="{{ $ev }}">{{ $ev }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 dark:text-[#8A90B0] uppercase mb-1">Date From</label>
                        <input type="date" x-model="filters.date_from" @change="reload()" class="w-full border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 dark:text-[#8A90B0] uppercase mb-1">Date To</label>
                        <input type="date" x-model="filters.date_to" @change="reload()" class="w-full border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500 outline-none">
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Results -->
    <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] overflow-hidden">
        <!-- Skeleton loading -->
        <div x-show="loading" class="p-4 space-y-3">
            <template x-for="i in 5" :key="i">
                <div class="skelly sk-card">
                    <div class="grid grid-cols-4 gap-4 px-2">
                        <div class="skelly sk-line-md"></div>
                        <div class="skelly sk-line-md"></div>
                        <div class="skelly sk-line-sm"></div>
                        <div class="skelly sk-line-md"></div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Results injected via AJAX -->
        <div x-show="!loading" x-cloak @click="handlePaginationClick($event)" x-ref="results" x-html="html" class="fade-in"></div>
    </div>
</div>
@endsection
