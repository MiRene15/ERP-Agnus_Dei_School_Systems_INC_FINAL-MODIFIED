@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('admin.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a><span class="opacity-40"> / </span><span class="current">Settings</span>
@endsection

@section('content')
<div class="mb-6"><h2 class="text-2xl font-bold text-gray-900">School Settings</h2></div>

@if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
@endif

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 max-w-lg">
    <form method="POST" action="{{ route('admin.settings.update') }}">
        @csrf

        <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wide mb-3">School Year</h3>
        <div class="mb-5">
            <label class="block text-sm font-medium text-gray-700 mb-1">Active School Year</label>
            <p class="text-xs text-gray-500 mb-2">This controls which school year data is shown across the system (admissions, enrollments, classes, fees).</p>
            <select name="active_school_year" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                @foreach($schoolYears as $sy)
                <option value="{{ $sy }}" {{ $activeSY === $sy ? 'selected' : '' }}>{{ $sy }}</option>
                @endforeach
            </select>
            <p class="text-xs text-gray-400 mt-1">Past and present school years are available. Add new years by creating fee schedules or enrollments.</p>
        </div>

        <hr class="my-5 border-gray-200">

        <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wide mb-3">School Signatories</h3>
        <p class="text-xs text-gray-500 mb-3">These names appear on official documents (Report Card, Certificate of Registration).</p>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">School Directress</label>
            <input type="text" name="directress_name" value="{{ $directressName }}" maxlength="100" placeholder="e.g. Mrs. Juanita Dela Cruz" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
        </div>

        <div class="mb-5">
            <label class="block text-sm font-medium text-gray-700 mb-1">School Principal</label>
            <input type="text" name="principal_name" value="{{ $principalName }}" maxlength="100" placeholder="e.g. Mr. Jose Santos" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
        </div>

        <button type="submit" class="px-5 py-2 rounded-lg text-sm font-semibold text-white transition hover:opacity-90 cursor-pointer" style="background: var(--navy);">Save Settings</button>
    </form>
</div>
@endsection
