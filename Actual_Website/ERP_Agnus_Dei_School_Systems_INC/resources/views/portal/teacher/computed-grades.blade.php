@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('teacher.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <span class="current">Computed Grades</span>
@endsection

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h2 class="text-2xl font-bold" style="color: var(--navy);">Computed Grades – DepEd MATATAG K-12</h2>
        <p class="text-gray-600 mt-1">We use MATATAG (DO 15, s. 2026). Review the computed breakdown, then Post to lock.</p>
    </div>
    <div class="flex items-center gap-2">
        <label class="text-sm text-gray-600 font-medium">School Year:</label>
        <select onchange="window.location.href='?school_year='+this.value+'&grading_period={{ request('grading_period','1st Term') }}&class_id={{ request('class_id','') }}'" class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
            @foreach($schoolYears as $sy)
                <option value="{{ $sy }}" {{ $sy === $schoolYear ? 'selected' : '' }}>{{ $sy }}</option>
            @endforeach
        </select>
    </div>
</div>

<div x-data="ajaxTable('{{ route('teacher.computed-grades') }}', { school_year: '{{ $schoolYear }}', class_id: '{{ request('class_id') }}', grading_period: '{{ request('grading_period', '1st Term') }}', grade_level: '{{ request('grade_level') }}', section: '{{ request('section') }}' })">
    <div class="flex gap-2 mb-4 flex-wrap">
        <select x-model="filters.grade_level" @change="reload()" class="rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
            <option value="">All Grades</option>
            @foreach(($pickerGrades ?? collect()) as $gl)
                <option value="{{ $gl }}">{{ $gl }}</option>
            @endforeach
        </select>
        <select x-model="filters.section" @change="reload()" class="rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
            <option value="">All Sections</option>
            @foreach(($pickerSections ?? collect()) as $sec)
                <option value="{{ $sec }}">{{ $sec }}</option>
            @endforeach
        </select>
    </div>
    <div x-show="loading" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-3">
        <div class="skelly sk-line-md w-48 mb-4"></div>
        <div class="skelly sk-line-md w-64 mb-4"></div>
        <div class="skelly sk-card">
            <div class="grid grid-cols-8 gap-2 px-2 py-3">
                <template x-for="i in 8" :key="i">
                    <div class="skelly sk-line-sm"></div>
                </template>
            </div>
        </div>
        <template x-for="i in 4" :key="i">
            <div class="skelly sk-card">
                <div class="grid grid-cols-8 gap-2 px-2 py-3">
                    <template x-for="j in 8" :key="j">
                        <div class="skelly sk-line-sm"></div>
                    </template>
                </div>
            </div>
        </template>
    </div>
    <div x-show="!loading" x-cloak x-html="html" class="fade-in"></div>
</div>
@endsection
