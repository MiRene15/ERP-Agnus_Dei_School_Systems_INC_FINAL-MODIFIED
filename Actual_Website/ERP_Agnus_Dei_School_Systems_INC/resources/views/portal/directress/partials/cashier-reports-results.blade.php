<div class="flex items-center justify-between mb-6">
    <h3 class="font-semibold text-gray-900 dark:text-[#E8EAF6]">Cashier Statistics</h3>
    <a href="{{ route('directress.cashier-reports.export', ['date_from' => $dateFrom, 'date_to' => $dateTo]) }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-green-600 text-white hover:bg-green-700">Export CSV</a>
</div>

<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-4">
        <p class="text-xs text-gray-500 dark:text-[#8A90B0] uppercase tracking-wide">Total Collected</p>
        <p class="text-2xl font-bold text-green-600 mt-1">₱{{ number_format($totalCollected, 2) }}</p>
        <p class="text-xs text-gray-400 dark:text-[#8A90B0]">{{ $receiptCount }} receipts</p>
    </div>
    <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-4">
        <p class="text-xs text-gray-500 dark:text-[#8A90B0] uppercase tracking-wide">Avg. Payment</p>
        <p class="text-2xl font-bold text-gray-900 dark:text-[#E8EAF6] mt-1">₱{{ number_format($avgPayment, 2) }}</p>
    </div>
    <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-4">
        <p class="text-xs text-gray-500 dark:text-[#8A90B0] uppercase tracking-wide">Total Assessed</p>
        <p class="text-2xl font-bold text-gray-900 dark:text-[#E8EAF6] mt-1">₱{{ number_format($totalAssessed, 2) }}</p>
    </div>
    <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-4">
        <p class="text-xs text-gray-500 dark:text-[#8A90B0] uppercase tracking-wide">Outstanding Balance</p>
        <p class="text-2xl font-bold text-red-600 mt-1">₱{{ number_format($totalBalance, 2) }}</p>
    </div>
</div>

<div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-6 mb-6">
    <h3 class="font-semibold text-gray-900 dark:text-[#E8EAF6] mb-4">Collection Trends</h3>
    <canvas id="cashierMonthlyChart" height="120"></canvas>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function(){
    const dark = document.documentElement.classList.contains('dark');
    const tc = dark ? '#E8EAF6' : '#1E293B';
    const gc = dark ? '#2A2F58' : '#E2E8F0';
    new Chart(document.getElementById('cashierMonthlyChart'), {
        type: 'bar',
        data: {
            labels: [@foreach($monthlyData as $m => $d)'{{ \Carbon\Carbon::parse($m.'-01')->format('M Y') }}',@endforeach],
            datasets: [{ label: 'Collected (₱)', data: [@foreach($monthlyData as $d){{ $d['total'] }},@endforeach], backgroundColor: '#22c55e', borderRadius: 6 }, { label: 'Receipts', data: [@foreach($monthlyData as $d){{ $d['count'] }},@endforeach], backgroundColor: '#6366f1', borderRadius: 6, yAxisID: 'y1' }]
        },
        options: { scales: { x: { ticks: { color: tc }, grid: { color: gc } }, y: { ticks: { color: tc }, grid: { color: gc } }, y1: { position: 'right', ticks: { color: tc }, grid: { display: false } } }, plugins: { legend: { labels: { color: tc } } } }
    });
})();
</script>

@if($monthlyData->isNotEmpty())
<div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-6 mb-6">
    <h3 class="font-semibold text-gray-900 dark:text-[#E8EAF6] mb-4">Monthly Breakdown</h3>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead><tr class="border-b border-gray-200 dark:border-[#2A2F58]"><th class="text-left py-2 px-2 font-medium text-gray-600 dark:text-[#C1C4DC]">Month</th><th class="text-right py-2 px-2 font-medium text-gray-600 dark:text-[#C1C4DC]">Receipts</th><th class="text-right py-2 px-2 font-medium text-gray-600 dark:text-[#C1C4DC]">Collected</th></tr></thead>
            <tbody>
                @foreach($monthlyData as $month => $data)
                <tr class="border-b border-gray-50 dark:border-[#2A2F58] hover:bg-gray-50 dark:hover:bg-[#1E2447]">
                    <td class="py-2 px-2 font-medium text-gray-900 dark:text-[#E8EAF6]">{{ \Carbon\Carbon::parse($month . '-01')->format('F Y') }}</td>
                    <td class="py-2 px-2 text-right text-gray-600 dark:text-[#C1C4DC]">{{ $data['count'] }}</td>
                    <td class="py-2 px-2 text-right font-semibold text-green-600">₱{{ number_format($data['total'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-6">
    <h3 class="font-semibold text-gray-900 dark:text-[#E8EAF6] mb-4">Recent Payments</h3>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead><tr class="border-b border-gray-200 dark:border-[#2A2F58]"><th class="text-left py-2 px-2 font-medium text-gray-600 dark:text-[#C1C4DC]">Date</th><th class="text-left py-2 px-2 font-medium text-gray-600 dark:text-[#C1C4DC]">Student</th><th class="text-right py-2 px-2 font-medium text-gray-600 dark:text-[#C1C4DC]">Amount</th><th class="text-left py-2 px-2 font-medium text-gray-600 dark:text-[#C1C4DC]">Receipt</th></tr></thead>
            <tbody>
                @forelse($payments->take(15) as $p)
                <tr class="border-b border-gray-50 dark:border-[#2A2F58] hover:bg-gray-50 dark:hover:bg-[#1E2447]">
                    <td class="py-2 px-2 text-gray-900 dark:text-[#E8EAF6]">{{ $p->payment_date->format('M d, Y') }}</td>
                    <td class="py-2 px-2 text-gray-900 dark:text-[#E8EAF6]">{{ $p->ledger->student->first_name ?? '' }} {{ $p->ledger->student->last_name ?? '' }}</td>
                    <td class="py-2 px-2 text-right font-medium text-green-600">₱{{ number_format($p->amount_paid, 2) }}</td>
                    <td class="py-2 px-2 text-gray-500 dark:text-[#8A90B0] font-mono text-xs">{{ $p->receipt_number }}</td>
                </tr>
                @empty
                <tr><td colspan="4" class="py-4 text-center text-gray-400 dark:text-[#8A90B0] text-sm">No payments in this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
