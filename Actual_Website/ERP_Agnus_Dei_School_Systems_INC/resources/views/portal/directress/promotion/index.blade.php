@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('directress.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <span class="current">Promotion Sign-off</span>
@endsection

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">Promotion Sign-off</h2>
    <p class="text-gray-600 mt-1">Principal-approved proposals. Signing off <strong>executes immediately</strong> — the system creates the new enrollment and carries fees over.</p>
</div>

@if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm">{{ session('error') }}</div>
@endif

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <h3 class="font-semibold text-gray-900 mb-4">Approved by Principal ({{ $proposals->count() }})</h3>
    @if($proposals->isEmpty())
        <p class="text-sm text-gray-500 py-4 text-center">Nothing waiting for sign-off.</p>
    @else
    <div class="overflow-x-auto">
        <table class="w-full min-w-[900px] text-sm">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="text-left py-2 px-2 font-medium text-gray-600">Student</th>
                    <th class="text-left py-2 px-2 font-medium text-gray-600">Section / Year</th>
                    <th class="text-left py-2 px-2 font-medium text-gray-600">Action</th>
                    <th class="text-left py-2 px-2 font-medium text-gray-600">Chain</th>
                    <th class="text-center py-2 px-2 font-medium text-gray-600">Sign-off</th>
                </tr>
            </thead>
            <tbody>
                @foreach($proposals as $proposal)
                <tr class="border-b border-gray-50">
                    <td class="py-2 px-2 font-medium text-gray-900">{{ $proposal->enrollment->student->first_name }} {{ $proposal->enrollment->student->last_name }}<span class="block text-xs text-gray-400 font-normal">{{ $proposal->enrollment->student->student_number }}</span></td>
                    <td class="py-2 px-2 text-gray-600">{{ $proposal->enrollment->section->grade_level ?? '?' }} {{ $proposal->enrollment->section->section_name ?? '' }}<span class="block text-xs text-gray-400">→ {{ $proposal->school_year }}</span></td>
                    <td class="py-2 px-2">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700">{{ \App\Models\PromotionProposal::ACTIONS[$proposal->action] ?? $proposal->action }}</span>
                        @if($proposal->reason)<span class="block text-xs text-gray-500 mt-1">{{ $proposal->reason }}</span>@endif
                    </td>
                    <td class="py-2 px-2 text-xs text-gray-500">Registrar: {{ $proposal->proposer?->name ?? '—' }}<br>Principal: {{ $proposal->principal_at?->format('M d, Y') ?? '—' }}</td>
                    <td class="py-2 px-2 text-center">
                        <form method="POST" action="{{ route('directress.promotion.signoff', $proposal) }}" onsubmit="return confirm('Sign off and EXECUTE {{ $proposal->action }} for {{ $proposal->enrollment->student->first_name }} {{ $proposal->enrollment->student->last_name }}? This creates enrollments and moves fees.')" class="inline">
                            @csrf
                            <button type="submit" class="px-3 py-1.5 text-xs font-semibold text-white transition" style="background: var(--navy);" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">Sign off & Execute</button>
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
