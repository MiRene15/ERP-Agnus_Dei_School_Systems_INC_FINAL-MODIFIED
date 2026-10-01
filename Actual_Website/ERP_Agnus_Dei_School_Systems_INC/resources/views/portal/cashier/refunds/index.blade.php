@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('cashier.dashboard') }}" style="color: var(--muted);">Cashier Dashboard</a>
    <span class="opacity-40">/</span>
    <span class="current">Refund Payouts</span>
@endsection

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900 dark:text-[#E8EAF6]">Refund Payouts</h2>
    <p class="text-gray-600 dark:text-[#C1C4DC] mt-1">Withdrawals approved by the Registrar with money due. Releasing posts a REF payment and adjusts the ledger. Only the Cashier releases payouts.</p>
</div>

@if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm">{{ session('error') }}</div>
@endif

<div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-6 mb-6">
    <h3 class="font-semibold text-gray-900 dark:text-[#E8EAF6] mb-4">Awaiting Release ({{ $pending->count() }})</h3>
    @if($pending->isEmpty())
        <p class="text-sm text-gray-500 py-4 text-center">No payouts waiting.</p>
    @else
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200 dark:border-[#2A2F58]">
                    <th class="text-left py-2 px-2 font-medium text-gray-600 dark:text-[#8A90B0]">Student</th>
                    <th class="text-left py-2 px-2 font-medium text-gray-600 dark:text-[#8A90B0]">Section</th>
                    <th class="text-right py-2 px-2 font-medium text-gray-600 dark:text-[#8A90B0]">Refund Due</th>
                    <th class="text-left py-2 px-2 font-medium text-gray-600 dark:text-[#8A90B0]">Approved by</th>
                    <th class="text-center py-2 px-2 font-medium text-gray-600 dark:text-[#8A90B0]">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pending as $w)
                <tr class="border-b border-gray-50 dark:border-[#2A2F58]">
                    <td class="py-2 px-2 font-medium text-gray-900 dark:text-[#E8EAF6]">{{ $w->student->first_name }} {{ $w->student->last_name }}</td>
                    <td class="py-2 px-2 text-gray-600 dark:text-[#C1C4DC]">{{ $w->enrollment->section->section_name ?? '—' }}</td>
                    <td class="py-2 px-2 text-right font-medium text-red-600">₱{{ number_format($w->refund_amount, 2) }}</td>
                    <td class="py-2 px-2 text-xs text-gray-500">{{ $w->processor?->name ?? '—' }}</td>
                    <td class="py-2 px-2 text-center">
                        <form method="POST" action="{{ route('cashier.refunds.release', $w) }}" onsubmit="return confirm('Release refund payout of ₱{{ number_format($w->refund_amount, 2) }} to {{ $w->student->first_name }} {{ $w->student->last_name }}?')" class="inline">
                            @csrf
                            <button type="submit" class="px-3 py-1.5 text-xs font-semibold text-white bg-green-600 hover:bg-green-700 rounded-lg">Release Payout</button>
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
    <h3 class="font-semibold text-gray-900 dark:text-[#E8EAF6] mb-4">Recently Released</h3>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200 dark:border-[#2A2F58]">
                    <th class="text-left py-2 px-2 font-medium text-gray-600 dark:text-[#8A90B0]">Student</th>
                    <th class="text-right py-2 px-2 font-medium text-gray-600 dark:text-[#8A90B0]">Amount</th>
                    <th class="text-left py-2 px-2 font-medium text-gray-600 dark:text-[#8A90B0]">Released</th>
                </tr>
            </thead>
            <tbody>
                @forelse($released as $w)
                <tr class="border-b border-gray-50 dark:border-[#2A2F58]">
                    <td class="py-2 px-2 text-gray-900 dark:text-[#E8EAF6]">{{ $w->student->first_name }} {{ $w->student->last_name }}</td>
                    <td class="py-2 px-2 text-right">₱{{ number_format($w->refund_amount, 2) }}</td>
                    <td class="py-2 px-2 text-xs text-gray-500">{{ $w->refund_processed_at?->format('M d, Y h:i A') ?? '' }}</td>
                </tr>
                @empty
                <tr><td colspan="3" class="py-6 text-center text-gray-500 text-sm">No payouts released yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
