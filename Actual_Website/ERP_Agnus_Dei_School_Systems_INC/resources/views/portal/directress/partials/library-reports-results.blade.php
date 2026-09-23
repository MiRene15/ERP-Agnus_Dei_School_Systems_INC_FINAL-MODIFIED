<div class="flex items-center justify-between mb-6">
    <h3 class="font-semibold text-gray-900 dark:text-[#E8EAF6]">Library Statistics</h3>
    <a href="{{ route('directress.library-reports.export') }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-green-600 text-white hover:bg-green-700">Export CSV</a>
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
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <canvas id="libraryStatusChart" height="200"></canvas>
        </div>
        <div>
            <canvas id="libraryPopularChart" height="200"></canvas>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function(){
    const dark = document.documentElement.classList.contains('dark');
    const tc = dark ? '#E8EAF6' : '#1E293B';
    const gc = dark ? '#2A2F58' : '#E2E8F0';
    // Status doughnut
    new Chart(document.getElementById('libraryStatusChart'), {
        type: 'doughnut',
        data: {
            labels: ['Available', 'Borrowed', 'Overdue'],
            datasets: [{ data: [{{ $availableCopies }}, {{ $borrowedCount }}, {{ $overdueCount }}], backgroundColor: ['#22c55e','#f59e0b','#ef4444'], borderWidth: 0 }]
        },
        options: { plugins: { legend: { labels: { color: tc } } } }
    });
    // Popular books bar
    new Chart(document.getElementById('libraryPopularChart'), {
        type: 'bar',
        data: {
            labels: [@foreach($popularBooks as $b)'{{ Str::limit($b->title, 15) }}',@endforeach],
            datasets: [{ label: 'Borrows', data: [@foreach($popularBooks as $b){{ $b->borrowings_count }},@endforeach], backgroundColor: '#6366f1', borderRadius: 6 }]
        },
        options: { scales: { x: { ticks: { color: tc }, grid: { color: gc } }, y: { ticks: { color: tc }, grid: { color: gc } } }, plugins: { legend: { display: false } } }
    });
})();
</script>

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
