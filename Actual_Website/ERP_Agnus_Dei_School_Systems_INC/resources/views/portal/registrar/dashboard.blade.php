@extends('portal.layouts.app')

@section('breadcrumbs')
    <span class="current">Registrar Dashboard</span>
@endsection

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">Registrar Office</h2>
    <p class="text-gray-600 mt-1">Review student applications, manage academic records, and process enrollments.</p>
</div>

@if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
@endif

@if(!auth()->user()->has_seen_welcome)
<div x-data="{ show: true }" x-show="show" x-transition class="mb-4 p-5 bg-gradient-to-r from-teal-50 to-cyan-50 border border-teal-200 rounded-xl">
    <div class="flex items-start gap-4">
        <div class="w-12 h-12 rounded-full bg-teal-100 flex items-center justify-center flex-shrink-0">
            <svg class="w-6 h-6 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div class="flex-1">
            <h3 class="font-bold text-gray-900">Welcome to the Registrar Dashboard!</h3>
            <p class="text-sm text-gray-600 mt-1">Review student applications, verify requirements, manage enrollments, and process student records.</p>
            <div class="flex gap-2 mt-3">
                <a href="{{ route('registrar.admissions.index') }}" class="text-xs font-semibold text-teal-700 hover:text-teal-900 underline">Review Admissions &rarr;</a>
            </div>
        </div>
        <button @click="show = false; fetch('/dismiss-welcome', {method:'POST', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'}})" class="text-gray-400 hover:text-gray-600 flex-shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
</div>
@endif

<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
    <a href="{{ route('registrar.admissions.index') }}" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition block">
        <h3 class="text-lg font-bold text-gray-900 mb-2">Pending Admissions</h3>
        <p class="text-4xl font-bold text-blue-600">{{ $pendingCount }}</p>
        <p class="text-sm text-gray-500 mt-2">Awaiting document verification</p>
    </a>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-2">Currently Enrolled</h3>
        <p class="text-4xl font-bold text-green-600">{{ $enrolledCount }}</p>
        <p class="text-sm text-gray-500 mt-2">Active enrollments this school year</p>
    </div>
</div>

@if($recentAdmissions->isNotEmpty())
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <h3 class="font-semibold text-gray-900 mb-4">Recent Applications</h3>
    <div class="divide-y divide-gray-50">
        @foreach($recentAdmissions as $admission)
        <div class="py-3 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-xs font-bold text-blue-700">
                    {{ substr($admission->student->first_name, 0, 1) }}{{ substr($admission->student->last_name, 0, 1) }}
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-900">{{ $admission->student->first_name }} {{ $admission->student->last_name }}</p>
                    <p class="text-xs text-gray-500">{{ $admission->application_type }} &middot; {{ $admission->grade_level }}{{ $admission->strand ? ' — '.$admission->strand : '' }} &middot; {{ $admission->school_year }} &middot; {{ $admission->created_at->diffForHumans() }}</p>
                </div>
            </div>
            <a href="{{ route('registrar.admissions.show', $admission) }}" class="text-sm text-blue-600 hover:text-blue-800 font-medium">Review</a>
        </div>
        @endforeach
    </div>
    @if($pendingCount > 5)
    <div class="mt-3 text-center">
        <a href="{{ route('registrar.admissions.index') }}" class="text-sm text-blue-600 hover:text-blue-800 font-medium">View all {{ $pendingCount }} pending admissions &rarr;</a>
    </div>
    @endif
</div>
@endif
@endsection
