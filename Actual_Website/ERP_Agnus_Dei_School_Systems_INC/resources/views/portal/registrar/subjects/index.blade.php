@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ request()->routeIs('principal.*') ? route('principal.dashboard') : route('registrar.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <span class="current">Subjects</span>
@endsection

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Subjects</h2>
        <p class="text-gray-600 mt-1">View and manage subjects by grade level.</p>
    </div>
    @if(empty($readOnly))
    <a href="{{ route('registrar.subjects.create') }}" class="px-4 py-2 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">+ Add Subject</a>
    @else
    <span class="px-3 py-1.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-500">Read-only oversight</span>
    @endif
</div>

@if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm">{{ session('error') }}</div>
@endif

@if(empty($readOnly) && ($pendingRequests ?? collect())->isNotEmpty())
<div class="mb-6 bg-amber-50 border border-amber-200 rounded-xl p-4">
    <h3 class="text-sm font-semibold text-amber-800 mb-2">Waiting for Principal approval ({{ $pendingRequests->count() }}) — live subjects unchanged until approved</h3>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-amber-200">
                    <th class="text-left py-1 px-2 font-medium text-amber-700">Action</th>
                    <th class="text-left py-1 px-2 font-medium text-amber-700">Subject</th>
                    <th class="text-left py-1 px-2 font-medium text-amber-700">Requested</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pendingRequests as $req)
                <tr class="border-b border-amber-100 last:border-0">
                    <td class="py-1 px-2 text-amber-800 font-medium">{{ ucfirst($req->action) }}</td>
                    <td class="py-1 px-2 text-gray-700">{{ $req->subject?->subject_code ?? ($req->payload['subject_code'] ?? '?') }} — {{ $req->subject?->name ?? ($req->payload['name'] ?? '') }}</td>
                    <td class="py-1 px-2 text-xs text-gray-500">{{ $req->created_at->format('M d, Y') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<div x-data="ajaxTable('{{ request()->routeIs('principal.*') ? route('principal.subjects.index') : route('registrar.subjects.index') }}', { search: '{{ request('search') }}', grade_level: '{{ request('grade_level') }}' })">
    <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-gray-900 dark:text-[#E8EAF6]">Search Subjects</h3>
        </div>
    <div class="flex gap-2 flex-wrap items-center">
        <form method="GET" class="flex gap-2 flex-1 flex-wrap" @submit.prevent="reload()">
            <input type="text" x-model="filters.search" @input="scheduleReload()"
                   placeholder="Search by name or code..."
                   class="flex-1 min-w-[200px] rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
            <select x-model="filters.grade_level" @change="reload()" class="rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                <option value="All">All Grade Levels</option>
                @foreach($gradeLevels as $gl)
                    <option value="{{ $gl }}">{{ $gl }}</option>
                @endforeach
            </select>
            <button type="submit" class="px-4 py-2 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);">Search</button>
            <button type="button" @click="reset()" class="px-4 py-2 rounded-lg text-sm font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 transition">Clear</button>
        </form>
    </div>
    </div>

    <!-- Skeleton loading -->
    <div x-show="loading && !html" class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 space-y-3">
        <template x-for="i in 5" :key="i">
            <div class="skelly sk-card">
                <div class="grid grid-cols-4 gap-4 px-2">
                    <div class="skelly sk-line-md col-span-2"></div>
                    <div class="skelly sk-line-md"></div>
                    <div class="skelly sk-line-sm"></div>
                    <div class="skelly sk-line-sm"></div>
                </div>
            </div>
        </template>
    </div>

    <!-- Results injected via AJAX -->
    <div x-show="error" x-cloak class="m-4 p-3 rounded-lg bg-red-50 border border-red-200 text-sm text-red-700 flex items-center justify-between gap-3"><span x-text="error"></span><button type="button" @click="reload()" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-white border border-red-200 hover:bg-red-100">Refresh</button></div>
    <div x-show="(loading || isRateLimited) && html" x-cloak class="px-3 py-2 rounded-lg bg-amber-50 dark:bg-[rgba(245,158,11,0.12)] border border-amber-200 dark:border-[rgba(245,158,11,0.3)] text-xs text-amber-800 dark:text-[#FCD34D]">Showing results for &quot;<span class="font-semibold" x-text="displayedSearch"></span>&quot; &mdash; searching for &quot;<span class="font-semibold" x-text="filters.search"></span>&quot;&hellip;</div>
    <div x-show="html || !loading" x-cloak @click="handlePaginationClick($event)" x-ref="results" x-html="html" class="fade-in" :class="((loading || isRateLimited) && html) ? 'opacity-50 transition-opacity' : 'opacity-100 transition-opacity'"></div>
</div>
@endsection
