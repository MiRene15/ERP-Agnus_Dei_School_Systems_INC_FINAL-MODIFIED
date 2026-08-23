@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('registrar.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a><span class="opacity-40"> / </span><span class="current">Sections</span>
@endsection

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Sections</h2>
        <p class="text-gray-600 mt-1">Manage sections/classrooms per grade level.</p>
    </div>
    <a href="{{ route('registrar.sections.create') }}" class="px-4 py-2 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);">+ Add Section</a>
</div>

@if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
@endif

<div x-data="ajaxTable('{{ route('registrar.sections.index') }}', { search: '{{ request('search') }}', grade_level: '{{ request('grade_level') }}' })">
    <div class="mb-4 flex gap-2 flex-wrap items-center">
        <form method="GET" class="flex gap-2 flex-1 flex-wrap" @submit.prevent="reload()">
            <input type="text" x-model="filters.search" @input.debounce.300ms="reload()"
                   placeholder="Search by section name..."
                   class="flex-1 min-w-[200px] rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
            <select x-model="filters.grade_level" @change="reload()" class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                <option value="All">All Grade Levels</option>
                @foreach($gradeLevels as $gl)
                    @if($gl !== 'All')
                    <option value="{{ $gl }}">{{ $gl }}</option>
                    @endif
                @endforeach
            </select>
            <button type="submit" class="px-4 py-2 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);">Search</button>
            <button type="button" @click="reset()" class="px-4 py-2 rounded-lg text-sm font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 transition">Clear</button>
        </form>
    </div>

    <!-- Skeleton loading -->
    <div x-show="loading" class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 space-y-3">
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
    <div x-show="!loading" x-cloak @click="handlePaginationClick($event)" x-ref="results" x-html="html" class="fade-in"></div>
</div>
@endsection
