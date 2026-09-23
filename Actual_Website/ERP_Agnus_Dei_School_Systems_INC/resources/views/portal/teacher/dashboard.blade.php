@extends('portal.layouts.app')

@section('breadcrumbs')
    <span class="current">Teacher Dashboard</span>
@endsection

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-[#E8EAF6]">Faculty Portal</h2>
        <p class="text-gray-600 dark:text-[#C1C4DC] mt-1">Manage your classes, submit grades, and view your schedule.</p>
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

<div x-data="ajaxTable('{{ route('teacher.dashboard') }}', { school_year: '{{ $schoolYear }}' })">
    <div x-show="loading" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <template x-for="i in 3" :key="i">
                <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-5">
                    <div class="skelly sk-line-sm w-24 mb-2"></div>
                    <div class="skelly sk-line-md w-16"></div>
                </div>
            </template>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-6 space-y-3">
                <div class="skelly sk-line-md w-32 mb-4"></div>
                <template x-for="i in 3" :key="i">
                    <div class="skelly sk-card"></div>
                </template>
            </div>
            <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-6 space-y-3">
                <div class="skelly sk-line-md w-40 mb-4"></div>
                <template x-for="i in 3" :key="i">
                    <div class="skelly sk-card"></div>
                </template>
            </div>
        </div>
    </div>
    <div x-show="!loading" x-cloak x-html="html" class="fade-in"></div>
</div>
@endsection
