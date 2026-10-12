@extends('portal.layouts.app')
@section('breadcrumbs')
    <a href="{{ route('student.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <span class="current">Report Card</span>
@endsection

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">My Report Card</h2>
        <p class="text-gray-600 mt-1">{{ $enrollment->section->grade_level }} - {{ $enrollment->section->section_name ?? 'N/A' }} · {{ $schoolYear ?? $enrollment->school_year }}</p>
    </div>
    <div class="flex items-center gap-2">
        <label class="text-sm text-gray-600 font-medium">School Year:</label>
        <select onchange="window.location.href='?school_year='+this.value" class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
            @foreach(($schoolYears ?? collect([$enrollment->school_year])) as $sy)
                <option value="{{ $sy }}" {{ $sy === ($schoolYear ?? $enrollment->school_year) ? 'selected' : '' }}>{{ $sy }}</option>
            @endforeach
        </select>
    </div>
</div>

<div x-data="ajaxTable('{{ route('student.report-card') }}', { school_year: '{{ $schoolYear ?? $enrollment->school_year }}' })">
    <div x-show="loading" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-3">
        <div class="skelly sk-line-sm w-32 mb-2"></div>
        <div class="skelly sk-line-sm w-48 mb-4"></div>
        <div class="skelly sk-card">
            <div class="grid grid-cols-6 gap-2 px-2 py-3">
                <template x-for="i in 6" :key="i">
                    <div class="skelly sk-line-sm"></div>
                </template>
            </div>
        </div>
        <template x-for="i in 4" :key="i">
            <div class="skelly sk-card">
                <div class="grid grid-cols-6 gap-2 px-2 py-3">
                    <template x-for="j in 6" :key="j">
                        <div class="skelly sk-line-sm"></div>
                    </template>
                </div>
            </div>
        </template>
    </div>
    <div x-show="!loading" x-cloak x-html="html" class="fade-in"></div>
</div>
@endsection
