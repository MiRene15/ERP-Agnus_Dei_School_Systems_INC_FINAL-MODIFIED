@if($ledgers->isEmpty())
    <p class="text-sm text-gray-500 text-center py-8">No enrolled students found with ledgers.</p>
@else
<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Student</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Grade</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Total Assessed</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Current Discount</th>
                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Action</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @foreach($ledgers as $ledger)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3">
                    <div class="text-sm font-medium text-gray-900">{{ $ledger->student->first_name }} {{ $ledger->student->last_name }}</div>
                    <div class="text-xs text-gray-500">{{ $ledger->student->user->email }}</div>
                </td>
                <td class="px-4 py-3 text-sm text-gray-600">
                    @php
                        $enroll = $ledger->student->enrollments()->where('status','Active')->latest()->first();
                    @endphp
                    {{ $enroll?->section?->grade_level ?? '—' }}
                </td>
                <td class="px-4 py-3 text-sm text-gray-900 text-right font-medium">₱{{ number_format($ledger->total_assessed, 2) }}</td>
                <td class="px-4 py-3">
                    @if($ledger->discount_applied > 0)
                        <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full bg-green-100 text-green-700">
                            {{ $discountTypes[$ledger->discount_type ?? ''] ?? '—' }} — ₱{{ number_format($ledger->discount_applied, 2) }}
                        </span>
                    @else
                        <span class="text-xs text-gray-400">No discount</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-center">
                    <button onclick="openDiscountModal({{ $ledger->id }}, '{{ $ledger->discount_type }}', {{ $ledger->discount_applied }}, {{ $ledger->total_assessed }})"
                            class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">
                        {{ $ledger->discount_applied > 0 ? 'Edit' : 'Grant' }}
                    </button>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
<div class="px-4 py-3 border-t border-gray-100">
    {{ $ledgers->links() }}
</div>
@endif
