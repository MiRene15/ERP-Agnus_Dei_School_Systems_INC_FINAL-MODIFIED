<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="font-semibold text-gray-900 mb-4">Account Summary</h3>
        @if($student->ledger)
        <div class="space-y-3 text-sm">
            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                <span class="text-gray-600">Payment Plan</span>
                <span class="font-medium text-gray-900">{{ ucfirst($student->ledger->payment_plan) }}</span>
            </div>
            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                <span class="text-gray-600">Total Assessed</span>
                <span class="font-medium text-gray-900">₱ {{ number_format($student->ledger->total_assessed, 2) }}</span>
            </div>
            @if($student->ledger->discount_applied > 0)
            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                <span class="text-gray-600">Discount ({{ ucfirst($student->ledger->discount_type) }})</span>
                <span class="font-medium text-green-600">-₱ {{ number_format($student->ledger->discount_applied, 2) }}</span>
            </div>
            @endif
            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                <span class="text-gray-600">Total Paid</span>
                <span class="font-medium text-green-600">₱ {{ number_format($student->ledger->total_paid, 2) }}</span>
            </div>
            <div class="flex justify-between items-center py-2">
                <span class="text-gray-600 font-medium">Balance</span>
                <span class="font-bold text-lg {{ $student->ledger->balance > 0 ? 'text-red-600' : 'text-green-600' }}">₱ {{ number_format($student->ledger->balance, 2) }}</span>
            </div>
            <div class="flex justify-between items-center py-2">
                <span class="text-gray-600">IT Confirmation</span>
                <span class="font-medium {{ $student->ledger->it_confirmed_at ? 'text-green-600' : 'text-yellow-600' }}">
                    {{ $student->ledger->it_confirmed_at ? 'Confirmed' : 'Pending' }}
                </span>
            </div>
        </div>
        @else
        <p class="text-sm text-gray-500 text-center py-4">No payment records yet.</p>
        @endif
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="font-semibold text-gray-900 mb-4">Payment History</h3>
        @if($student->ledger && $student->ledger->payments->isNotEmpty())
        <ul class="divide-y divide-gray-100 text-sm">
            @foreach($student->ledger->payments->sortByDesc('created_at') as $payment)
            <li class="py-3">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="font-medium text-gray-900">₱ {{ number_format($payment->amount_paid, 2) }}</p>
                        <p class="text-xs text-gray-500">{{ $payment->payment_date->format('M d, Y') }}</p>
                        <p class="text-xs text-gray-400">Receipt: {{ $payment->receipt_number }}</p>
                    </div>
                </div>
            </li>
            @endforeach
        </ul>
        @else
        <p class="text-sm text-gray-500 text-center py-4">No payments recorded.</p>
        @endif
    </div>
</div>
