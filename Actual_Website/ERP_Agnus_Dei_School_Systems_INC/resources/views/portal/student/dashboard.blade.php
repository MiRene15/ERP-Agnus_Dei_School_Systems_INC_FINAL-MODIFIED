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
