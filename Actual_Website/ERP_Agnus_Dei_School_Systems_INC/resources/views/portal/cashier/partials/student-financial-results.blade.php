<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900 dark:text-[#E8EAF6]">Student Financial Record</h2>
    <p class="text-gray-600 dark:text-[#C1C4DC] mt-1">{{ $student->first_name }} {{ $student->last_name }} &middot; {{ $student->student_number }}</p>
</div>

@if(($student->ledger?->balance ?? 0) <= 0 && $student->ledger?->payment_plan === 'full' && $student->ledger?->total_paid > 0)
<div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg flex items-center gap-3">
    <svg class="w-6 h-6 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    <div>
        <p class="text-sm font-semibold text-green-800">Fully Paid</p>
        <p class="text-xs text-green-600">All fees have been settled. No further payments required.</p>
    </div>
</div>
@endif

@php
    $libraryFees = $libraryFees ?? collect();
    $libraryTotal = $libraryTotal ?? $libraryFees->sum('total_fees');
@endphp

@if(isset($libraryFees) && $libraryFees->isNotEmpty())
<div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-6 mb-6">
    <h3 class="font-semibold text-gray-900 dark:text-[#E8EAF6] mb-4">Library Fees</h3>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead><tr class="border-b border-gray-200 dark:border-[#2A2F58]"><th class="text-left py-2 px-2 font-medium text-gray-600 dark:text-[#8A90B0]">Book</th><th class="text-left py-2 px-2 font-medium text-gray-600 dark:text-[#8A90B0]">Borrowed</th><th class="text-left py-2 px-2 font-medium text-gray-600 dark:text-[#8A90B0]">Returned</th><th class="text-right py-2 px-2 font-medium text-gray-600 dark:text-[#8A90B0]">Fee</th></tr></thead>
            <tbody>
                @foreach($libraryFees as $lf)
                <tr class="border-b border-gray-50 dark:border-[#2A2F58]"><td class="py-2 px-2">{{ $lf->book->title ?? $lf->book_title }}</td><td class="py-2 px-2 text-gray-600 dark:text-[#C1C4DC]">{{ $lf->borrow_date ? \Carbon\Carbon::parse($lf->borrow_date)->format('M d, Y') : '-' }}</td><td class="py-2 px-2 text-gray-600 dark:text-[#C1C4DC]">{{ $lf->actual_return_date ?? $lf->returned_at ?? $lf->return_date ?? 'Not returned' }}</td><td class="py-2 px-2 text-right font-medium text-red-600">₱{{ number_format($lf->total_fees, 2) }}</td></tr>
                @endforeach
                <tr class="font-semibold border-t-2 border-gray-200 dark:border-[#2A2F58]"><td colspan="3" class="py-2 px-2 text-right">Total Library Fees:</td><td class="py-2 px-2 text-right text-red-600">₱{{ number_format($libraryTotal, 2) }}</td></tr>
            </tbody>
        </table>
    </div>
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <div class="lg:col-span-1 space-y-6">
        <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-6">
            <h3 class="font-semibold text-gray-900 dark:text-[#E8EAF6] mb-4">Student Info</h3>
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <dt class="text-gray-500 dark:text-[#8A90B0]">Full Name</dt>
                    <dd class="font-medium text-gray-900 dark:text-[#E8EAF6]">{{ $student->first_name }} {{ $student->last_name }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500 dark:text-[#8A90B0]">Student No.</dt>
                    <dd class="font-medium text-gray-900 dark:text-[#E8EAF6]">{{ $student->student_number }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500 dark:text-[#8A90B0]">Grade Level</dt>
                    <dd class="font-medium text-gray-900 dark:text-[#E8EAF6]">{{ $enrollment?->section?->grade_level ?? 'N/A' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500 dark:text-[#8A90B0]">Section</dt>
                    <dd class="font-medium text-gray-900 dark:text-[#E8EAF6]">{{ $enrollment?->section?->section_name ?? 'N/A' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500 dark:text-[#8A90B0]">School Year</dt>
                    <dd class="font-medium text-gray-900 dark:text-[#E8EAF6]">{{ $enrollment?->school_year ?? 'N/A' }}</dd>
                </div>
                <div class="flex justify-between items-center">
                    <dt class="text-gray-500 dark:text-[#8A90B0]">Payment Plan</dt>
                    <dd class="font-medium">
                        @if($student->ledger?->payment_plan)
                            @php $isLocked = $student->ledger->payments->isNotEmpty(); @endphp
                            <span title="{{ $isLocked ? 'Payment plan locked after first payment' : '' }}" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium {{ $student->ledger->payment_plan === 'full' ? 'bg-blue-100 text-blue-700' : 'bg-orange-100 text-orange-700' }}">
                                @if($isLocked)
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/></svg>
                                @endif
                                {{ $student->ledger->payment_plan === 'full' ? 'Full Payment' : 'Installment' }}
                            </span>
                        @else
                            <span class="text-gray-400 dark:text-[#8A90B0]">Not set</span>
                        @endif
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500 dark:text-[#8A90B0]">Clearance</dt>
                    <dd class="font-medium">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $student->ledger?->clearance_status === 'Cleared' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                            {{ $student->ledger?->clearance_status ?? 'Pending' }}
                        </span>
                    </dd>
                </div>
            </dl>
        </div>
    </div>

    <div class="lg:col-span-2">
        <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-900 dark:text-[#E8EAF6]">Payment History</h3>
                <div class="flex items-center gap-3">
                    @if(!empty($paymentYears) && count($paymentYears) > 1)
                    <select onchange="window.location.href='{{ route('cashier.student-financial', $student) }}?payment_year='+this.value" class="rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-3 py-1.5 text-xs focus:ring-2 focus:ring-blue-500 outline-none">
                        <option value="all" {{ ($selectedYear ?? 'all') === 'all' ? 'selected' : '' }}>All Years</option>
                        @foreach($paymentYears as $year)
                        <option value="{{ $year }}" {{ ($selectedYear ?? 'all') === $year ? 'selected' : '' }}>{{ $year }}</option>
                        @endforeach
                    </select>
                    @endif
                    @if(!(($student->ledger?->balance ?? 0) <= 0 && $student->ledger?->payment_plan === 'full' && $student->ledger?->total_paid > 0))
                    <a href="{{ route('cashier.payment', $student) }}" class="px-4 py-2 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);">Process Payment</a>
                    @endif
                </div>
            </div>

            @if($payments->isEmpty())
                <div class="py-12 text-center">
                    <svg class="w-10 h-10 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    <p class="text-sm font-medium text-gray-500 dark:text-[#8A90B0]">No payments recorded yet.</p>
                    <p class="text-xs text-gray-400 dark:text-[#8A90B0] mt-1">Payment history will appear here once payments are processed.</p>
                </div>
            @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-[#161A33] border-b border-gray-200 dark:border-[#2A2F58]">
                        <tr>
                            <th class="text-left px-4 py-2 font-semibold text-gray-600 dark:text-[#C1C4DC]">Date</th>
                            <th class="text-left px-4 py-2 font-semibold text-gray-600 dark:text-[#C1C4DC]">Amount</th>
                            <th class="text-left px-4 py-2 font-semibold text-gray-600 dark:text-[#C1C4DC]">Receipt No.</th>
                            <th class="text-left px-4 py-2 font-semibold text-gray-600 dark:text-[#C1C4DC]">AR No.</th>
                            <th class="text-left px-4 py-2 font-semibold text-gray-600 dark:text-[#C1C4DC]">Cashier</th>
                            <th class="text-left px-4 py-2 font-semibold text-gray-600 dark:text-[#C1C4DC]">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($payments as $p)
                        <tr class="hover:bg-gray-50 dark:hover:bg-[#161A33]">
                            <td class="px-4 py-2 text-gray-900 dark:text-[#E8EAF6]">{{ $p->payment_date instanceof \Carbon\Carbon ? $p->payment_date->format('M d, Y') : \Carbon\Carbon::parse($p->payment_date)->format('M d, Y') }}</td>
                            <td class="px-4 py-2 font-medium text-gray-900 dark:text-[#E8EAF6]">₱ {{ number_format($p->amount_paid, 2) }}</td>
                            <td class="px-4 py-2 text-gray-600 dark:text-[#C1C4DC] font-mono text-xs">{{ $p->receipt_number }}</td>
                            <td class="px-4 py-2 text-gray-600 dark:text-[#C1C4DC] font-mono text-xs">{{ $p->ar_number ?? '—' }}</td>
                            <td class="px-4 py-2 text-gray-500 dark:text-[#8A90B0] text-xs">{{ $p->cashier?->name }}</td>
                            <td class="px-4 py-2">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('cashier.receipt.print', $p) }}" target="_blank"
                                       class="text-xs font-semibold px-2 py-1 rounded transition" style="background: var(--navy); color: white;">Print</a>
                                    @if($p->receipt_file_path)
                                    <a href="{{ asset('storage/' . $p->receipt_file_path) }}" target="_blank"
                                       class="text-xs text-blue-600 hover:text-blue-800 font-medium">View</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>
</div>

<div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-6 mb-6">
    <h3 class="font-semibold text-gray-900 dark:text-[#E8EAF6] mb-4">Fee Breakdown</h3>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead><tr class="border-b border-gray-200 dark:border-[#2A2F58]"><th class="text-left py-2 px-2 font-medium text-gray-600 dark:text-[#8A90B0]">Item</th><th class="text-right py-2 px-2 font-medium text-gray-600 dark:text-[#8A90B0]">Amount</th></tr></thead>
            <tbody>
                @foreach($feeSchedules as $fs)
                <tr class="border-b border-gray-50 dark:border-[#2A2F58]"><td class="py-2 px-2">Tuition - {{ $fs->term }} ({{ $fs->grade_level }})</td><td class="py-2 px-2 text-right">₱{{ number_format($fs->tuition_fee, 2) }}</td></tr>
                <tr class="border-b border-gray-50 dark:border-[#2A2F58]"><td class="py-2 px-2">Misc - {{ $fs->term }}</td><td class="py-2 px-2 text-right">₱{{ number_format($fs->misc_fee, 2) }}</td></tr>
                @endforeach
                @if(($libraryTotal ?? 0) > 0)
                <tr class="border-b border-gray-50 dark:border-[#2A2F58]"><td class="py-2 px-2">Library Fees</td><td class="py-2 px-2 text-right text-red-600">₱{{ number_format($libraryTotal, 2) }}</td></tr>
                @endif
                @if($student->ledger)
                <tr class="border-b border-gray-50 dark:border-[#2A2F58]"><td class="py-2 px-2">Discount ({{ $student->ledger->discount_type ?? 'None' }})</td><td class="py-2 px-2 text-right text-green-600">-₱{{ number_format($student->ledger->discount_applied ?? 0, 2) }}</td></tr>
                <tr class="border-b border-gray-50 dark:border-[#2A2F58]"><td class="py-2 px-2">Carried Balance</td><td class="py-2 px-2 text-right">₱{{ number_format($student->ledger->carried_over_balance ?? 0, 2) }}</td></tr>
                <tr class="font-semibold border-t-2 border-gray-200 dark:border-[#2A2F58] bg-gray-50 dark:bg-[#161A33]"><td class="py-2 px-2">Total Assessed</td><td class="py-2 px-2 text-right">₱{{ number_format(($student->ledger->total_assessed ?? 0) + ($libraryTotal ?? 0), 2) }}</td></tr>
                <tr><td class="py-2 px-2">Total Paid</td><td class="py-2 px-2 text-right text-green-600">₱{{ number_format($student->ledger->total_paid ?? 0, 2) }}</td></tr>
                <tr class="font-bold text-base border-t border-gray-200 dark:border-[#2A2F58]"><td class="py-2 px-2">Balance</td><td class="py-2 px-2 text-right {{ (($student->ledger->balance ?? 0) + ($libraryTotal ?? 0)) > 0 ? 'text-red-600' : 'text-green-600' }}">₱{{ number_format(($student->ledger->balance ?? 0) + ($libraryTotal ?? 0), 2) }}</td></tr>
                @else
                <tr class="font-semibold border-t-2 border-gray-200 dark:border-[#2A2F58] bg-gray-50 dark:bg-[#161A33]"><td class="py-2 px-2">Total Assessed (excl. ledger)</td><td class="py-2 px-2 text-right">₱{{ number_format($feeSchedules->sum('tuition_fee') + $feeSchedules->sum('misc_fee') + ($libraryTotal ?? 0), 2) }}</td></tr>
                @endif
            </tbody>
        </table>
    </div>
</div>

<div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-6 mb-6">
    <h3 class="font-semibold text-gray-900 dark:text-[#E8EAF6] mb-4">Payments Made</h3>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead><tr class="border-b border-gray-200 dark:border-[#2A2F58]"><th class="text-left py-2 px-2 font-medium text-gray-600 dark:text-[#8A90B0]">Date</th><th class="text-left py-2 px-2 font-medium text-gray-600 dark:text-[#8A90B0]">Receipt #</th><th class="text-left py-2 px-2 font-medium text-gray-600 dark:text-[#8A90B0]">AR #</th><th class="text-right py-2 px-2 font-medium text-gray-600 dark:text-[#8A90B0]">Amount</th></tr></thead>
            <tbody>
                @forelse($payments as $p)
                <tr class="border-b border-gray-50 dark:border-[#2A2F58]"><td class="py-2 px-2">{{ \Carbon\Carbon::parse($p->payment_date)->format('M d, Y') }}</td><td class="py-2 px-2 font-mono text-xs">{{ $p->receipt_number }}</td><td class="py-2 px-2 font-mono text-xs">{{ $p->ar_number ?? '-' }}</td><td class="py-2 px-2 text-right font-medium">₱{{ number_format($p->amount_paid, 2) }}</td></tr>
                @empty
                <tr><td colspan="4" class="py-4 text-center text-gray-400">No payments yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
