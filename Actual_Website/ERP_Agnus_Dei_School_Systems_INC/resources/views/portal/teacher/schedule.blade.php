@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('teacher.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <span class="current">My Schedule</span>
@endsection

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-[#E8EAF6]">My Weekly Schedule</h2>
        <p class="text-gray-600 dark:text-[#C1C4DC] mt-1">{{ $schoolYear }}</p>
    </div>
    <div class="flex items-center gap-2">
        <label class="text-sm text-gray-600 dark:text-[#C1C4DC] font-medium">School Year:</label>
        <select onchange="window.location.href='?school_year='+this.value" class="rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
            @foreach($schoolYears as $sy)
                <option value="{{ $sy }}" {{ $sy === $schoolYear ? 'selected' : '' }}>{{ $sy }}</option>
            @endforeach
        </select>
    </div>
</div>

<div x-data="ajaxTable('{{ route('teacher.schedule') }}', { school_year: '{{ $schoolYear }}' })">
    <div x-show="loading" class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-6">
        <div class="grid grid-cols-5 gap-3">
            <template x-for="i in 5" :key="i">
                <div class="space-y-2">
                    <div class="skelly sk-line-sm rounded-lg"></div>
                    <template x-for="j in 3" :key="j">
                        <div class="skelly sk-card"></div>
                    </template>
                </div>
            </template>
        </div>
    </div>
    <div x-show="!loading" x-cloak x-html="html" class="fade-in"></div>
</div>
@endsection
