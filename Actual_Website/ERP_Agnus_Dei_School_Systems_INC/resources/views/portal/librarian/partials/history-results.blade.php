<div class="overflow-x-auto">
<table class="w-full text-sm">
<thead><tr class="border-b border-gray-200"><th class="text-left py-3 px-2 font-medium text-gray-600">Date</th><th class="text-left py-3 px-2 font-medium text-gray-600">Student</th><th class="text-left py-3 px-2 font-medium text-gray-600">Book</th><th class="text-left py-3 px-2 font-medium text-gray-600">Status</th><th class="text-left py-3 px-2 font-medium text-gray-600">Condition</th><th class="text-left py-3 px-2 font-medium text-gray-600">Fees</th></tr></thead>
<tbody>
@forelse($transactions as $txn)
<tr class="border-b border-gray-100">
<td class="py-2 px-2 text-xs text-gray-500">{{ $txn->borrow_date?->format('M d, Y') }}</td>
<td class="py-2 px-2 text-gray-900">{{ $txn->student->first_name ?? '' }} {{ $txn->student->last_name ?? '' }}</td>
<td class="py-2 px-2 text-gray-600">{{ $txn->book_title }}</td>
<td class="py-2 px-2"><span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $txn->status === 'Returned' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">{{ $txn->status }}</span></td>
<td class="py-2 px-2 text-xs">{{ $txn->condition_at_return ?? $txn->condition_at_borrow ?? '—' }}</td>
<td class="py-2 px-2 text-xs">₱{{ number_format($txn->total_fees ?? 0, 2) }}</td>
</tr>
@empty
<tr><td colspan="6" class="py-6 text-center text-gray-400">No history yet.</td></tr>
@endforelse
</tbody>
</table>
</div>
<div class="p-4">{{ $transactions->links() }}</div>
