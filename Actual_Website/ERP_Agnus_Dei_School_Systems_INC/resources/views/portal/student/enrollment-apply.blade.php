@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('student.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <span class="current">Enrollment Request</span>
@endsection

@section('sidebar-links')
    <a href="{{ route('student.enrollment.create') }}" class="sidebar-link">
        <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        <span class="sidebar-label">Enrollment Request</span>
    </a>
@endsection

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">Enrollment Request</h2>
    <p class="text-gray-600 mt-1">Request enrollment for the upcoming school year.</p>
</div>

@if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm">{{ session('error') }}</div>
@endif
@if(session('info'))
    <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg text-blue-800 text-sm">{{ session('info') }}</div>
@endif

@if($pendingAdmission)
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-yellow-100 flex items-center justify-center">
                <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <h3 class="font-semibold text-gray-900">Enrollment Request Pending</h3>
                <p class="text-sm text-gray-600">Your request for {{ $pendingAdmission->school_year }} is awaiting registrar approval.</p>
            </div>
        </div>
    </div>
@else
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 max-w-2xl">
        @if($activeEnrollment)
            <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg text-blue-800 text-sm flex items-center gap-2">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Currently enrolled in {{ $activeEnrollment->section->grade_level }} - {{ $activeEnrollment->section->section_name }} ({{ $activeEnrollment->school_year }})
            </div>
        @endif

        <form method="POST" action="{{ route('student.enrollment.store') }}">
            @csrf

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Grade Level</label>
                <select name="grade_level" required
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                    <option value="">Select grade level...</option>
                    @if($nextGradeLevel)
                        <option value="{{ $nextGradeLevel }}" selected>{{ $nextGradeLevel }} (recommended)</option>
                    @endif
                    <optgroup label="Kindergarten">
                        <option value="Kinder">Kinder</option>
                    </optgroup>
                    <optgroup label="Elementary">
                        @foreach(['Grade 1','Grade 2','Grade 3','Grade 4','Grade 5','Grade 6'] as $g)
                            <option value="{{ $g }}" {{ $nextGradeLevel === $g ? 'selected' : '' }}>{{ $g }}</option>
                        @endforeach
                    </optgroup>
                    <optgroup label="Junior High School">
                        @foreach(['Grade 7','Grade 8','Grade 9','Grade 10'] as $g)
                            <option value="{{ $g }}" {{ $nextGradeLevel === $g ? 'selected' : '' }}>{{ $g }}</option>
                        @endforeach
                    </optgroup>
                    <optgroup label="Senior High School">
                        @foreach(['Grade 11','Grade 12'] as $g)
                            <option value="{{ $g }}" {{ $nextGradeLevel === $g ? 'selected' : '' }}>{{ $g }}</option>
                        @endforeach
                    </optgroup>
                </select>
                @error('grade_level') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">School Year</label>
                <select name="school_year" required
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                    <option value="">Select school year...</option>
                    <option value="2026-2027">2026-2027</option>
                    <option value="2027-2028">2027-2028</option>
                </select>
                @error('school_year') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <button type="submit"
                    class="px-6 py-2.5 rounded-lg text-sm font-semibold text-white transition"
                    style="background: var(--navy);" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                Submit Enrollment Request
            </button>
        </form>
    </div>
@endif
@endsection
