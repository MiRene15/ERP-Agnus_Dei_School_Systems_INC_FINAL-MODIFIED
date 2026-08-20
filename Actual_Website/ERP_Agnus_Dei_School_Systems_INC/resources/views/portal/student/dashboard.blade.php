@extends('portal.layouts.app')

@section('breadcrumbs')
    <span class="current">Student Dashboard</span>
@endsection

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">Welcome to your Portal</h2>
    <p class="text-gray-600 mt-1">
        @if(!$student->student_number && !$pendingAdmission)
            Please complete your admission application to get started.
        @elseif(!$student->student_number && $pendingAdmission)
            Your admission application is being reviewed. Check the status and upload requirements.
        @elseif($activeEnrollment)
            You are enrolled in {{ $activeEnrollment->section->grade_level }} - {{ $activeEnrollment->section->section_name }} for {{ $activeEnrollment->school_year }}.
        @else
            Welcome back! Please enroll for the upcoming school year.
        @endif
    </p>
</div>

@if(!auth()->user()->has_seen_welcome)
<div x-data="{ show: true }" x-show="show" x-transition class="mb-4 p-5 bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-xl">
    <div class="flex items-start gap-4">
        <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div class="flex-1">
            <h3 class="font-bold text-gray-900">Welcome to your Student Portal!</h3>
            <p class="text-sm text-gray-600 mt-1">Here you can view your class schedule, check your Statement of Account, download your Report Card and COR. Use the sidebar to navigate between pages.</p>
            <div class="flex gap-2 mt-3">
                <a href="{{ route('student.schedule') }}" class="text-xs font-semibold text-blue-700 hover:text-blue-900 underline">View Schedule &rarr;</a>
                <a href="{{ route('student.ledger') }}" class="text-xs font-semibold text-blue-700 hover:text-blue-900 underline">Check Account &rarr;</a>
            </div>
        </div>
        <button @click="show = false; fetch('/dismiss-welcome', {method:'POST', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'}})" class="text-gray-400 hover:text-gray-600 flex-shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
</div>
@endif
@if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
@endif
@if(session('info'))
    <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg text-blue-800 text-sm">{{ session('info') }}</div>
@endif

<div x-data="ajaxTable('{{ route('student.dashboard') }}')">
    <div x-show="loading" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <template x-for="i in 4" :key="i">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                    <div class="skelly sk-line-sm w-20 mb-2"></div>
                    <div class="skelly sk-line-md w-16 mb-1"></div>
                    <div class="skelly sk-line-sm w-24"></div>
                </div>
            </template>
        </div>
        <div class="grid grid-cols-4 gap-3">
            <template x-for="i in 4" :key="i">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex flex-col items-center gap-2">
                    <div class="skelly sk-card w-10 h-10 rounded-lg"></div>
                    <div class="skelly sk-line-sm w-16"></div>
                </div>
            </template>
        </div>
    </div>
    <div x-show="!loading" x-cloak x-html="html" class="fade-in"></div>
</div>
@endsection
