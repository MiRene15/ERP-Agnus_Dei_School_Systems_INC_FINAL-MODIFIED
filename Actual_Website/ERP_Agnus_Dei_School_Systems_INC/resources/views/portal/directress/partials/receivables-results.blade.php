<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-6">
        <p class="text-sm text-gray-500 dark:text-[#8A90B0] font-medium">Total Receivable</p>
        <p class="text-2xl font-bold text-gray-900 dark:text-[#E8EAF6]">₱ {{ number_format($totalReceivable ?? 0, 2) }}</p>
    </div>
    <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-6">
        <p class="text-sm text-gray-500 dark:text-[#8A90B0] font-medium">Students with Balance</p>
        <p class="text-2xl font-bold text-gray-900 dark:text-[#E8EAF6]">{{ $countReceivable ?? 0 }}</p>
    </div>
    <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-6">
        <p class="text-sm text-gray-500 dark:text-[#8A90B0] font-medium mb-2">By Payment Plan</p>
        <div class="space-y-1 text-sm">
            @forelse($byPlan ?? [] as $plan => $data)
            <div class="flex justify-between gap-3">
                <span class="text-gray-600 dark:text-[#C1C4DC]">{{ ucfirst($plan) }}</span>
                <span class="font-medium text-gray-900 dark:text-[#E8EAF6]">{{ $data['count'] }} — ₱ {{ number_format($data['total'], 2) }}</span>
            </div>
            @empty
            <span class="text-xs text-gray-400 dark:text-[#8A90B0]">No balances outstanding.</span>
            @endforelse
            @if(($unpaid['count'] ?? 0) > 0)
            <div class="flex justify-between gap-3 pt-1 border-t border-gray-100 dark:border-[#2A2F58]">
                <span class="text-gray-600 dark:text-[#C1C4DC]">No payment yet</span>
                <span class="font-medium text-gray-900 dark:text-[#E8EAF6]">{{ $unpaid['count'] }} — ₱ {{ number_format($unpaid['total'], 2) }}</span>
            </div>
            @endif
        </div>
    </div>
</div>

@if(isset($receivables) && $receivables->isNotEmpty())
    @foreach($receivables as $grade => $ledgers)
    <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] overflow-hidden mb-6">
        <div class="px-4 py-3 bg-gray-50 dark:bg-[#161A33] border-b border-gray-200 dark:border-[#2A2F58] flex items-center justify-between">
            <h4 class="text-sm font-medium text-gray-600 dark:text-[#C1C4DC]">{{ $grade }}</h4>
            <span class="text-xs text-gray-500 dark:text-[#8A90B0]">{{ $ledgers->count() }} student(s) — ₱ {{ number_format($ledgers->sum('balance'), 2) }}</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-[#161A33] border-b border-gray-200 dark:border-[#2A2F58]">
                    <tr>
                        <th class="text-left px-4 py-2 font-medium text-gray-600 dark:text-[#8A90B0]">Student</th>
                        <th class="text-left px-4 py-2 font-medium text-gray-600 dark:text-[#8A90B0]">Section</th>
                        <th class="text-left px-4 py-2 font-medium text-gray-600 dark:text-[#8A90B0]">Plan</th>
                        <th class="text-right px-4 py-2 font-medium text-gray-600 dark:text-[#8A90B0]">Balance</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-[#2A2F58]">
                    @foreach($ledgers as $ledger)
                    @php $student = $ledger->student; $enrollment = $student?->enrollments->where('status','Active')->first(); @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-[#23274C]">
                        <td class="px-4 py-2 text-gray-600 dark:text-[#C1C4DC]">{{ $student->first_name ?? '' }} {{ $student->last_name ?? '' }} <span class="text-xs text-gray-400">({{ $student->student_number ?? '' }})</span></td>
                        <td class="py-2 px-2 text-gray-600 dark:text-[#C1C4DC]">{{ $enrollment?->section?->section_name ?? '—' }}</td>
                        <td class="py-2 px-2">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ ($ledger->payment_plan ?? '') === 'full' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700' }}">{{ ucfirst($ledger->payment_plan ?? 'N/A') }}</span>
                        </td>
                        <td class="px-4 py-2 text-right font-medium text-red-600">₱ {{ number_format($ledger->balance, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-blue-50 dark:bg-[rgba(96,165,250,0.12)] border-t border-blue-200 dark:border-[rgba(96,165,250,0.25)] font-medium">
                        <td class="px-4 py-2 text-gray-600 dark:text-[#C1C4DC] text-xs uppercase" colspan="2">{{ $grade }} total ({{ $ledgers->count() }})</td>
                        <td></td>
                        <td class="px-4 py-2 text-right text-blue-800 dark:text-[#60A5FA]">₱ {{ number_format($ledgers->sum('balance'), 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    @endforeach
@else
<div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-8 text-center">
    <p class="text-sm text-gray-400 dark:text-[#8A90B0]">No receivables found. All balances are settled.</p>
</div>
@endif
