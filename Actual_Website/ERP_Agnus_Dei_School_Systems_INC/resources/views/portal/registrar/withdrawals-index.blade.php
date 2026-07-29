@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('registrar.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <span class="current">Withdrawal Requests</span>
@endsection

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">Withdrawal Requests</h2>
    <p class="text-gray-600 mt-1">Review and process student withdrawal requests.</p>
</div>

@if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
@endif

<div class="mb-4 flex gap-2 flex-wrap items-center">
    <form method="GET" class="flex gap-2 flex-1 flex-wrap">
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="Search by student name or section..."
               class="flex-1 min-w-[200px] rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
        <select name="status" class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
            <option value="All" {{ request('status') === 'All' || !request('status') ? 'selected' : '' }}>All Status</option>
            <option value="Pending" {{ request('status') === 'Pending' ? 'selected' : '' }}>Pending</option>
            <option value="Approved" {{ request('status') === 'Approved' ? 'selected' : '' }}>Approved</option>
            <option value="Rejected" {{ request('status') === 'Rejected' ? 'selected' : '' }}>Rejected</option>
        </select>
        <button type="submit" class="px-4 py-2 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);">Search</button>
        @if(request()->anyFilled(['search', 'status']))
            <a href="{{ url()->current() }}" class="px-4 py-2 rounded-lg text-sm font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 transition">Clear</a>
        @endif
    </form>
</div>

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
    <div class="mt-4">
        {{ $withdrawals->links() }}
    </div>
    @endif
</div>
@endsection
