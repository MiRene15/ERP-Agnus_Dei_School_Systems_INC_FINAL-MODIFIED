<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Total Collected</p>
        <p class="text-2xl font-bold text-green-600 mt-1">₱{{ number_format($totalCollected, 2) }}</p>
        <p class="text-xs text-gray-400">{{ $receiptCount }} receipts</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Avg. Payment</p>
        <p class="text-2xl font-bold text-gray-900 mt-1">₱{{ number_format($avgPayment, 2) }}</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Total Assessed</p>
        <p class="text-2xl font-bold text-gray-900 mt-1">₱{{ number_format($totalAssessed, 2) }}</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Outstanding Balance</p>
        <p class="text-2xl font-bold text-red-600 mt-1">₱{{ number_format($totalBalance, 2) }}</p>
    </div>
</div>

@if($monthlyData->isNotEmpty())
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
    <h3 class="font-semibold text-gray-900 mb-4">Monthly Breakdown</h3>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead><tr class="border-b border-gray-200"><th class="text-left py-2 px-2 font-medium text-gray-600">Month</th><th class="text-right py-2 px-2 font-medium text-gray-600">Receipts</th><th class="text-right py-2 px-2 font-medium text-gray-600">Collected</th></tr></thead>
            <tbody>
                @foreach($monthlyData as $month => $data)
                <tr class="border-b border-gray-50">
                    <td class="py-2 px-2 font-medium">{{ \Carbon\Carbon::parse($month . '-01')->format('F Y') }}</td>
                    <td class="py-2 px-2 text-right text-gray-600">{{ $data['count'] }}</td>
                    <td class="py-2 px-2 text-right font-semibold text-green-600">₱{{ number_format($data['total'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <h3 class="font-semibold text-gray-900 mb-4">Recent Payments</h3>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead><tr class="border-b border-gray-200"><th class="text-left py-2 px-2 font-medium text-gray-600">Date</th><th class="text-left py-2 px-2 font-medium text-gray-600">Student</th><th class="text-right py-2 px-2 font-medium text-gray-600">Amount</th><th class="text-left py-2 px-2 font-medium text-gray-600">Receipt</th></tr></thead>
            <tbody>
                @forelse($payments->take(15) as $p)
                <tr class="border-b border-gray-50">
                    <td class="py-2 px-2">{{ $p->payment_date->format('M d, Y') }}</td>
                    <td class="py-2 px-2">{{ $p->ledger->student->first_name ?? '' }} {{ $p->ledger->student->last_name ?? '' }}</td>
                    <td class="py-2 px-2 text-right font-medium text-green-600">₱{{ number_format($p->amount_paid, 2) }}</td>
                    <td class="py-2 px-2 text-gray-500 font-mono text-xs">{{ $p->receipt_number }}</td>
                </tr>
                @empty
                <tr><td colspan="4" class="py-4 text-center text-gray-400 text-sm">No payments in this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
