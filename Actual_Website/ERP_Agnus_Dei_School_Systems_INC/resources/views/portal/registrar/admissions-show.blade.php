@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('registrar.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <a href="{{ route('registrar.admissions.index') }}" class="no-underline" style="color: var(--muted);">Admissions Queue</a>
    <span class="opacity-40">/</span>
    <span class="current">{{ $admission->application_number }}</span>
@endsection

@section('sidebar-links')
    <a href="{{ route('registrar.dashboard') }}" class="sidebar-link">
        <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
        <span class="sidebar-label">Dashboard</span>
    </a>
    <a href="{{ route('registrar.admissions.index') }}" class="sidebar-link">
        <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        <span class="sidebar-label">Admissions Queue</span>
    </a>
@endsection

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">Application Review</h2>
    <p class="text-gray-600 mt-1">{{ $admission->application_number }} &middot; {{ $admission->application_type }} Student &middot; {{ $admission->school_year }}</p>
</div>

@if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm">{{ session('error') }}</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-semibold text-gray-900 mb-4">Applicant Information</h3>
            <dl class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <dt class="text-gray-500">Full Name</dt>
                    <dd class="font-medium text-gray-900">{{ $admission->student->first_name }} {{ $admission->student->last_name }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Grade Level</dt>
                    <dd class="font-medium text-gray-900">{{ $admission->grade_level }}</dd>
                </div>
                @if($admission->strand)
                <div>
                    <dt class="text-gray-500">SHS Strand</dt>
                    <dd class="font-medium text-gray-900">{{ $admission->strand }}</dd>
                </div>
                @endif
                <div>
                    <dt class="text-gray-500">Personal Email</dt>
                    <dd class="font-medium text-gray-900">{{ $admission->student->personal_email ?? 'N/A' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Institutional Email</dt>
                    <dd class="font-medium text-gray-900">{{ $admission->student->user->email }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Student No.</dt>
                    <dd class="font-medium text-gray-900">{{ $admission->student->student_number ?? 'Not yet assigned' }}</dd>
                </div>
            </dl>
        </div>

        @if($admission->requirements->isNotEmpty())
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-semibold text-gray-900 mb-4">Submitted Requirements</h3>
            <ul class="divide-y divide-gray-100">
                @foreach($admission->requirements as $req)
                <li class="py-3 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ $req->document_type }}</p>
                            <p class="text-xs text-gray-500">{{ $req->status }}</p>
                        </div>
                    </div>
                    <a href="{{ asset('storage/' . $req->file_path) }}" target="_blank"
                       class="text-sm text-blue-600 hover:text-blue-800 font-medium">View</a>
                </li>
                @endforeach
            </ul>
        </div>
        @else
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <p class="text-sm text-gray-500 text-center py-4">No requirements uploaded yet.</p>
        </div>
        @endif
    </div>

    <div class="lg:col-span-1 space-y-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-semibold text-gray-900 mb-3">Status</h3>
            <div class="mb-4">
                @switch($admission->status)
                    @case('Pending')
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">Pending</span>
                        @break
                    @case('Approved By Registrar')
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">Approved</span>
                        @break
                    @case('Rejected')
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">Rejected</span>
                        @break
                @endswitch
            </div>

            @if($admission->status === 'Pending')
            <form method="POST" action="{{ route('registrar.admissions.approve', $admission) }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Assign Section</label>
                    @if($sections->isEmpty())
                        <div class="p-3 bg-yellow-50 border border-yellow-200 rounded-lg text-sm text-yellow-800">No sections available for {{ $admission->grade_level }}. Please create one first.</div>
                    @else
                    <select name="section_id" required
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                        <option value="">Select section...</option>
                        @foreach($sections as $section)
                            <option value="{{ $section->id }}">{{ $section->section_name }}</option>
                        @endforeach
                    </select>
                    @endif
                </div>
                <button type="submit"
                        class="w-full px-4 py-2 rounded-lg text-sm font-semibold text-white bg-green-600 hover:bg-green-700 transition">
                    Approve & Enroll
                </button>
            </form>

            <form method="POST" action="{{ route('registrar.admissions.reject', $admission) }}"
                  onsubmit="return confirm('Reject this application? This cannot be undone.')">
                @csrf
                <button type="submit"
                        class="w-full px-4 py-2 rounded-lg text-sm font-semibold text-red-700 bg-red-50 hover:bg-red-100 transition mt-2">
                    Reject Application
                </button>
            </form>
            @endif
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-semibold text-gray-900 mb-3">Timeline</h3>
            <ul class="space-y-3 text-sm">
                <li class="flex items-start gap-2">
                    <div class="w-2 h-2 rounded-full bg-green-500 mt-1.5 flex-shrink-0"></div>
                    <div>
                        <p class="font-medium text-gray-900">Submitted</p>
                        <p class="text-gray-500 text-xs">{{ $admission->created_at->format('M d, Y h:i A') }}</p>
                    </div>
                </li>
                @if($admission->status !== 'Pending')
                <li class="flex items-start gap-2">
                    <div class="w-2 h-2 rounded-full {{ $admission->status === 'Approved By Registrar' ? 'bg-green-500' : 'bg-red-500' }} mt-1.5 flex-shrink-0"></div>
                    <div>
                        <p class="font-medium text-gray-900">{{ $admission->status === 'Approved By Registrar' ? 'Approved' : 'Rejected' }}</p>
                        <p class="text-gray-500 text-xs">{{ $admission->updated_at->format('M d, Y h:i A') }}</p>
                    </div>
                </li>
                @endif
            </ul>
        </div>
    </div>
</div>
@endsection
