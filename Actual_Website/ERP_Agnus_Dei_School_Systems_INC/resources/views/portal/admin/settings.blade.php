@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('admin.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a><span class="opacity-40"> / </span><span class="current">Settings</span>
@endsection

@section('content')
<div class="mb-6"><h2 class="text-2xl font-bold text-gray-900">School Settings</h2></div>

@if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
@endif

<div class="space-y-6 max-w-2xl">
    <form method="POST" action="{{ route('admin.settings.update') }}">
        @csrf

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wide mb-3">School Year</h3>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Active School Year</label>
                <p class="text-xs text-gray-500 mb-2">This controls which school year data is shown across the system (admissions, enrollments, classes, fees).</p>
                <select name="active_school_year" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                    @foreach($schoolYears as $sy)
                    <option value="{{ $sy }}" {{ $activeSY === $sy ? 'selected' : '' }}>{{ $sy }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-400 mt-1">Past and present school years are available. Add new years by creating fee schedules or enrollments.</p>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mt-6">
            <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wide mb-3">School Identity</h3>
            <p class="text-xs text-gray-500 mb-3">Displayed on the public website header, reports, and printed documents.</p>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">School Name</label>
                <input type="text" name="school_name" value="{{ $schoolName }}" maxlength="150" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">School Address</label>
                <input type="text" name="school_address" value="{{ $schoolAddress }}" maxlength="255" placeholder="e.g. Brgy. San Jose, Antipolo City" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Contact Email</label>
                <input type="email" name="contact_email" value="{{ $contactEmail }}" maxlength="150" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Contact Phone</label>
                <input type="text" name="contact_phone" value="{{ $contactPhone }}" maxlength="30" placeholder="+63 9XX XXX XXXX" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
            <hr class="my-4 border-gray-200">
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Signatories (shown on Report Card / COR)</h4>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">School Directress</label>
                <input type="text" name="directress_name" value="{{ $directressName }}" maxlength="100" placeholder="e.g. Mrs. Juanita Dela Cruz" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
            <div class="mb-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">School Principal</label>
                <input type="text" name="principal_name" value="{{ $principalName }}" maxlength="100" placeholder="e.g. Mr. Jose Santos" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mt-6">
            <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wide mb-3">Academic</h3>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Passing Grade (GWA threshold)</label>
                <p class="text-xs text-gray-500 mb-2">Used for Report Card remarks and Promotion "Qualified" badge. Default 75.</p>
                <input type="number" name="passing_grade" value="{{ $passingGrade }}" min="50" max="100" class="w-32 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
            <p class="text-xs text-gray-400 mt-2">Grading weights remain fixed at WW 20% / Quiz 20% / Seatwork 20% / Exam 40% (per DepEd-aligned config).</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mt-6">
            <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wide mb-3">Library</h3>
            <p class="text-xs text-gray-500 mb-3">Fines are posted to the student's ledger on book return.</p>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Late fee / day (₱)</label>
                    <input type="number" step="0.01" name="library_late_fee_per_day" value="{{ $lateFee }}" min="0" max="100" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Loan duration (days)</label>
                    <input type="number" name="library_loan_duration_days" value="{{ $loanDuration }}" min="1" max="60" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Damage — Minor (₱)</label>
                    <input type="number" step="0.01" name="library_damage_minor" value="{{ $damageMinor }}" min="0" max="5000" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Damage — Major (₱)</label>
                    <input type="number" step="0.01" name="library_damage_major" value="{{ $damageMajor }}" min="0" max="10000" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Max books / student</label>
                    <input type="number" name="library_max_books_per_student" value="{{ $maxBooks }}" min="1" max="20" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mt-6">
            <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wide mb-3">System</h3>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Enrollment</label>
                <select name="enrollment_open" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                    <option value="1" {{ $enrollmentOpen === '1' ? 'selected' : '' }}>Open — students can submit admission applications</option>
                    <option value="0" {{ $enrollmentOpen === '0' ? 'selected' : '' }}>Closed — admission form shows "Enrollment is closed"</option>
                </select>
                <p class="text-xs text-gray-400 mt-1">When closed, new inquiries still create accounts but admission submit is blocked.</p>
            </div>
        </div>

        <div class="mt-6 flex items-center gap-3">
            <button type="submit" class="px-6 py-2.5 rounded-lg text-sm font-semibold text-white transition hover:opacity-90 cursor-pointer" style="background: var(--navy);">Save All Settings</button>
            <a href="{{ route('admin.audit-logs') }}" class="text-xs text-gray-500 hover:text-blue-600 underline">View audit logs →</a>
        </div>
    </form>
</div>
@endsection
