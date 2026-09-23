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

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
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

        <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-6">
            <h3 class="font-semibold text-gray-900 dark:text-[#E8EAF6] mb-4">Fee Summary</h3>
            <div class="space-y-3 text-sm">
                @foreach($feeSchedules as $fs)
                @php $termTotal = $fs->tuition_fee + $fs->misc_fee; @endphp
                <div class="bg-gray-50 dark:bg-[#161A33] rounded-lg p-3 border border-gray-100 dark:border-[#2A2F58]">
                    <div class="flex justify-between items-center mb-2">
                        <span class="font-medium text-gray-800 dark:text-[#E8EAF6]">{{ $fs->term ?: $enrollment->school_year }}</span>
                        <span class="font-semibold text-gray-900 dark:text-white">₱ {{ number_format($termTotal, 2) }}</span>
                    </div>
                    <div class="space-y-1.5">
                        @if($termTotal > 0)
                        <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                            <div class="h-2 rounded-full bg-blue-500" style="width: {{ round($fs->tuition_fee / $termTotal * 100) }}%"></div>
                        </div>
                        @endif
                        <div class="flex justify-between text-xs text-gray-500 dark:text-[#8A90B0]">
                            <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-blue-500 inline-block"></span> Tuition: ₱ {{ number_format($fs->tuition_fee, 2) }}</span>
                            <span>Misc: ₱ {{ number_format($fs->misc_fee, 2) }}</span>
                        </div>
                    </div>
                    @if(!empty($fs->misc_fee_items))
                        <div class="mt-2 text-xs text-gray-400 dark:text-[#6A7094] border-t border-gray-100 dark:border-[#2A2F58] pt-2">Breakdown: @foreach((is_string($fs->misc_fee_items) ? json_decode($fs->misc_fee_items, true) : $fs->misc_fee_items) as $k => $v) {{ ucfirst($k) }} ₱{{ number_format($v,2) }}@if(!$loop->last) · @endif @endforeach</div>
                    @endif
                </div>
                @endforeach

                @php $libraryFees = \App\Models\LibraryTransaction::where('student_id', $student->id)->where('fees_assessed', true)->sum('total_fees'); @endphp
                @if($libraryFees > 0)
                <div class="flex justify-between py-1 text-xs items-center">
                    <span class="text-gray-500 dark:text-[#8A90B0] flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-orange-400 inline-block"></span> Library Fees</span>
                    <span class="font-medium text-orange-600">₱ {{ number_format($libraryFees, 2) }}</span>
                </div>
                @endif
                @if($student->ledger)
                <div class="flex justify-between py-2 border-t border-gray-200 dark:border-[#2A2F58] font-semibold">
                    <span class="text-gray-800">Total Assessed (incl. Library)</span>
                    <span class="text-gray-900 dark:text-[#E8EAF6]">₱ {{ number_format($student->ledger->total_assessed, 2) }}</span>
                </div>

                @if($student->ledger->discount_applied > 0)
                <div class="bg-blue-50 rounded-lg p-3 border border-blue-100">
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium text-blue-700 flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                            Discount
                            @if($student->ledger->discount_type)
                            <span class="text-xs font-normal">({{ ucfirst($student->ledger->discount_type) }})</span>
                            @endif
                            @if($student->ledger->total_assessed > 0)
                            <span class="text-xs bg-blue-100 px-1.5 py-0.5 rounded-full text-blue-600">{{ round($student->ledger->discount_applied / $student->ledger->total_assessed * 100) }}%</span>
                            @endif
                        </span>
                        <span class="font-semibold text-blue-700">-₱ {{ number_format($student->ledger->discount_applied, 2) }}</span>
                    </div>
                </div>
                @endif

                <div class="flex justify-between py-2">
                    <span class="text-gray-600 dark:text-[#C1C4DC] flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-green-500 inline-block"></span> Total Paid</span>
                    <span class="font-medium text-green-600">₱ {{ number_format($student->ledger->total_paid, 2) }}</span>
                </div>
                <div class="flex justify-between py-2 border-t border-gray-200 dark:border-[#2A2F58] font-bold">
                    <span class="text-gray-800">Balance</span>
                    <span class="{{ $student->ledger->balance > 0 ? 'text-red-600' : 'text-green-600' }}">
                        ₱ {{ number_format($student->ledger->balance, 2) }}
                    </span>
                </div>

                @if($student->ledger->total_assessed > 0)
                <div class="mt-2 pt-2 border-t border-gray-100 dark:border-[#2A2F58]">
                    <div class="flex justify-between text-xs text-gray-500 dark:text-[#8A90B0] mb-1.5">
                        <span>Payment Progress</span>
                        <span class="font-medium {{ $student->ledger->balance <= 0 ? 'text-green-600' : 'text-blue-600' }}">{{ round(($student->ledger->total_paid / $student->ledger->total_assessed) * 100) }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2.5 overflow-hidden">
                        <div class="h-2.5 rounded-full transition-all duration-500 {{ $student->ledger->balance <= 0 ? 'bg-green-500' : 'bg-blue-500' }}" 
                             style="width: {{ min(100, round(($student->ledger->total_paid / $student->ledger->total_assessed) * 100)) }}%"></div>
                    </div>
                </div>
                @endif

                @else
                <div class="text-center py-3 text-gray-400 dark:text-[#8A90B0] text-xs">No ledger record yet.</div>
                @endif
            </div>
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
                            <td class="px-4 py-2 text-gray-900 dark:text-[#E8EAF6]">{{ $p->payment_date->format('M d, Y') }}</td>
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
