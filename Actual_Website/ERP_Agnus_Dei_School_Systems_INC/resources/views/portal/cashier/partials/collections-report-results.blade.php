<div class="p-4 border-b border-gray-100 dark:border-[#2A2F58] bg-gray-50 dark:bg-[#161A33]">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-[#1A1E3B] rounded-lg border border-gray-200 dark:border-[#2A2F58] p-4">
            <p class="text-xs font-semibold text-gray-500 dark:text-[#8A90B0] uppercase">Total Collections</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-[#E8EAF6] mt-1">₱ {{ number_format($totalCollected ?? $payments->sum('amount_paid'), 2) }}</p>
            <p class="text-xs text-gray-400 dark:text-[#8A90B0] mt-1">{{ $dateFrom ?? '' }} &rarr; {{ $dateTo ?? '' }}</p>
        </div>
        <div class="bg-white dark:bg-[#1A1E3B] rounded-lg border border-gray-200 dark:border-[#2A2F58] p-4">
            <p class="text-xs font-semibold text-gray-500 dark:text-[#8A90B0] uppercase">Total Receipts</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-[#E8EAF6] mt-1">{{ $receiptCount ?? $payments->count() }}</p>
            <p class="text-xs text-gray-400 dark:text-[#8A90B0] mt-1">payment(s) in range</p>
        </div>
        <div class="bg-white dark:bg-[#1A1E3B] rounded-lg border border-gray-200 dark:border-[#2A2F58] p-4">
            <p class="text-xs font-semibold text-gray-500 dark:text-[#8A90B0] uppercase">By Payment Plan</p>
            <div class="mt-1.5 space-y-1 text-sm">
                @php $planData = $byPlan ?? $payments->groupBy(fn($p) => $p->ledger->payment_plan ?? 'Unknown')->map(fn($g) => ['count' => $g->count(), 'total' => $g->sum('amount_paid')]); @endphp
                @forelse($planData as $plan => $data)
                <div class="flex justify-between gap-3">
                    <span class="text-gray-600 dark:text-[#C1C4DC]">{{ ucfirst($plan) }}</span>
                    <span class="font-medium text-gray-900 dark:text-[#E8EAF6]">{{ $data['count'] }} — ₱ {{ number_format($data['total'], 2) }}</span>
                </div>
                @empty
                <span class="text-xs text-gray-400 dark:text-[#8A90B0]">No payments in range.</span>
                @endforelse
            </div>
        </div>
    </div>
</div>

@if(!empty($dailyBreakdown) && count($dailyBreakdown) > 1)
<div class="p-4 border-b border-gray-100 dark:border-[#2A2F58]">
    <h4 class="text-sm font-semibold text-gray-700 dark:text-[#E8EAF6] mb-3">Daily Breakdown <span class="font-normal text-gray-400 dark:text-[#8A90B0]">(grand total above)</span></h4>
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
        @foreach($dailyBreakdown as $day)
        <div class="bg-gray-50 dark:bg-[#161A33] rounded-lg p-3 text-center border border-gray-200 dark:border-[#2A2F58] hover:border-blue-300 dark:hover:border-[#3B4172] transition">
            <p class="text-xs text-gray-500 dark:text-[#8A90B0] font-medium">{{ \Carbon\Carbon::parse($day['date'])->format('M d') }}</p>
            <p class="text-lg font-bold text-gray-900 dark:text-[#E8EAF6] mt-1">₱ {{ number_format($day['total'], 0) }}</p>
            <p class="text-xs text-gray-400 dark:text-[#8A90B0]">{{ $day['count'] }} receipt(s)</p>
        </div>
        @endforeach
    </div>
</div>
@endif

<div class="overflow-x-auto">
<table class="w-full text-sm">
    <thead class="bg-gray-50 dark:bg-[#161A33] border-b border-gray-200 dark:border-[#2A2F58]">
        <tr>
            <th class="text-left px-4 py-3 font-semibold text-gray-600 dark:text-[#C1C4DC]">Date</th>
            <th class="text-left px-4 py-3 font-semibold text-gray-600 dark:text-[#C1C4DC]">Student</th>
            <th class="text-left px-4 py-3 font-semibold text-gray-600 dark:text-[#C1C4DC]">Amount</th>
            <th class="text-left px-4 py-3 font-semibold text-gray-600 dark:text-[#C1C4DC]">Receipt No.</th>
            <th class="text-left px-4 py-3 font-semibold text-gray-600 dark:text-[#C1C4DC]">AR No.</th>
            <th class="text-left px-4 py-3 font-semibold text-gray-600 dark:text-[#C1C4DC]">Plan</th>
            <th class="text-left px-4 py-3 font-semibold text-gray-600 dark:text-[#C1C4DC]">Cashier</th>
        </tr>
    </thead>
    <tbody class="divide-y divide-gray-100 dark:divide-[#2A2F58]">
        @forelse($payments as $p)
        <tr class="hover:bg-gray-50 dark:hover:bg-[#1E2447]">
            <td class="px-4 py-2 text-gray-900 dark:text-[#E8EAF6]">{{ $p->payment_date->format('M d, Y') }}</td>
            <td class="px-4 py-2 text-gray-900 dark:text-[#E8EAF6]">{{ $p->ledger?->student?->first_name }} {{ $p->ledger?->student?->last_name }}</td>
            <td class="px-4 py-2 font-medium text-gray-900 dark:text-[#E8EAF6]">₱ {{ number_format($p->amount_paid, 2) }}</td>
            <td class="px-4 py-2 text-gray-600 dark:text-[#C1C4DC] font-mono text-xs">{{ $p->receipt_number }}</td>
            <td class="px-4 py-2 text-gray-600 dark:text-[#C1C4DC] font-mono text-xs">{{ $p->ar_number ?? '—' }}</td>
            <td class="px-4 py-2">
                <span class="text-xs font-medium px-2 py-0.5 rounded-full {{ ($p->ledger?->payment_plan ?? '') === 'full' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300' : 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300' }}">
                    {{ ucfirst($p->ledger?->payment_plan ?? 'N/A') }}
                </span>
            </td>
            <td class="px-4 py-2 text-gray-500 dark:text-[#8A90B0] text-xs">{{ $p->cashier?->name }}</td>
        </tr>
        @empty
        <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400 dark:text-[#8A90B0]">No payments found for the selected date range.</td></tr>
        @endforelse
    </tbody>
    @if($payments->isNotEmpty())
    <tfoot>
        <tr class="bg-blue-50 dark:bg-[rgba(96,165,250,0.12)] border-t border-blue-200 dark:border-[rgba(96,165,250,0.25)] font-semibold">
            <td class="px-4 py-3 text-gray-700 dark:text-[#C1C4DC] text-xs uppercase" colspan="2">Total Collections ({{ $payments->count() }} receipt(s))</td>
            <td class="px-4 py-3 text-blue-800 dark:text-[#60A5FA]">₱ {{ number_format($payments->sum('amount_paid'), 2) }}</td>
            <td colspan="4"></td>
        </tr>
    </tfoot>
    @endif
</table>
</div>
