@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ auth()->user()->role_id === 9 ? route('principal.dashboard') : route('registrar.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <span class="current">Grade Unlocks</span>
@endsection

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900 dark:text-[#E8EAF6]">Grade Unlock Requests</h2>
    <p class="text-gray-600 dark:text-[#C1C4DC] mt-1">Teachers ask to reopen submitted grades for correction. Approving returns those grades to Pending — the teacher corrects and re-submits.</p>
</div>

@if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm">{{ session('error') }}</div>
@endif

<div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-6 mb-6">
    <h3 class="font-semibold text-gray-900 dark:text-[#E8EAF6] mb-4">Pending ({{ $pending->count() }})</h3>
    @if($pending->isEmpty())
        <p class="text-sm text-gray-500 py-4 text-center">No pending requests.</p>
    @else
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200 dark:border-[#2A2F58]">
                    <th class="text-left py-2 px-2 font-medium text-gray-600 dark:text-[#8A90B0]">Class</th>
                    <th class="text-left py-2 px-2 font-medium text-gray-600 dark:text-[#8A90B0]">Term</th>
                    <th class="text-left py-2 px-2 font-medium text-gray-600 dark:text-[#8A90B0]">Teacher / Reason</th>
                    <th class="text-center py-2 px-2 font-medium text-gray-600 dark:text-[#8A90B0]">Decision</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pending as $req)
                <tr class="border-b border-gray-50 dark:border-[#2A2F58]">
                    <td class="py-2 px-2 font-medium text-gray-900 dark:text-[#E8EAF6]">{{ $req->schoolClass->subject->name ?? '—' }}<span class="block text-xs text-gray-500 font-normal">{{ $req->schoolClass->grade_level ?? '' }} {{ $req->schoolClass->section ?? '' }}</span></td>
                    <td class="py-2 px-2 text-gray-600 dark:text-[#C1C4DC]">{{ $req->grading_period }}</td>
                    <td class="py-2 px-2 text-xs text-gray-600 dark:text-[#C1C4DC] max-w-sm">{{ $req->requester?->name ?? '—' }}: {{ $req->reason }}<span class="block text-gray-400 mt-1">{{ $req->created_at->format('M d, Y h:i A') }}</span></td>
                    <td class="py-2 px-2 text-center whitespace-nowrap">
                        <form method="POST" action="{{ route('registrar.grade-unlocks.approve', $req) }}" class="inline">
                            @csrf
                            <button type="submit" class="px-3 py-1.5 text-xs font-semibold text-white bg-green-600 hover:bg-green-700 rounded-lg">Unlock</button>
                        </form>
                        <form method="POST" action="{{ route('registrar.grade-unlocks.reject', $req) }}" onsubmit="return confirm('Reject this unlock request? Grades stay submitted.')" class="inline">
                            @csrf
                            <button type="submit" class="px-3 py-1.5 text-xs font-semibold text-red-600 hover:text-red-800 bg-red-50 hover:bg-red-100 rounded-lg">Reject</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

<div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-6">
    <h3 class="font-semibold text-gray-900 dark:text-[#E8EAF6] mb-4">Recent Decisions</h3>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200 dark:border-[#2A2F58]">
                    <th class="text-left py-2 px-2 font-medium text-gray-600 dark:text-[#8A90B0]">Class / Term</th>
                    <th class="text-left py-2 px-2 font-medium text-gray-600 dark:text-[#8A90B0]">Teacher</th>
                    <th class="text-left py-2 px-2 font-medium text-gray-600 dark:text-[#8A90B0]">Status</th>
                    <th class="text-left py-2 px-2 font-medium text-gray-600 dark:text-[#8A90B0]">Reviewed</th>
                </tr>
            </thead>
            <tbody>
                @forelse($history as $req)
                <tr class="border-b border-gray-50 dark:border-[#2A2F58]">
                    <td class="py-2 px-2 text-gray-900 dark:text-[#E8EAF6]">{{ $req->schoolClass->subject->name ?? '—' }} <span class="text-xs text-gray-500">· {{ $req->grading_period }}</span></td>
                    <td class="py-2 px-2 text-xs text-gray-500">{{ $req->requester?->name ?? '—' }}</td>
                    <td class="py-2 px-2">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $req->status === 'approved' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">{{ ucfirst($req->status) }}</span>
                    </td>
                    <td class="py-2 px-2 text-xs text-gray-500">{{ $req->reviewer?->name ?? '—' }} · {{ $req->reviewed_at?->format('M d, Y') ?? '' }}</td>
                </tr>
                @empty
                <tr><td colspan="4" class="py-6 text-center text-gray-500 text-sm">No decisions yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
