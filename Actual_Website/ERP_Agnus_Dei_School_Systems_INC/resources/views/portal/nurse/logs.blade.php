@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('nurse.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <span class="current">Consultation Logs</span>
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

<div class="mb-4 flex gap-2 flex-wrap items-center">
    <form method="GET" class="flex gap-2 flex-1 flex-wrap">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by student name..." class="flex-1 min-w-[200px] rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
        <select name="incident_type" class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
            <option value="All" {{ request('incident_type') === 'All' || !request('incident_type') ? 'selected' : '' }}>All Types</option>
            <option value="Fever" {{ request('incident_type') === 'Fever' ? 'selected' : '' }}>Fever</option>
            <option value="Injury" {{ request('incident_type') === 'Injury' ? 'selected' : '' }}>Injury</option>
            <option value="Headache" {{ request('incident_type') === 'Headache' ? 'selected' : '' }}>Headache</option>
            <option value="Stomachache" {{ request('incident_type') === 'Stomachache' ? 'selected' : '' }}>Stomachache</option>
            <option value="Allergy" {{ request('incident_type') === 'Allergy' ? 'selected' : '' }}>Allergy</option>
        </select>
        <input type="date" name="date_from" value="{{ request('date_from') }}" class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" placeholder="From">
        <input type="date" name="date_to" value="{{ request('date_to') }}" class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" placeholder="To">
        <button type="submit" class="px-4 py-2 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);">Filter</button>
        @if(request()->anyFilled(['search', 'incident_type', 'date_from', 'date_to']))
            <a href="{{ url()->current() }}" class="px-4 py-2 rounded-lg text-sm font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 transition">Clear</a>
        @endif
    </form>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
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