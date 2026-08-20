@extends('portal.layouts.app')

@section('breadcrumbs')
    <span class="current">Teacher Dashboard</span>
@endsection

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">Faculty Portal</h2>
    <p class="text-gray-600 mt-1">Manage your classes, submit grades, and view your schedule.</p>
</div>

@if(!auth()->user()->has_seen_welcome)
<div x-data="{ show: true }" x-show="show" x-transition class="mb-4 p-5 bg-gradient-to-r from-orange-50 to-amber-50 border border-orange-200 rounded-xl">
    <div class="flex items-start gap-4">
        <div class="w-12 h-12 rounded-full bg-orange-100 flex items-center justify-center flex-shrink-0">
            <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div class="flex-1">
            <h3 class="font-bold text-gray-900">Welcome to the Faculty Portal!</h3>
            <p class="text-sm text-gray-600 mt-1">View your classes, manage student grades, and check your daily schedule. Click a class to open its grade sheet.</p>
            <div class="flex gap-2 mt-3">
                <a href="{{ route('teacher.schedule') }}" class="text-xs font-semibold text-orange-700 hover:text-orange-900 underline">View Schedule &rarr;</a>
            </div>
        </div>
        <button @click="show = false; fetch('/dismiss-welcome', {method:'POST', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'}})" class="text-gray-400 hover:text-gray-600 flex-shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
</div>
@endif

<div x-data="ajaxTable('{{ route('teacher.dashboard') }}')">
    <div x-show="loading" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <template x-for="i in 3" :key="i">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                    <div class="skelly sk-line-sm w-24 mb-2"></div>
                    <div class="skelly sk-line-md w-16"></div>
                </div>
            </template>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-3">
                <div class="skelly sk-line-md w-32 mb-4"></div>
                <template x-for="i in 3" :key="i">
                    <div class="skelly sk-card"></div>
                </template>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-3">
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
