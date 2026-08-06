@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('principal.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <span class="current">Student Grades</span>
@endsection

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">Student Grades</h2>
    <p class="text-gray-600 mt-1">View student grades by grade level.</p>
</div>

<div class="mb-4 flex gap-2 flex-wrap">
    @foreach($gradeLevels as $gl)
    <a href="{{ route('principal.grades', array_filter(['grade_level' => $gl, 'school_year' => request('school_year')])) }}"
       class="px-3 py-1.5 rounded-lg text-sm font-medium transition {{ $selectedGrade === $gl ? 'text-white' : 'text-gray-600 bg-gray-100 hover:bg-gray-200' }}"
       style="{{ $selectedGrade === $gl ? 'background: var(--navy);' : '' }}">
        {{ $gl }}
    </a>
    @endforeach
</div>

<div x-data="ajaxTable('{{ route('principal.grades') }}', { search: '{{ request('search') }}', school_year: '{{ request('school_year') }}' })">
    <div class="mb-4 flex gap-2 flex-wrap items-center">
        <form method="GET" class="flex gap-2 flex-1 flex-wrap" @submit.prevent="reload()">
            <input type="text" x-model="filters.search" @input.debounce.300ms="reload()"
                   placeholder="Search by student name or section..."
                   class="flex-1 min-w-[200px] rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
            <select x-model="filters.school_year" @change="reload()"
                    class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                @foreach($schoolYears as $sy)
                    <option value="{{ $sy }}">{{ $sy }}</option>
                @endforeach
            </select>
            <button type="submit" class="px-4 py-2 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);">Search</button>
            <button type="button" @click="reset()" class="px-4 py-2 rounded-lg text-sm font-semibold bg-gray-100 text-gray-700 hover:bg-gray-200 transition">Clear</button>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div x-show="loading" class="p-4 space-y-3">
            <template x-for="i in 5" :key="i">
                <div class="skelly sk-card">
                    <div class="grid grid-cols-5 gap-4 px-2">
                        <div class="skelly sk-line-md col-span-1"></div>
                        <div class="skelly sk-line-md"></div>
                        <div class="skelly sk-line-sm"></div>
                        <div class="skelly sk-line-sm"></div>
                        <div class="skelly sk-line-sm"></div>
                    </div>
                </div>
            </template>
        </div>

        <div x-show="!loading" x-cloak @click="handlePaginationClick($event)" x-ref="results" x-html="html" class="fade-in"></div>
    </div>
</div>
@endsection
