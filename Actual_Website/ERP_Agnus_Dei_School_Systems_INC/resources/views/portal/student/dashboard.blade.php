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

@if($activeEnrollment && $student->ledger && $student->ledger->balance > 0 && $student->ledger->clearance_status !== 'Cleared')
<div class="mb-4 p-4 bg-amber-50 border border-amber-200 rounded-lg text-amber-800 text-sm flex items-start gap-3">
    <svg class="w-5 h-5 text-amber-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    <div>
        <p class="font-semibold">Gentle Reminder</p>
        <p class="mt-1">You have an outstanding balance of <strong>₱{{ number_format($student->ledger->balance, 2) }}</strong>. Please visit the Cashier's Office to settle your payment.</p>
    </div>
</div>
@endif

{{-- ═══════════════════════════════════════════════════════════════ --}}
{{-- NO ENROLLMENT STATE --}}
{{-- ═══════════════════════════════════════════════════════════════ --}}
@if(!$student->student_number && !$pendingAdmission)
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-full bg-blue-100 flex items-center justify-center">
                <svg class="w-7 h-7 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <div class="flex-1">
                <h3 class="text-lg font-bold text-gray-900">Start Your Application</h3>
                <p class="text-sm text-gray-600 mt-1">Complete your admission application to begin your journey at Agnus Dei School.</p>
            </div>
            <a href="{{ route('student.admission.create') }}" class="px-5 py-2.5 rounded-lg text-sm font-semibold text-white transition flex-shrink-0" style="background: var(--navy);">Apply Now</a>
        </div>
    </div>

@elseif(!$student->student_number && $pendingAdmission)
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-full bg-yellow-100 flex items-center justify-center">
                <svg class="w-7 h-7 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="flex-1">
                <h3 class="text-lg font-bold text-gray-900">Application Submitted</h3>
                <p class="text-sm text-gray-600 mt-1">#{{ $pendingAdmission->application_number }} — {{ $pendingAdmission->application_type }} Student for {{ $pendingAdmission->school_year }}. Upload your requirements while waiting.</p>
            </div>
            <a href="{{ route('student.admission.status') }}" class="px-5 py-2.5 rounded-lg text-sm font-semibold text-white transition flex-shrink-0" style="background: var(--navy);">View Status</a>
        </div>
    </div>

@elseif(!$activeEnrollment && $student->student_number)
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-full bg-purple-100 flex items-center justify-center">
                <svg class="w-7 h-7 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
            <div class="flex-1">
                <h3 class="text-lg font-bold text-gray-900">Enroll for {{ date('Y') }}-{{ date('Y') + 1 }}</h3>
                <p class="text-sm text-gray-600 mt-1">Submit an enrollment request for the upcoming school year.</p>
            </div>
            <a href="{{ route('student.enrollment.create') }}" class="px-5 py-2.5 rounded-lg text-sm font-semibold text-white transition flex-shrink-0" style="background: var(--navy);">Enroll Now</a>
        </div>
    </div>

{{-- ═══════════════════════════════════════════════════════════════ --}}
{{-- ENROLLED STATE: STATS + BUTTONS --}}
{{-- ═══════════════════════════════════════════════════════════════ --}}
@elseif($activeEnrollment)
    {{-- Stat Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9.5a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
                </div>
                <span class="text-sm font-medium text-gray-500">Enrollment</span>
            </div>
            <p class="text-xl font-bold text-gray-900">{{ $activeEnrollment->section->grade_level }}</p>
            <p class="text-sm text-gray-600">{{ $activeEnrollment->section->section_name }}</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-lg {{ ($student->ledger->balance ?? 0) > 0 ? 'bg-red-100' : 'bg-green-100' }} flex items-center justify-center">
                    <svg class="w-5 h-5 {{ ($student->ledger->balance ?? 0) > 0 ? 'text-red-600' : 'text-green-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <span class="text-sm font-medium text-gray-500">Balance</span>
            </div>
            <p class="text-xl font-bold {{ ($student->ledger->balance ?? 0) > 0 ? 'text-red-600' : 'text-green-600' }}">₱{{ number_format($student->ledger->balance ?? 0, 2) }}</p>
            <p class="text-sm text-gray-600">{{ ucfirst($student->ledger->payment_plan ?? 'N/A') }} plan</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-lg bg-purple-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                </div>
                <span class="text-sm font-medium text-gray-500">Subjects</span>
            </div>
            <p class="text-xl font-bold text-gray-900">{{ $activeEnrollment->subjects->count() }}</p>
            <p class="text-sm text-gray-600">Enrolled subjects</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-lg bg-orange-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <span class="text-sm font-medium text-gray-500">Status</span>
            </div>
            <p class="text-xl font-bold text-green-600">{{ $activeEnrollment->status }}</p>
            <p class="text-sm text-gray-600">{{ $activeEnrollment->school_year }}</p>
        </div>
    </div>

    {{-- Quick Action Buttons --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
        <a href="#grades" class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex flex-col items-center gap-2 hover:shadow-md transition">
            <div class="w-10 h-10 rounded-lg bg-orange-100 flex items-center justify-center">
                <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
            </div>
            <span class="text-sm font-medium text-gray-700">My Grades</span>
        </a>
        <a href="#schedule" class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex flex-col items-center gap-2 hover:shadow-md transition">
            <div class="w-10 h-10 rounded-lg bg-purple-100 flex items-center justify-center">
                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
            <span class="text-sm font-medium text-gray-700">Schedule</span>
        </a>
        <a href="#ledger" class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex flex-col items-center gap-2 hover:shadow-md transition">
            <div class="w-10 h-10 rounded-lg bg-green-100 flex items-center justify-center">
                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
            </div>
            <span class="text-sm font-medium text-gray-700">Account</span>
        </a>
        <a href="{{ route('student.report-card') }}" class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex flex-col items-center gap-2 hover:shadow-md transition">
            <div class="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <span class="text-sm font-medium text-gray-700">Report Card</span>
        </a>
        <a href="{{ route('student.cor') }}" class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex flex-col items-center gap-2 hover:shadow-md transition">
            <div class="w-10 h-10 rounded-lg bg-indigo-100 flex items-center justify-center">
                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/></svg>
            </div>
            <span class="text-sm font-medium text-gray-700">COR</span>
        </a>
    </div>

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- CLASS SCHEDULE — Column Layout --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6" id="schedule" x-data="scheduleManager()">
        <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
            <svg class="w-5 h-5 text-purple-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            Class Schedule
        </h3>
        @if($activeEnrollment->subjects->isNotEmpty())
        <div class="overflow-x-auto">
            <table class="w-full text-sm border-collapse">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="text-left py-3 px-3 font-semibold text-gray-600 border border-gray-200">Time</th>
                        <th class="text-left py-3 px-3 font-semibold text-gray-600 border border-gray-200">Subject - Teacher</th>
                        @foreach(['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'] as $day)
                        <th class="text-center py-3 px-2 font-semibold text-gray-600 border border-gray-200">{{ substr($day, 0, 3) }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($scheduleSlots as $slot)
                    <tr class="border-b border-gray-100">
                        <td class="py-3 px-3 font-medium text-gray-700 border border-gray-200 text-xs whitespace-nowrap">{{ $slot['time'] }}</td>
                        <td class="py-3 px-3 text-gray-600 border border-gray-200 text-xs">{{ $slot['label'] }}</td>
                        @foreach(['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'] as $day)
                        <td class="py-3 px-2 text-center border border-gray-200">
                            @if(isset($slot['days'][$day]))
                                <div class="text-xs font-medium text-gray-900">{{ $slot['days'][$day]['subject'] }}</div>
                                <div class="text-xs text-gray-500">{{ $slot['days'][$day]['teacher'] }}</div>
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>
                        @endforeach
                    </tr>
                    @endforeach
                    @if(empty($scheduleSlots))
                    <tr>
                        <td colspan="8" class="py-6 text-center text-sm text-gray-400 border border-gray-200">No schedules set.</td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
        @else
        <p class="text-sm text-gray-500 text-center py-4">No subjects assigned yet.</p>
        @endif
    </div>

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- GRADES — Per-Term Filter --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6" id="grades" x-data="{ selectedTerm: 'all' }">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-gray-900 flex items-center">
                <svg class="w-5 h-5 text-orange-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                My Grades
            </h3>
            <select x-model="selectedTerm" class="rounded-lg border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500">
                <option value="all">All Terms</option>
                <option value="1st Term">1st Term</option>
                <option value="2nd Term">2nd Term</option>
                <option value="3rd Term">3rd Term</option>
            </select>
        </div>
        @if($grades->isNotEmpty())
        <div class="overflow-x-auto">
            <table class="w-full text-sm border-collapse">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="text-left py-3 px-3 font-medium text-gray-600 border border-gray-200">Subject</th>
                        <template x-for="period in (selectedTerm === 'all' ? @js($gradingPeriods) : [selectedTerm])" :key="period">
                            <th class="text-center py-3 px-2 font-medium text-gray-600 border border-gray-200" x-text="period"></th>
                        </template>
                        <th class="text-center py-3 px-2 font-medium text-gray-600 border border-gray-200">Final</th>
                        <th class="text-center py-3 px-2 font-medium text-gray-600 border border-gray-200">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($grades as $grade)
                    <tr class="border-b border-gray-100">
                        <td class="py-3 px-3 font-medium text-gray-900 border border-gray-200">{{ $grade->schoolClass->subject->name ?? 'N/A' }}</td>
                        @if($selectedTerm === 'all')
                            @foreach($gradingPeriods as $period)
                            <td class="py-3 px-2 text-center text-gray-700 border border-gray-200">
                                @php
                                    $g = $grades->where('schoolClass.subject.name', $grade->schoolClass->subject->name)->where('grading_period', $period)->first();
                                @endphp
                                {{ $g?->final_grade ?? '—' }}
                            </td>
                            @endforeach
                        @else
                            <td class="py-3 px-2 text-center text-gray-700 border border-gray-200">
                                @php
                                    $g = $grades->where('schoolClass.subject.name', $grade->schoolClass->subject->name)->where('grading_period', $selectedTerm)->first();
                                @endphp
                                {{ $g?->final_grade ?? '—' }}
                            </td>
                        @endif
                        <td class="py-3 px-2 text-center font-semibold text-gray-900 border border-gray-200">{{ $grade->final_grade ?? '—' }}</td>
                        <td class="py-3 px-2 text-center border border-gray-200">
                            @if($grade->final_grade)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $grade->final_grade >= 75 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                {{ $grade->final_grade >= 75 ? 'Passed' : 'Failed' }}
                            </span>
                            @else
                            <span class="text-gray-400">—</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <p class="text-sm text-gray-500 text-center py-4">No grades available yet.</p>
        @endif
    </div>

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- STATEMENT OF ACCOUNT --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6" id="ledger">
        <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
            <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Statement of Account
        </h3>
        @if($student->ledger)
        <div class="space-y-3 text-sm">
            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                <span class="text-gray-600">Payment Plan</span>
                <span class="font-medium text-gray-900">{{ ucfirst($student->ledger->payment_plan) }}</span>
            </div>
            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                <span class="text-gray-600">Total Assessed</span>
                <span class="font-medium text-gray-900">₱ {{ number_format($student->ledger->total_assessed, 2) }}</span>
            </div>
            @if($student->ledger->discount_applied > 0)
            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                <span class="text-gray-600">Discount ({{ ucfirst($student->ledger->discount_type) }})</span>
                <span class="font-medium text-green-600">-₱ {{ number_format($student->ledger->discount_applied, 2) }}</span>
            </div>
            @endif
            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                <span class="text-gray-600">Total Paid</span>
                <span class="font-medium text-green-600">₱ {{ number_format($student->ledger->total_paid, 2) }}</span>
            </div>
            <div class="flex justify-between items-center py-2">
                <span class="text-gray-600 font-medium">Balance</span>
                <span class="font-bold text-lg {{ $student->ledger->balance > 0 ? 'text-red-600' : 'text-green-600' }}">₱ {{ number_format($student->ledger->balance, 2) }}</span>
            </div>
            <div class="flex justify-between items-center py-2">
                <span class="text-gray-600">IT Confirmation</span>
                <span class="font-medium {{ $student->ledger->it_confirmed_at ? 'text-green-600' : 'text-yellow-600' }}">
                    {{ $student->ledger->it_confirmed_at ? 'Confirmed' : 'Pending' }}
                </span>
            </div>
        </div>
        @else
        <p class="text-sm text-gray-500 text-center py-4">No payment records yet.</p>
        @endif
    </div>
@endif

<script>
function scheduleManager() {
    return {
        init() {
            // Schedule is server-rendered, this component is just a placeholder for Alpine
        }
    }
}
</script>
@endsection
