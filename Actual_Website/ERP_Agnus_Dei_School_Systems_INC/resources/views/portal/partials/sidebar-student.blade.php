@php
    $student ??= Auth::user()->student;
    $activeEnrollment ??= $student?->enrollments()->where('status', 'Active')->first();
    $pendingAdmission ??= $student?->admissions()->where('status', 'Pending')->first();
    $draftAdmission ??= $pendingAdmission ? null : $student?->admissions()->where('status', 'Draft')->latest()->first();
@endphp
@if($student && $student->student_number)
    <a href="{{ route('student.schedule') }}" class="sidebar-link {{ request()->routeIs('student.schedule') ? 'active' : '' }}">
        <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        <span class="sidebar-label">Class Schedule</span>
    </a>
    <a href="{{ route('student.ledger') }}" class="sidebar-link {{ request()->routeIs('student.ledger') ? 'active' : '' }}">
        <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
        <span class="sidebar-label">Statement of Account</span>
    </a>
    @if($pendingAdmission || !$activeEnrollment)
    <a href="{{ route('student.admission.status') }}" class="sidebar-link {{ request()->routeIs('student.admission.status') ? 'active' : '' }}">
        <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        <span class="sidebar-label">Application Status</span>
    </a>
    @endif
    @if($activeEnrollment)
    <a href="{{ route('student.withdrawal.create') }}" class="sidebar-link {{ request()->routeIs('student.withdrawal.create') ? 'active' : '' }}">
        <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
        <span class="sidebar-label">Request Withdrawal</span>
    </a>
    <a href="{{ route('student.report-card') }}" class="sidebar-link {{ request()->routeIs('student.report-card') ? 'active' : '' }}">
        <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        <span class="sidebar-label">Report Card</span>
    </a>
    <a href="{{ route('student.cor') }}" class="sidebar-link {{ request()->routeIs('student.cor') ? 'active' : '' }}">
        <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/></svg>
        <span class="sidebar-label">COR</span>
    </a>
    @endif
@elseif($draftAdmission)
    <a href="{{ route('student.admission.create') }}" class="sidebar-link {{ request()->routeIs('student.admission.create') ? 'active' : '' }}">
        <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
        <span class="sidebar-label">Continue Application</span>
    </a>
@elseif(!$pendingAdmission)
    <a href="{{ route('student.admission.create') }}" class="sidebar-link {{ request()->routeIs('student.admission.create') ? 'active' : '' }}">
        <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        <span class="sidebar-label">Apply for Admission</span>
    </a>
@else
    <a href="{{ route('student.admission.status') }}" class="sidebar-link {{ request()->routeIs('student.admission.status') ? 'active' : '' }}">
        <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        <span class="sidebar-label">Application Status</span>
    </a>
@endif
@if($student && $student->student_number && !$activeEnrollment && !$pendingAdmission)
    <a href="{{ route('student.enrollment.create') }}" class="sidebar-link {{ request()->routeIs('student.enrollment.create') ? 'active' : '' }}">
        <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        <span class="sidebar-label">Enroll Now</span>
    </a>
@endif
