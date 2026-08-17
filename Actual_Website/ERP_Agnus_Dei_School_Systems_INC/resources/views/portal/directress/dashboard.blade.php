@extends('portal.layouts.app')

@section('breadcrumbs')
    <span class="current">Directress Dashboard</span>
@endsection

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">School Directress Dashboard</h2>
    <p class="text-gray-600 mt-1">Manage fees, graduation fees, and teacher profiles.</p>
</div>

@if(!auth()->user()->has_seen_welcome)
<div x-data="{ show: true }" x-show="show" x-transition class="mb-4 p-5 bg-gradient-to-r from-rose-50 to-pink-50 border border-rose-200 rounded-xl">
    <div class="flex items-start gap-4">
        <div class="w-12 h-12 rounded-full bg-rose-100 flex items-center justify-center flex-shrink-0">
            <svg class="w-6 h-6 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div class="flex-1">
            <h3 class="font-bold text-gray-900">Welcome to the Directress Dashboard!</h3>
            <p class="text-sm text-gray-600 mt-1">Manage teacher profiles, fee schedules, and graduation fees. Use the sidebar for all configuration tools.</p>
        </div>
        <button @click="show = false; fetch('/dismiss-welcome', {method:'POST', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'}})" class="text-gray-400 hover:text-gray-600 flex-shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
</div>
@endif

<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <p class="text-sm text-gray-500">Total Teachers</p>
        <p class="text-2xl font-bold text-gray-900 mt-1">{{ $totalTeachers }}</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <p class="text-sm text-gray-500">Active Teachers</p>
        <p class="text-2xl font-bold text-green-600 mt-1">{{ $activeTeachers }}</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <p class="text-sm text-gray-500">Fee Schedules</p>
        <p class="text-2xl font-bold text-gray-900 mt-1">{{ $feeSchedules }}</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <p class="text-sm text-gray-500">Graduation Fees</p>
        <p class="text-2xl font-bold text-gray-900 mt-1">{{ $graduationFees }}</p>
    </div>
</div>
@endsection
