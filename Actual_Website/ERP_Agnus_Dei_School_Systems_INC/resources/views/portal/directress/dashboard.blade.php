@extends('portal.layouts.app')

@section('breadcrumbs')
    <span class="current">Directress Dashboard</span>
@endsection

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900 dark:text-[#E8EAF6]">School Directress Dashboard</h2>
    <p class="text-gray-600 dark:text-[#C1C4DC] mt-1">Manage fees, graduation fees, and teacher profiles.</p>
</div>

<div x-data="ajaxTable('{{ route('directress.dashboard') }}')">
    <div x-show="loading" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <template x-for="i in 4" :key="i">
                <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-5">
                    <div class="skelly sk-line-sm w-24 mb-2"></div>
                    <div class="skelly sk-line-md w-16"></div>
                </div>
            </template>
        </div>
    </div>
    <div x-show="!loading" x-cloak x-html="html" class="fade-in"></div>
</div>
@endsection