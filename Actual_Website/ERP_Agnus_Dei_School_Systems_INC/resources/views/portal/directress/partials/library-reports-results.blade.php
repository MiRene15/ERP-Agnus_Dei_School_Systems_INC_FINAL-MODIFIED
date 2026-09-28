<div class="flex items-center justify-between mb-6">
    <h3 class="font-semibold text-gray-900 dark:text-[#E8EAF6]">Library Statistics</h3>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-4">
        <p class="text-xs text-gray-500 dark:text-[#8A90B0] uppercase tracking-wide">Total Books</p>
        <p class="text-2xl font-bold text-gray-900 dark:text-[#E8EAF6] mt-1">{{ $totalBooks }}</p>
        <p class="text-xs text-gray-400 dark:text-[#8A90B0]">{{ $totalCopies }} total copies</p>
    </div>
    <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-4">
        <p class="text-xs text-gray-500 dark:text-[#8A90B0] uppercase tracking-wide">Currently Borrowed</p>
        <p class="text-2xl font-bold text-orange-600 mt-1">{{ $borrowedCount }}</p>
        <p class="text-xs text-gray-400 dark:text-[#8A90B0]">{{ $availableCopies }} copies available</p>
    </div>
    <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-4">
        <p class="text-xs text-gray-500 dark:text-[#8A90B0] uppercase tracking-wide">Overdue</p>
        <p class="text-2xl font-bold text-red-600 mt-1">{{ $overdueCount }}</p>
        <p class="text-xs text-gray-400 dark:text-[#8A90B0]">Total fines: ₱{{ number_format($totalFines, 2) }}</p>
    </div>
</div>

<div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-6 mb-6">
    <h3 class="font-semibold text-gray-900 dark:text-[#E8EAF6] mb-4">Library Overview</h3>
    @php $statusTotal = max(1, $availableCopies + $borrowedCount + $overdueCount); @endphp
    <div class="flex h-4 rounded-full overflow-hidden bg-gray-100 dark:bg-[#23274C] mb-3">
        <div class="bg-green-500" style="width: {{ round($availableCopies / $statusTotal * 100, 1) }}%"></div>
        <div class="bg-amber-500" style="width: {{ round($borrowedCount / $statusTotal * 100, 1) }}%"></div>
        <div class="bg-red-500" style="width: {{ round($overdueCount / $statusTotal * 100, 1) }}%"></div>
    </div>
    <div class="flex flex-wrap gap-4 text-xs text-gray-600 dark:text-[#C1C4DC]">
        <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-green-500 inline-block"></span>Available: {{ $availableCopies }}</span>
        <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-amber-500 inline-block"></span>Borrowed: {{ $borrowedCount }}</span>
        <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-red-500 inline-block"></span>Overdue: {{ $overdueCount }}</span>
    </div>
    <p class="text-xs text-gray-400 dark:text-[#8A90B0] mt-3">Copy status across all titles — transaction history and popular titles below.</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-6">
        <h3 class="font-semibold text-gray-900 dark:text-[#E8EAF6] mb-4">Recent Transactions</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="border-b border-gray-200 dark:border-[#2A2F58]"><th class="text-left py-2 px-2 font-medium text-gray-600 dark:text-[#C1C4DC]">Student</th><th class="text-left py-2 px-2 font-medium text-gray-600 dark:text-[#C1C4DC]">Book</th><th class="text-left py-2 px-2 font-medium text-gray-600 dark:text-[#C1C4DC]">Status</th></tr></thead>
                <tbody>
                    @forelse($recentTransactions as $txn)
                    <tr class="border-b border-gray-50 dark:border-[#2A2F58] hover:bg-gray-50 dark:hover:bg-[#1E2447]">
                        <td class="py-2 px-2 text-gray-900 dark:text-[#E8EAF6]">{{ $txn->student->first_name ?? '' }} {{ $txn->student->last_name ?? '' }}</td>
                        <td class="py-2 px-2 text-gray-600 dark:text-[#C1C4DC]">{{ $txn->book->title ?? $txn->book_title }}</td>
                        <td class="py-2 px-2">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $txn->status === 'Returned' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">{{ $txn->status }}</span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="py-4 text-center text-gray-400 dark:text-[#8A90B0] text-sm">No transactions yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-6">
        <h3 class="font-semibold text-gray-900 dark:text-[#E8EAF6] mb-4">Most Popular Books</h3>
        <div class="space-y-3">
            @forelse($popularBooks as $book)
            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-[#161A33] rounded-lg border border-transparent dark:border-[#2A2F58]">
                <div>
                    <p class="font-medium text-gray-900 dark:text-[#E8EAF6] text-sm">{{ $book->title }}</p>
                    <p class="text-xs text-gray-500 dark:text-[#8A90B0]">{{ $book->author }}</p>
                </div>
                <span class="text-sm font-semibold text-gray-600 dark:text-[#C1C4DC]">{{ $book->borrowings_count }} borrows</span>
            </div>
            @empty
            <p class="text-sm text-gray-400 dark:text-[#8A90B0] text-center py-4">No book data yet.</p>
            @endforelse
        </div>
    </div>
</div>
