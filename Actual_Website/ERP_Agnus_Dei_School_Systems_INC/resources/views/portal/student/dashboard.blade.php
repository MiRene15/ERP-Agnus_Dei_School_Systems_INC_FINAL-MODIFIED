@extends('portal.layouts.app')

@section('breadcrumbs')
    <span class="current">Student Dashboard</span>
@endsection

@section('sidebar-links')
    @if($student->student_number)
        <a href="#" class="sidebar-link">
            <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
            <span class="sidebar-label">My Grades</span>
        </a>
        <a href="#" class="sidebar-link">
            <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            <span class="sidebar-label">Class Schedule</span>
        </a>
        <a href="#" class="sidebar-link">
            <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
            <span class="sidebar-label">Statement of Account</span>
        </a>
    @elseif(!$pendingAdmission)
        <a href="{{ route('student.admission.create') }}" class="sidebar-link">
            <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <span class="sidebar-label">Apply for Admission</span>
        </a>
    @else
        <a href="{{ route('student.admission.status') }}" class="sidebar-link">
            <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <span class="sidebar-label">Application Status</span>
        </a>
    @endif
    @if($student->student_number && !$activeEnrollment && !$pendingAdmission)
        <a href="{{ route('student.enrollment.create') }}" class="sidebar-link">
            <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            <span class="sidebar-label">Enroll Now</span>
        </a>
    @endif
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

<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
    @if(!$student->student_number && !$pendingAdmission)
        <div class="md:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-full bg-blue-100 flex items-center justify-center">
                    <svg class="w-7 h-7 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-bold text-gray-900">Start Your Application</h3>
                    <p class="text-sm text-gray-600 mt-1">Complete your admission application to begin your journey at Agnus Dei School.</p>
                </div>
                <a href="{{ route('student.admission.create') }}" class="px-5 py-2.5 rounded-lg text-sm font-semibold text-white transition flex-shrink-0" style="background: var(--navy);" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">Apply Now</a>
            </div>
        </div>
    @elseif(!$student->student_number && $pendingAdmission)
        <div class="md:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-full bg-yellow-100 flex items-center justify-center">
                    <svg class="w-7 h-7 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-bold text-gray-900">Application Submitted</h3>
                    <p class="text-sm text-gray-600 mt-1">#{{ $pendingAdmission->application_number }} — {{ $pendingAdmission->application_type }} Student for {{ $pendingAdmission->school_year }}. Upload your requirements while waiting.</p>
                </div>
                <a href="{{ route('student.admission.status') }}" class="px-5 py-2.5 rounded-lg text-sm font-semibold text-white transition flex-shrink-0" style="background: var(--navy);" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">View Status</a>
            </div>
        </div>
    @elseif($activeEnrollment)
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                <svg class="w-5 h-5 text-blue-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9.5a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
                Current Academic Standing
            </h3>
            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                <div>
                    <p class="text-sm text-gray-500 font-medium">Section</p>
                    <p class="font-bold text-gray-900">{{ $activeEnrollment->section->grade_level }} - {{ $activeEnrollment->section->section_name }}</p>
                </div>
                <div class="text-right">
                    <p class="text-sm text-gray-500 font-medium">Status</p>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">{{ $activeEnrollment->status }}</span>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Outstanding Balance
            </h3>
            <div class="flex items-center justify-between p-4 bg-red-50 rounded-lg border border-red-100">
                <div>
                    <p class="text-sm text-red-800 font-medium">Total Due (Current Quarter)</p>
                    <p class="text-2xl font-bold text-red-900">₱ 5,200.00</p>
                </div>
                <a href="#" class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded shadow-sm hover:bg-red-700 transition">View Ledger</a>
            </div>
        </div>
    @else
        <div class="md:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-full bg-purple-100 flex items-center justify-center">
                    <svg class="w-7 h-7 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-bold text-gray-900">Enroll for {{ date('Y') + 1 - 1 }}-{{ date('Y') + 1 }}</h3>
                    <p class="text-sm text-gray-600 mt-1">Submit an enrollment request for the upcoming school year.</p>
                </div>
                <a href="{{ route('student.enrollment.create') }}" class="px-5 py-2.5 rounded-lg text-sm font-semibold text-white transition flex-shrink-0" style="background: var(--navy);" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">Enroll Now</a>
            </div>
        </div>
    @endif
</div>
@endsection
