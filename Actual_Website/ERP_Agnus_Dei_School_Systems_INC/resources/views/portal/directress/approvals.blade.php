@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('directress.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <span class="current">Approvals</span>
@endsection

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900 dark:text-[#E8EAF6]">Approvals</h2>
    <p class="text-gray-600 dark:text-[#C1C4DC] mt-1">Everything awaiting the Directress's decision — discounts and promotion sign-offs — in one place.</p>
</div>

@if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm">{{ session('error') }}</div>
@endif

<div x-data="{ tab: ((v) => ['discount-approvals','promotion'].includes(v) ? v : 'discount-approvals')(new URLSearchParams(window.location.search).get('view')), setTab(v) { this.tab = v; const u = new URL(window.location.href); if (v === 'discount-approvals') { u.searchParams.delete('view'); } else { u.searchParams.set('view', v); } window.history.replaceState({}, '', u); } }">
    <div class="inline-flex gap-1 mb-6 p-1 rounded-xl bg-gray-100 dark:bg-[#23274C]" role="tablist" aria-label="Approval type">
        <button @click="setTab('discount-approvals')" :class="tab==='discount-approvals' ? 'text-white shadow font-semibold' : 'text-gray-500 dark:text-[#8A90B0]'" :style="tab==='discount-approvals' ? 'background: var(--navy);' : ''" class="px-4 py-2 text-sm rounded-lg transition" role="tab" :aria-selected="tab==='discount-approvals'">Discount Approvals @if($badgeCounts['discount-approvals'] > 0)<span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-semibold bg-green-600 text-white">{{ $badgeCounts['discount-approvals'] }}</span>@endif</button>
        <button @click="setTab('promotion')" :class="tab==='promotion' ? 'text-white shadow font-semibold' : 'text-gray-500 dark:text-[#8A90B0]'" :style="tab==='promotion' ? 'background: var(--navy);' : ''" class="px-4 py-2 text-sm rounded-lg transition" role="tab" :aria-selected="tab==='promotion'">Promotion @if($badgeCounts['promotion'] > 0)<span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-semibold bg-amber-500 text-white">{{ $badgeCounts['promotion'] }}</span>@endif</button>
    </div>

    <div x-show="tab==='discount-approvals'" x-cloak>
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Discount Approvals</h2>
            <p class="text-gray-600 mt-1">You approve or reject. Only the Cashier applies approved discounts — you never touch paid marking.</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
            <h3 class="font-semibold text-gray-900 mb-4">Pending ({{ $pending->count() }})</h3>
            @if($pending->isEmpty())
                <p class="text-sm text-gray-500 py-4 text-center">No pending requests.</p>
            @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="text-left py-2 px-2 font-medium text-gray-600">Student</th>
                            <th class="text-left py-2 px-2 font-medium text-gray-600">Type / Amount</th>
                            <th class="text-left py-2 px-2 font-medium text-gray-600">Proof</th>
                            <th class="text-left py-2 px-2 font-medium text-gray-600">Requested by</th>
                            <th class="text-center py-2 px-2 font-medium text-gray-600">Decision</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pending as $req)
                        <tr class="border-b border-gray-50">
                            <td class="py-2 px-2 font-medium text-gray-900">{{ $req->ledger->student->first_name }} {{ $req->ledger->student->last_name }}</td>
                            <td class="py-2 px-2 text-gray-600">{{ \App\Models\DiscountRequest::TYPES[$req->discount_type] ?? $req->discount_type }} — ₱{{ number_format($req->discount_amount, 2) }}</td>
                            <td class="py-2 px-2 text-gray-600 text-xs max-w-xs">{{ $req->proof_details }}</td>
                            <td class="py-2 px-2 text-xs text-gray-500">{{ $req->requester?->name ?? '—' }}<br>{{ $req->created_at->format('M d, Y h:i A') }}</td>
                            <td class="py-2 px-2 text-center whitespace-nowrap">
                                <form method="POST" action="{{ route('directress.discount-requests.approve', $req) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="px-3 py-1.5 text-xs font-semibold text-white bg-green-600 hover:bg-green-700 rounded-lg">Approve</button>
                                </form>
                                <form method="POST" action="{{ route('directress.discount-requests.reject', $req) }}" onsubmit="return confirm('Reject this discount request?')" class="inline">
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

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-semibold text-gray-900 mb-4">Recent Decisions</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="text-left py-2 px-2 font-medium text-gray-600">Student</th>
                            <th class="text-left py-2 px-2 font-medium text-gray-600">Type / Amount</th>
                            <th class="text-left py-2 px-2 font-medium text-gray-600">Status</th>
                            <th class="text-left py-2 px-2 font-medium text-gray-600">Reviewed</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($history as $req)
                        <tr class="border-b border-gray-50">
                            <td class="py-2 px-2 font-medium text-gray-900">{{ $req->ledger->student->first_name }} {{ $req->ledger->student->last_name }}</td>
                            <td class="py-2 px-2 text-gray-600">{{ \App\Models\DiscountRequest::TYPES[$req->discount_type] ?? $req->discount_type }} — ₱{{ number_format($req->discount_amount, 2) }}</td>
                            <td class="py-2 px-2">
                                @php $badge = ['approved' => 'bg-blue-100 text-blue-700', 'rejected' => 'bg-red-100 text-red-700', 'applied' => 'bg-green-100 text-green-700'][$req->status] ?? 'bg-gray-100 text-gray-600'; @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $badge }}">{{ ucfirst($req->status) }}</span>
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
    </div>

    <div x-show="tab==='promotion'" x-cloak>
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Promotion Sign-off</h2>
            <p class="text-gray-600 mt-1">Principal-approved proposals. Signing off <strong>executes immediately</strong> — the system creates the new enrollment and carries fees over.</p>
        </div>

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
    </div>
</div>
@endsection
