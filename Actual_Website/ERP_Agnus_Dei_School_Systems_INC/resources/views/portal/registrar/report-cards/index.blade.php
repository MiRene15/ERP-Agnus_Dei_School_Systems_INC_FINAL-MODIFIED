@extends('portal.layouts.app')
@section('breadcrumbs')
    <a href="{{ route('registrar.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <span class="current">Report Cards</span>
@endsection
@section('sidebar-links')
    <a href="{{ route('registrar.dashboard') }}" class="sidebar-link"><svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg><span class="sidebar-label">Dashboard</span></a>
    <a href="{{ route('registrar.admissions.index') }}" class="sidebar-link"><svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg><span class="sidebar-label">Admissions Queue</span></a>
    <a href="{{ route('registrar.withdrawals.index') }}" class="sidebar-link"><svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg><span class="sidebar-label">Withdrawals</span></a>
    <a href="{{ route('registrar.report-cards.index') }}" class="sidebar-link active"><svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg><span class="sidebar-label">Report Cards</span></a>
@endsection
@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">Report Cards</h2>
    <p class="text-gray-600 mt-1">View and print student report cards for {{ active_school_year() }}.</p>
</div>
@if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
@endif
@if($enrollments->isEmpty())
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <p class="text-sm text-gray-500 text-center py-4">No active enrollments found for this school year.</p>
    </div>
@else
    @foreach($enrollments as $grade => $group)
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Grade {{ $grade }}</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="text-left py-3 px-2 font-medium text-gray-600">Student</th>
                        <th class="text-left py-3 px-2 font-medium text-gray-600">Section</th>
                        <th class="text-left py-3 px-2 font-medium text-gray-600">LRN</th>
                        <th class="text-right py-3 px-2 font-medium text-gray-600">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($group as $enrollment)
                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                        <td class="py-3 px-2">
                            <span class="font-medium text-gray-900">{{ $enrollment->student->first_name }} {{ $enrollment->student->last_name }}</span>
                        </td>
                        <td class="py-3 px-2 text-gray-700">{{ $enrollment->section->section_name ?? 'N/A' }}</td>
                        <td class="py-3 px-2 text-gray-700">{{ $enrollment->student->student_number ?? 'N/A' }}</td>
                        <td class="py-3 px-2 text-right">
                            <a href="{{ route('registrar.report-cards.show', $enrollment) }}" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-indigo-600 bg-indigo-50 hover:bg-indigo-100 rounded-lg mr-1">View</a>
                            <a href="{{ route('registrar.report-cards.print', $enrollment) }}" target="_blank" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg">Print PDF</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endforeach
@endif
@endsection
