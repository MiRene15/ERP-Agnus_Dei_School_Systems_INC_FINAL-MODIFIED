@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('registrar.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <span class="current">Admissions Queue</span>
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
    {{-- <a href="#" class="sidebar-link">
        <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
        <span class="sidebar-label">Student Records</span>
    </a> --}}
@endsection

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Admissions Queue</h2>
        <p class="text-gray-600 mt-1">Review and process student admission applications.</p>
    </div>
    <div class="flex gap-3 text-sm">
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg px-3 py-2">
            <span class="font-bold text-yellow-800">{{ $pendingCount }}</span>
            <span class="text-yellow-700">Pending</span>
        </div>
        <div class="bg-green-50 border border-green-200 rounded-lg px-3 py-2">
            <span class="font-bold text-green-800">{{ $approvedCount }}</span>
            <span class="text-green-700">Approved</span>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
@endif

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    @if($admissions->isEmpty())
        <div class="p-6 text-center text-gray-500">No admissions applications yet.</div>
    @else
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 bg-gray-50/50">
                    <th class="text-left px-4 py-3 font-semibold text-gray-700">App No.</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-700">Applicant</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-700">Type</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-700">Grade Level</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-700">Strand</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-700">School Year</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-700">Submitted</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-700">Status</th>
                    <th class="text-right px-4 py-3 font-semibold text-gray-700">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($admissions as $admission)
                <tr class="hover:bg-gray-50/50 transition">
                    <td class="px-4 py-3 font-mono text-xs">{{ $admission->application_number }}</td>
                    <td class="px-4 py-3 font-medium text-gray-900">{{ $admission->student->first_name }} {{ $admission->student->last_name }}</td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                            {{ $admission->application_type === 'New' ? 'bg-blue-100 text-blue-800' : ($admission->application_type === 'Old' ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800') }}">
                            {{ $admission->application_type }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-700 font-medium">{{ $admission->grade_level }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $admission->strand ?? '—' }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $admission->school_year }}</td>
                    <td class="px-4 py-3 text-gray-500 text-xs">{{ $admission->created_at->diffForHumans() }}</td>
                    <td class="px-4 py-3">
                        @switch($admission->status)
                            @case('Pending')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Pending</span>
                                @break
                            @case('Approved By Registrar')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Approved</span>
                                @break
                            @case('Rejected')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Rejected</span>
                                @break
                            @default
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">{{ $admission->status }}</span>
                        @endswitch
                    </td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('registrar.admissions.show', $admission) }}"
                           class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium text-blue-700 bg-blue-50 hover:bg-blue-100 transition">
                            Review
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
