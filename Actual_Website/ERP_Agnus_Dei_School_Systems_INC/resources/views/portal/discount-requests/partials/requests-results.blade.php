<div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-gray-200 dark:border-[#2A2F58]">
                <th class="text-left py-2 px-2 font-medium text-gray-600 dark:text-[#8A90B0]">Student</th>
                <th class="text-left py-2 px-2 font-medium text-gray-600 dark:text-[#8A90B0]">Type / Amount</th>
                <th class="text-left py-2 px-2 font-medium text-gray-600 dark:text-[#8A90B0]">Proof</th>
                <th class="text-left py-2 px-2 font-medium text-gray-600 dark:text-[#8A90B0]">Requested by</th>
                <th class="text-left py-2 px-2 font-medium text-gray-600 dark:text-[#8A90B0]">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($requests as $req)
            <tr class="border-b border-gray-50 dark:border-[#2A2F58]">
                <td class="py-2 px-2 font-medium text-gray-900 dark:text-[#E8EAF6]">{{ $req->ledger->student->first_name }} {{ $req->ledger->student->last_name }}</td>
                <td class="py-2 px-2 text-gray-600 dark:text-[#C1C4DC]">{{ $discountTypes[$req->discount_type] ?? $req->discount_type }} — ₱{{ number_format($req->discount_amount, 2) }}</td>
                <td class="py-2 px-2 text-gray-600 dark:text-[#C1C4DC] text-xs max-w-xs">{{ $req->proof_details }}</td>
                <td class="py-2 px-2 text-xs text-gray-500">{{ $req->requester?->name ?? '—' }}</td>
                <td class="py-2 px-2">
                    @php $badge = ['pending' => 'bg-amber-100 text-amber-700', 'approved' => 'bg-blue-100 text-blue-700', 'rejected' => 'bg-red-100 text-red-700', 'applied' => 'bg-green-100 text-green-700'][$req->status] ?? 'bg-gray-100 text-gray-600'; @endphp
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $badge }}">{{ ucfirst($req->status) }}</span>
                </td>
            </tr>
            @empty
            <tr><td colspan="5" class="py-6 text-center text-gray-500 text-sm">No requests match your search.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
