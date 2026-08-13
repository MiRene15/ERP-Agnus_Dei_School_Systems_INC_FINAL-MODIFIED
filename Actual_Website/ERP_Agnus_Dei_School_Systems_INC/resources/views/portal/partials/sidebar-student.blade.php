@php
    $student ??= Auth::user()->student;
    $activeEnrollment ??= $student?->enrollments()->where('status', 'Active')->first();
    $pendingAdmission ??= $student?->admissions()->where('status', 'Pending')->first();
    $draftAdmission ??= $pendingAdmission ? null : $student?->admissions()->where('status', 'Draft')->latest()->first();
    $draftData = $draftAdmission?->draft_data;

    $checkSvg = '<svg width="14" height="14" viewBox="0 0 16 16" fill="#22c55e" class="inline-block ml-auto flex-shrink-0"><path d="M13.78 4.22a.75.75 0 010 1.06l-7.25 7.25a.75.75 0 01-1.06 0L2.22 9.28a.75.75 0 011.06-1.06L6 10.94l6.72-6.72a.75.75 0 011.06 0z"/></svg>';

    $stepsComplete = [];
    if ($student) {
        $stepsComplete['Application Details'] = !empty($pendingAdmission?->application_type) || !empty($draftData['application_type']);
        $stepsComplete['Personal Info'] = !empty($student->first_name) && !empty($student->last_name) && !empty($student->date_of_birth);
        $stepsComplete['Address'] = !empty($student->permanent_address);
        $stepsComplete['Family'] = !empty($student->father_name) || !empty($student->mother_name);
        $stepsComplete['Emergency'] = !empty($student->emergency_contact_name) && !empty($student->emergency_contact_number);
        $stepsComplete['Prev. School'] = !empty($student->previous_school);
    }
@endphp
@if($student && $student->student_number)
    <a href="#grades" class="sidebar-link">
        <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
        <span class="sidebar-label">My Grades</span>
    </a>
    <a href="#schedule" class="sidebar-link">
        <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        <span class="sidebar-label">Class Schedule</span>
    </a>
    <a href="#ledger" class="sidebar-link">
        <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
        <span class="sidebar-label">Statement of Account</span>
    </a>
    @if($pendingAdmission || !$activeEnrollment)
    <a href="{{ route('student.admission.status') }}" class="sidebar-link">
        <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        <span class="sidebar-label">Application Status</span>
    </a>
    @endif
    @if($activeEnrollment)
    <a href="{{ route('student.withdrawal.create') }}" class="sidebar-link">
        <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
        <span class="sidebar-label">Request Withdrawal</span>
    </a>
    <a href="{{ route('student.report-card') }}" class="sidebar-link">
        <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        <span class="sidebar-label">Report Card</span>
    </a>
    <a href="{{ route('student.cor') }}" class="sidebar-link">
        <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/></svg>
        <span class="sidebar-label">COR</span>
    </a>
    @endif
@elseif($draftAdmission || !$pendingAdmission)
    <div style="padding: 9px 11px; margin-bottom: 4px;">
        <div style="font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--muted); margin-bottom: 6px;">Admission Progress</div>
        @foreach($stepsComplete as $label => $done)
        <div style="display: flex; align-items: center; gap: 6px; padding: 3px 0; font-size: 0.8rem; color: {{ $done ? '#16a34a' : 'var(--muted)' }};">
            @if($done)
                {!! $checkSvg !!}
            @else
                <span style="width: 14px; height: 14px; border-radius: 50%; border: 1.5px solid #d1d5db; display: inline-block; flex-shrink: 0;"></span>
            @endif
            <span>{{ $label }}</span>
        </div>
        @endforeach
    </div>
    @if($draftAdmission)
    <a href="{{ route('student.admission.create') }}" class="sidebar-link">
        <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
        <span class="sidebar-label">Continue Application</span>
    </a>
    @else
    <a href="{{ route('student.admission.create') }}" class="sidebar-link">
        <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        <span class="sidebar-label">Apply for Admission</span>
    </a>
    @endif
@else
    <a href="{{ route('student.admission.status') }}" class="sidebar-link">
        <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        <span class="sidebar-label">Application Status</span>
    </a>
@endif
@if($student && $student->student_number && !$activeEnrollment && !$pendingAdmission)
    <a href="{{ route('student.enrollment.create') }}" class="sidebar-link">
        <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        <span class="sidebar-label">Enroll Now</span>
    </a>
@endif
