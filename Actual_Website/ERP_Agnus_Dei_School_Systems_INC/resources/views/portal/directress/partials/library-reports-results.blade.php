<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Total Books</p>
        <p class="text-2xl font-bold text-gray-900 mt-1">{{ $totalBooks }}</p>
        <p class="text-xs text-gray-400">{{ $totalCopies }} total copies</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Currently Borrowed</p>
        <p class="text-2xl font-bold text-orange-600 mt-1">{{ $borrowedCount }}</p>
        <p class="text-xs text-gray-400">{{ $availableCopies }} copies available</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Overdue</p>
        <p class="text-2xl font-bold text-red-600 mt-1">{{ $overdueCount }}</p>
        <p class="text-xs text-gray-400">Total fines: ₱{{ number_format($totalFines, 2) }}</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="font-semibold text-gray-900 mb-4">Recent Transactions</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="border-b border-gray-200"><th class="text-left py-2 px-2 font-medium text-gray-600">Student</th><th class="text-left py-2 px-2 font-medium text-gray-600">Book</th><th class="text-left py-2 px-2 font-medium text-gray-600">Status</th></tr></thead>
                <tbody>
                    @forelse($recentTransactions as $txn)
                    <tr class="border-b border-gray-50">
                        <td class="py-2 px-2">{{ $txn->student->first_name ?? '' }} {{ $txn->student->last_name ?? '' }}</td>
                        <td class="py-2 px-2 text-gray-600">{{ $txn->book->title ?? $txn->book_title }}</td>
                        <td class="py-2 px-2">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $txn->status === 'Returned' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">{{ $txn->status }}</span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="py-4 text-center text-gray-400 text-sm">No transactions yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="font-semibold text-gray-900 mb-4">Most Popular Books</h3>
        <div class="space-y-3">
            @forelse($popularBooks as $book)
            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                <div>
                    <p class="font-medium text-gray-900 text-sm">{{ $book->title }}</p>
                    <p class="text-xs text-gray-500">{{ $book->author }}</p>
                </div>
                <span class="text-sm font-semibold text-gray-600">{{ $book->borrowings_count }} borrows</span>
            </div>
            @empty
            <p class="text-sm text-gray-400 text-center py-4">No book data yet.</p>
            @endforelse
        </div>
    </div>
</div>
