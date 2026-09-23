@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('teacher.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <span class="current">Grade Table</span>
@endsection

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Grade Table</h2>
        <p class="text-gray-600 mt-1">Spreadsheet-style assessment entry. Edit cells directly and save all at once.</p>
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

<div x-data="ajaxTable('{{ route('teacher.grade-table') }}', { school_year: '{{ $schoolYear }}', class_id: '{{ request('class_id') }}', grading_period: '{{ request('grading_period', '1st Term') }}' })">
    <div x-show="loading" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-3">
        <div class="skelly sk-line-md w-48 mb-4"></div>
        <div class="skelly sk-card">
            <div class="grid grid-cols-10 gap-2 px-2 py-3">
                <template x-for="i in 10" :key="i">
                    <div class="skelly sk-line-sm"></div>
                </template>
            </div>
        </div>
        <template x-for="i in 4" :key="i">
            <div class="skelly sk-card">
                <div class="grid grid-cols-10 gap-2 px-2 py-3">
                    <template x-for="j in 10" :key="j">
                        <div class="skelly sk-line-sm"></div>
                    </template>
                </div>
            </div>
        </template>
    </div>
    <div x-show="!loading" x-cloak x-html="html" class="fade-in"></div>
</div>
@endsection
