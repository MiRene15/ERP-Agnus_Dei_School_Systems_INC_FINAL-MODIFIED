@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('nurse.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <span class="current">Consultation Logs</span>
@endsection

@section('sidebar-links')
    <a href="{{ route('nurse.logs') }}" class="sidebar-link">
        <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
        <span class="sidebar-label">Health Records</span>
    </a>
    <a href="{{ route('nurse.logs') }}" class="sidebar-link">
        <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        <span class="sidebar-label">Consultation Log</span>
    </a>
    {{-- <a href="#" class="sidebar-link">
        <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span class="sidebar-label">Medication Log</span>
    </a> --}}
@endsection

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Consultation Logs</h2>
        <p class="text-gray-600 mt-1">Record of all clinic visits and consultations.</p>
    </div>
    <a href="{{ route('nurse.logs.create') }}" class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        New Log
    </a>
</div>

@if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
@endif

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <form method="GET" class="mb-4">
        <div class="flex gap-2">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by student name..." class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
            <button type="submit" class="px-4 py-2 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">Search</button>
        </div>
    </form>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Date</th>
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Student</th>
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Complaint</th>
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Diagnosis</th>
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Treatment</th>
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Referred To</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                <tr class="border-b border-gray-100">
                    <td class="py-2 px-2 text-gray-900 whitespace-nowrap">{{ \Carbon\Carbon::parse($log->incident_date)->format('M d, Y') }}</td>
                    <td class="py-2 px-2">
                        <span class="font-medium text-gray-900">{{ $log->student->first_name ?? '' }} {{ $log->student->last_name ?? '' }}</span>
                    </td>
                    <td class="py-2 px-2 text-gray-600">{{ $log->complaint ?? $log->symptoms ?? 'N/A' }}</td>
                    <td class="py-2 px-2 text-gray-600">{{ $log->diagnosis ?? 'N/A' }}</td>
                    <td class="py-2 px-2 text-gray-600">{{ $log->treatment ?? 'N/A' }}</td>
                    <td class="py-2 px-2 text-gray-600">{{ $log->referred_to ?? '—' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="py-6 text-center text-gray-500 text-sm">No clinic logs found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $logs->links() }}
    </div>
</div>
@endsection