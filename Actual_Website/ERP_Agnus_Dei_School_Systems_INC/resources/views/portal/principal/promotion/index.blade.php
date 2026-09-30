@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('principal.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <span class="current">Promotion Approvals</span>
@endsection

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">Promotion Approvals</h2>
    <p class="text-gray-600 mt-1">Proposals prepared by the Registrar. Approve to send to the Directress for sign-off, or reject back. Nothing executes from this page.</p>
</div>

@if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm">{{ session('error') }}</div>
@endif

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <h3 class="font-semibold text-gray-900 mb-4">Proposed ({{ $proposals->count() }})</h3>
    @if($proposals->isEmpty())
        <p class="text-sm text-gray-500 py-4 text-center">No proposals waiting for approval.</p>
    @else
    <div class="overflow-x-auto">
        <table class="w-full min-w-[960px] text-sm">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="text-left py-2 px-2 font-medium text-gray-600">Student</th>
                    <th class="text-left py-2 px-2 font-medium text-gray-600">Section / Year</th>
                    <th class="text-left py-2 px-2 font-medium text-gray-600">Proposed</th>
                    <th class="text-left py-2 px-2 font-medium text-gray-600">GWA</th>
                    <th class="text-left py-2 px-2 font-medium text-gray-600">Proposed by</th>
                    <th class="text-center py-2 px-2 font-medium text-gray-600">Decision</th>
                </tr>
            </thead>
            <tbody>
                @foreach($proposals as $proposal)
                @php
                    $q = \App\Services\PromotionService::qualify($proposal->enrollment, $passingGrade ?? 75);
                    $name = $proposal->enrollment->student->first_name . ' ' . $proposal->enrollment->student->last_name;
                @endphp
                <tr class="border-b border-gray-50">
                    <td class="py-2 px-2 font-medium text-gray-900">{{ $name }}<span class="block text-xs text-gray-400 font-normal">{{ $proposal->enrollment->student->student_number }}</span></td>
                    <td class="py-2 px-2 text-gray-600">{{ $proposal->enrollment->section->grade_level ?? '?' }} {{ $proposal->enrollment->section->section_name ?? '' }}<span class="block text-xs text-gray-400">→ {{ $proposal->school_year }}</span></td>
                    <td class="py-2 px-2">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700">{{ \App\Models\PromotionProposal::ACTIONS[$proposal->action] ?? $proposal->action }}</span>
                        @if($proposal->reason)<span class="block text-xs text-gray-500 mt-1">{{ $proposal->reason }}</span>@endif
                    </td>
                    <td class="py-2 px-2">
                        @if($q['avg'] === null)
                            <span class="text-gray-400 text-xs">No grades</span>
                        @else
                            <span class="font-semibold {{ $q['qualified'] ? 'text-gray-900' : 'text-red-600' }}">{{ number_format($q['avg'], 2) }}</span>
                            @if($q['failCount'] > 0)<span class="block text-xs text-red-500">{{ $q['failCount'] }} failing</span>@endif
                        @endif
                    </td>
                    <td class="py-2 px-2 text-xs text-gray-500">{{ $proposal->proposer?->name ?? '—' }}<br>{{ $proposal->created_at->format('M d, Y') }}</td>
                    <td class="py-2 px-2 text-center whitespace-nowrap">
                        <form method="POST" action="{{ route('principal.promotion.approve', $proposal) }}" class="inline">
                            @csrf
                            <button type="submit" class="px-3 py-1.5 text-xs font-semibold text-white bg-green-600 hover:bg-green-700 rounded-lg">Approve</button>
                        </form>
                        <form method="POST" action="{{ route('principal.promotion.reject', $proposal) }}" onsubmit="return confirm('Reject this proposal?')" class="inline">
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
@endsection
