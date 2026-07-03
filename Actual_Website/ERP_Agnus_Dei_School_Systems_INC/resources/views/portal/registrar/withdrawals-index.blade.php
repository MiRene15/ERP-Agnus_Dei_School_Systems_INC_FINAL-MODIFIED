@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('registrar.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <span class="current">Withdrawal Requests</span>
@endsection

@section('sidebar-links')
    <a href="{{ route('registrar.dashboard') }}" class="sidebar-link"><svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg><span class="sidebar-label">Dashboard</span></a>
    <a href="{{ route('registrar.admissions.index') }}" class="sidebar-link"><svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg><span class="sidebar-label">Admissions Queue</span></a>
    <a href="{{ route('registrar.withdrawals.index') }}" class="sidebar-link"><svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg><span class="sidebar-label">Withdrawals</span></a>
@endsection

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">Withdrawal Requests</h2>
    <p class="text-gray-600 mt-1">Review and process student withdrawal requests.</p>
</div>

@if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
@endif

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    @if($withdrawals->isEmpty())
        <p class="text-sm text-gray-500 text-center py-4">No withdrawal requests.</p>
    @else
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Student</th>
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Section</th>
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Reason</th>
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Status</th>
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($withdrawals as $w)
                <tr class="border-b border-gray-100">
                    <td class="py-3 px-2">
                        <span class="font-medium text-gray-900">{{ $w->student->first_name }} {{ $w->student->last_name }}</span>
                    </td>
                    <td class="py-3 px-2 text-gray-700">{{ $w->enrollment->section->grade_level }} - {{ $w->enrollment->section->section_name }}</td>
                    <td class="py-3 px-2 text-gray-700 max-w-xs truncate">{{ $w->reason }}</td>
                    <td class="py-3 px-2">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                            {{ $w->status === 'Pending' ? 'bg-yellow-100 text-yellow-700' : ($w->status === 'Approved' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700') }}">
                            {{ $w->status }}
                        </span>
                    </td>
                    <td class="py-3 px-2">
                        @if($w->status === 'Pending')
                        <div class="flex gap-1">
                            <form method="POST" action="{{ route('registrar.withdrawals.approve', $w) }}" class="inline">
                                @csrf
                                <button type="submit" class="px-2 py-1 text-xs font-medium text-green-600 hover:text-green-800 bg-green-50 hover:bg-green-100 rounded">Approve</button>
                            </form>
                            <form method="POST" action="{{ route('registrar.withdrawals.reject', $w) }}" class="inline" onsubmit="return confirm('Reject this withdrawal?')">
                                @csrf
                                <input type="text" name="remarks" placeholder="Optional remarks..." class="w-32 px-2 py-1 text-xs border border-gray-200 rounded">
                                <button type="submit" class="px-2 py-1 text-xs font-medium text-red-600 hover:text-red-800 bg-red-50 hover:bg-red-100 rounded">Reject</button>
                            </form>
                        </div>
                        @else
                            <span class="text-xs text-gray-400">{{ $w->status }} by {{ $w->processor?->name ?? 'System' }}</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
@endsection
