@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('registrar.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <span class="current">Fee Assignment</span>
@endsection

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900 dark:text-[#E8EAF6]">Fee Assignment — Bulk from Enrollment</h2>
    <p class="text-gray-600 dark:text-[#C1C4DC] mt-1">One click assigns fees to every eligible enrollment. No more one-by-one assignment. Prices stay with the Directress — this page only assigns.</p>
</div>

@if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
@endif

<div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-6 mb-6">
    <div class="flex items-center justify-between gap-4 flex-wrap">
        <div>
            <h3 class="font-semibold text-gray-900 dark:text-[#E8EAF6]">Tuition Ledgers (SY {{ $schoolYear }})</h3>
            <p class="text-sm text-gray-600 dark:text-[#C1C4DC] mt-1">{{ $missingLedgers->count() }} active enrollment(s) still have no ledger. Ledgers are created from the Directress fee schedule (tuition + misc, installment plan, full balance).</p>
        </div>
        <form method="POST" action="{{ route('registrar.fee-assignment.ledgers') }}" onsubmit="return confirm('Create tuition ledgers for all {{ $missingLedgers->count() }} missing enrollment(s)?')">
            @csrf
            <button type="submit" {{ $missingLedgers->isEmpty() ? 'disabled' : '' }} class="px-6 py-2.5 rounded-lg text-sm font-semibold text-white transition disabled:opacity-40" style="background: var(--navy);" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">Assign All Missing</button>
        </form>
    </div>
</div>

<div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-6">
    <h3 class="font-semibold text-gray-900 dark:text-[#E8EAF6] mb-1">Graduation Fees</h3>
    <p class="text-sm text-gray-600 dark:text-[#C1C4DC] mb-4">Assign each fee to every eligible enrollment missing it.</p>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200 dark:border-[#2A2F58]">
                    <th class="text-left py-2 px-2 font-medium text-gray-600 dark:text-[#8A90B0]">Fee</th>
                    <th class="text-left py-2 px-2 font-medium text-gray-600 dark:text-[#8A90B0]">Grade / Year</th>
                    <th class="text-right py-2 px-2 font-medium text-gray-600 dark:text-[#8A90B0]">Amount</th>
                    <th class="text-center py-2 px-2 font-medium text-gray-600 dark:text-[#8A90B0]">Eligible / Missing</th>
                    <th class="text-center py-2 px-2 font-medium text-gray-600 dark:text-[#8A90B0]">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($gradFees as $fee)
                <tr class="border-b border-gray-50 dark:border-[#2A2F58]">
                    <td class="py-2 px-2 font-medium text-gray-900 dark:text-[#E8EAF6]">Graduation Fee #{{ $fee->id }}</td>
                    <td class="py-2 px-2 text-gray-600 dark:text-[#C1C4DC]">{{ $fee->grade_level ?? 'All grades' }} · {{ $fee->school_year ?? '—' }}</td>
                    <td class="py-2 px-2 text-right font-medium">₱{{ number_format($fee->graduation_fee + $fee->other_fees, 2) }}</td>
                    <td class="py-2 px-2 text-center text-gray-600 dark:text-[#C1C4DC]">{{ $fee->eligible_count }} / {{ $fee->missing_count }}</td>
                    <td class="py-2 px-2 text-center">
                        <form method="POST" action="{{ route('registrar.fee-assignment.graduation-fees') }}" onsubmit="return confirm('Assign to all {{ $fee->missing_count }} missing student(s)?')" class="inline">
                            @csrf
                            <input type="hidden" name="graduation_fee_id" value="{{ $fee->id }}">
                            <button type="submit" {{ $fee->missing_count === 0 ? 'disabled' : '' }} class="px-3 py-1.5 text-xs font-semibold text-white bg-green-600 hover:bg-green-700 rounded-lg disabled:opacity-40">Assign All Missing</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="py-6 text-center text-gray-500 text-sm">No graduation fees defined yet — the Directress sets prices first.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
