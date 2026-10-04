<div class="p-4 border-b border-gray-100 dark:border-[#2A2F58] bg-gray-50 dark:bg-[#161A33]">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-[#1A1E3B] rounded-lg border border-gray-200 dark:border-[#2A2F58] p-4">
            <p class="text-xs font-semibold text-gray-500 dark:text-[#8A90B0] uppercase">Total Receivable</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-[#E8EAF6] mt-1">₱ {{ number_format($totalReceivable ?? $receivables->sum('balance'), 2) }}</p>
            <p class="text-xs text-gray-400 dark:text-[#8A90B0] mt-1">still owed</p>
        </div>
        <div class="bg-white dark:bg-[#1A1E3B] rounded-lg border border-gray-200 dark:border-[#2A2F58] p-4">
            <p class="text-xs font-semibold text-gray-500 dark:text-[#8A90B0] uppercase">Students with Balance</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-[#E8EAF6] mt-1">{{ $countReceivable ?? $receivables->count() }}</p>
            <p class="text-xs text-gray-400 dark:text-[#8A90B0] mt-1">student(s) with dues</p>
        </div>
        <div class="bg-white dark:bg-[#1A1E3B] rounded-lg border border-gray-200 dark:border-[#2A2F58] p-4">
            <p class="text-xs font-semibold text-gray-500 dark:text-[#8A90B0] uppercase">By Payment Plan</p>
            <div class="mt-1.5 space-y-1 text-sm">
                @php $planData = $byPlan ?? $receivables->groupBy(fn($l) => $l->payment_plan ?? 'N/A')->map(fn($g) => ['count' => $g->count(), 'total' => $g->sum('balance')]); @endphp
                @forelse($planData as $plan => $data)
                <div class="flex justify-between gap-3">
                    <span class="text-gray-600 dark:text-[#C1C4DC]">{{ ucfirst($plan) }}</span>
                    <span class="font-medium text-gray-900 dark:text-[#E8EAF6]">{{ $data['count'] }} — ₱ {{ number_format($data['total'], 2) }}</span>
                </div>
                @empty
                <span class="text-xs text-gray-400 dark:text-[#8A90B0]">No balances outstanding.</span>
                @endforelse
            </div>
        </div>
    </div>
</div>

@if((!empty($dailyBreakdown) && count($dailyBreakdown) > 0) || ($unpaid['count'] ?? 0) > 0)
<div class="p-4 border-b border-gray-100 dark:border-[#2A2F58]">
    <h4 class="text-sm font-semibold text-gray-700 dark:text-[#E8EAF6] mb-3">Daily Breakdown</h4>
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
        @foreach($dailyBreakdown ?? [] as $day)
        <div class="bg-gray-50 dark:bg-[#161A33] rounded-lg p-3 text-center border border-gray-200 dark:border-[#2A2F58] hover:border-blue-300 dark:hover:border-[#3B4172] transition">
            <p class="text-xs text-gray-500 dark:text-[#8A90B0] font-medium">{{ \Carbon\Carbon::parse($day['date'])->format('M d') }}</p>
            <p class="text-lg font-bold text-gray-900 dark:text-[#E8EAF6] mt-1">₱ {{ number_format($day['total'], 0) }}</p>
            <p class="text-xs text-gray-400 dark:text-[#8A90B0]">{{ $day['count'] }} due(s)</p>
        </div>
        @endforeach
        @if(($unpaid['count'] ?? 0) > 0)
        <div class="bg-gray-50 dark:bg-[#161A33] rounded-lg p-3 text-center border border-gray-200 dark:border-[#2A2F58] hover:border-blue-300 dark:hover:border-[#3B4172] transition">
            <p class="text-xs text-gray-500 dark:text-[#8A90B0] font-medium">No payment yet</p>
            <p class="text-lg font-bold text-gray-900 dark:text-[#E8EAF6] mt-1">₱ {{ number_format($unpaid['total'], 0) }}</p>
            <p class="text-xs text-gray-400 dark:text-[#8A90B0]">{{ $unpaid['count'] }} due(s)</p>
        </div>
        @endif
    </div>
</div>
@endif

<div class="overflow-x-auto">
<table class="w-full text-sm">
    <thead class="bg-gray-50 dark:bg-[#161A33] border-b border-gray-200 dark:border-[#2A2F58]">
        <tr>
            <th class="text-left px-4 py-3 font-semibold text-gray-600 dark:text-[#C1C4DC]">Date</th>
            <th class="text-left px-4 py-3 font-semibold text-gray-600 dark:text-[#C1C4DC]">Student</th>
            <th class="text-left px-4 py-3 font-semibold text-gray-600 dark:text-[#C1C4DC]">LRN</th>
            <th class="text-right px-4 py-3 font-semibold text-gray-600 dark:text-[#C1C4DC]">Balance</th>
        </tr>
    </thead>
    <tbody class="divide-y divide-gray-100 dark:divide-[#2A2F58]">
        @forelse($receivables as $ledger)
        @php $student = $ledger->student; $pay = $ledger->payments->first(); @endphp
        <tr class="hover:bg-gray-50 dark:hover:bg-[#1E2447]">
            <td class="px-4 py-2 text-gray-900 dark:text-[#E8EAF6]">{{ $pay?->payment_date?->format('M d, Y') ?? '—' }}</td>
            <td class="px-4 py-2 text-gray-900 dark:text-[#E8EAF6]">{{ $student->first_name ?? '' }} {{ $student->last_name ?? '' }} <span class="text-xs text-gray-400">({{ $student->student_number ?? '—' }})</span></td>
            <td class="px-4 py-2 text-gray-600 dark:text-[#C1C4DC] font-mono text-xs">{{ $student->legacy_lrn ?? $student->student_number ?? '—' }}</td>
            <td class="px-4 py-2 text-right font-medium text-red-600">₱ {{ number_format($ledger->balance, 2) }}</td>
        </tr>
        @empty
        <tr><td colspan="4" class="px-4 py-8 text-center text-gray-400 dark:text-[#8A90B0]">No receivables found. All balances are settled.</td></tr>
        @endforelse
    </tbody>
</table>
</div>
