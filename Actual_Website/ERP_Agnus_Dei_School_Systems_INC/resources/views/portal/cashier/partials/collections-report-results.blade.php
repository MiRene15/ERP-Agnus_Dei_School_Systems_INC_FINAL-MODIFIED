<table class="w-full text-sm">
    <thead class="bg-gray-50 border-b border-gray-200">
        <tr>
            <th class="text-left px-4 py-3 font-semibold text-gray-600">Date</th>
            <th class="text-left px-4 py-3 font-semibold text-gray-600">Student</th>
            <th class="text-left px-4 py-3 font-semibold text-gray-600">Amount</th>
            <th class="text-left px-4 py-3 font-semibold text-gray-600">Receipt No.</th>
            <th class="text-left px-4 py-3 font-semibold text-gray-600">AR No.</th>
            <th class="text-left px-4 py-3 font-semibold text-gray-600">Plan</th>
            <th class="text-left px-4 py-3 font-semibold text-gray-600">Cashier</th>
        </tr>
    </thead>
    <tbody class="divide-y divide-gray-100">
        @forelse($payments as $p)
        <tr class="hover:bg-gray-50">
            <td class="px-4 py-2 text-gray-900">{{ $p->payment_date->format('M d, Y') }}</td>
            <td class="px-4 py-2 text-gray-900">{{ $p->ledger?->student?->first_name }} {{ $p->ledger?->student?->last_name }}</td>
            <td class="px-4 py-2 font-medium text-gray-900">₱ {{ number_format($p->amount_paid, 2) }}</td>
            <td class="px-4 py-2 text-gray-600 font-mono text-xs">{{ $p->receipt_number }}</td>
            <td class="px-4 py-2 text-gray-600 font-mono text-xs">{{ $p->ar_number ?? '—' }}</td>
            <td class="px-4 py-2">
                <span class="text-xs font-medium px-2 py-0.5 rounded-full {{ ($p->ledger?->payment_plan ?? '') === 'full' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700' }}">
                    {{ ucfirst($p->ledger?->payment_plan ?? 'N/A') }}
                </span>
            </td>
            <td class="px-4 py-2 text-gray-500 text-xs">{{ $p->cashier?->name }}</td>
        </tr>
        @empty
        <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">No payments found for the selected date range.</td></tr>
        @endforelse
    </tbody>
</table>
