@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('cashier.dashboard') }}" style="color: var(--muted);">Cashier Dashboard</a>
    <span class="opacity-40">/</span>
    <span class="current">Financial View — {{ $student->first_name }} {{ $student->last_name }}</span>
@endsection

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">Student Financial Record</h2>
    <p class="text-gray-600 mt-1">{{ $student->first_name }} {{ $student->last_name }} &middot; {{ $student->student_number }}</p>
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
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-semibold text-gray-900 mb-4">Student Info</h3>
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <dt class="text-gray-500">Full Name</dt>
                    <dd class="font-medium text-gray-900">{{ $student->first_name }} {{ $student->last_name }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Student No.</dt>
                    <dd class="font-medium text-gray-900">{{ $student->student_number }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Grade Level</dt>
                    <dd class="font-medium text-gray-900">{{ $enrollment?->section?->grade_level ?? 'N/A' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Section</dt>
                    <dd class="font-medium text-gray-900">{{ $enrollment?->section?->section_name ?? 'N/A' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">School Year</dt>
                    <dd class="font-medium text-gray-900">{{ $enrollment?->school_year ?? 'N/A' }}</dd>
                </div>
                <div class="flex justify-between items-center">
                    <dt class="text-gray-500">Payment Plan</dt>
                    <dd class="font-medium">
                        @if($student->ledger?->payment_plan)
                            @php $isLocked = $student->ledger->payments->isNotEmpty(); @endphp
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium {{ $student->ledger->payment_plan === 'full' ? 'bg-blue-100 text-blue-700' : 'bg-orange-100 text-orange-700' }}">
                                @if($isLocked)
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/></svg>
                                @endif
                                {{ $student->ledger->payment_plan === 'full' ? 'Full Payment' : 'Installment' }}
                            </span>
                        @else
                            <span class="text-gray-400">Not set</span>
                        @endif
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Clearance</dt>
                    <dd class="font-medium">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $student->ledger?->clearance_status === 'Cleared' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                            {{ $student->ledger?->clearance_status ?? 'Pending' }}
                        </span>
                    </dd>
                </div>
            </dl>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-semibold text-gray-900 mb-4">Fee Summary</h3>
            <div class="space-y-3 text-sm">
                @php $isSHS = in_array($enrollment?->section?->grade_level, ['Grade 11', 'Grade 12']); @endphp
                @if($isSHS)
                    @foreach($feeSchedules as $fs)
                    <div class="bg-gray-50 rounded-lg p-3">
                        <div class="flex justify-between items-center mb-2">
                            <span class="font-medium text-gray-800">{{ $fs->term }}</span>
                            <span class="font-semibold text-gray-900">₱ {{ number_format($fs->tuition_fee + $fs->misc_fee, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-xs text-gray-500">
                            <span>Tuition: ₱ {{ number_format($fs->tuition_fee, 2) }}</span>
                            <span>Misc: ₱ {{ number_format($fs->misc_fee, 2) }}</span>
                        </div>
                    </div>
                    @endforeach
                @else
                    <div class="bg-gray-50 rounded-lg p-3">
                        <div class="flex justify-between items-center mb-2">
                            <span class="font-medium text-gray-800">{{ $enrollment->school_year }} (Full Year)</span>
                            <span class="font-semibold text-gray-900">₱ {{ number_format($feeSchedules->sum('tuition_fee') + $feeSchedules->sum('misc_fee'), 2) }}</span>
                        </div>
                        <div class="flex justify-between text-xs text-gray-500">
                            <span>Tuition: ₱ {{ number_format($feeSchedules->sum('tuition_fee'), 2) }}</span>
                            <span>Misc: ₱ {{ number_format($feeSchedules->sum('misc_fee'), 2) }}</span>
                        </div>
                    </div>
                @endif

                @if($student->ledger)
                <div class="flex justify-between py-2 border-t border-gray-200 font-semibold">
                    <span class="text-gray-800">Total Assessed</span>
                    <span class="text-gray-900">₱ {{ number_format($student->ledger->total_assessed, 2) }}</span>
                </div>

                @if($student->ledger->discount_applied > 0)
                <div class="bg-blue-50 rounded-lg p-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-blue-700">
                            Discount
                            @if($student->ledger->discount_type)
                            <span class="text-xs">({{ ucfirst($student->ledger->discount_type) }})</span>
                            @endif
                            @if($student->ledger->total_assessed > 0)
                            <span class="text-xs text-blue-500">{{ round($student->ledger->discount_applied / $student->ledger->total_assessed * 100) }}%</span>
                            @endif
                        </span>
                        <span class="font-semibold text-blue-700">-₱ {{ number_format($student->ledger->discount_applied, 2) }}</span>
                    </div>
                </div>
                @endif

                <div class="flex justify-between py-2">
                    <span class="text-gray-600">Total Paid</span>
                    <span class="font-medium text-green-600">₱ {{ number_format($student->ledger->total_paid, 2) }}</span>
                </div>
                <div class="flex justify-between py-2 border-t border-gray-200 font-bold">
                    <span class="text-gray-800">Balance</span>
                    <span class="{{ $student->ledger->balance > 0 ? 'text-red-600' : 'text-green-600' }}">
                        ₱ {{ number_format($student->ledger->balance, 2) }}
                    </span>
                </div>
                @else
                <div class="text-center py-3 text-gray-400 text-xs">No ledger record yet.</div>
                @endif
            </div>
        </div>
    </div>

    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-900">Payment History</h3>
                @if(!(($student->ledger?->balance ?? 0) <= 0 && $student->ledger?->payment_plan === 'full' && $student->ledger?->total_paid > 0))
                <a href="{{ route('cashier.payment', $student) }}" class="px-4 py-2 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);">Process Payment</a>
                @endif
            </div>

            @if($payments->isEmpty())
                <p class="text-sm text-gray-500 text-center py-4">No payments recorded.</p>
            @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="text-left px-4 py-2 font-semibold text-gray-600">Date</th>
                            <th class="text-left px-4 py-2 font-semibold text-gray-600">Amount</th>
                            <th class="text-left px-4 py-2 font-semibold text-gray-600">Receipt No.</th>
                            <th class="text-left px-4 py-2 font-semibold text-gray-600">AR No.</th>
                            <th class="text-left px-4 py-2 font-semibold text-gray-600">Cashier</th>
                            <th class="text-left px-4 py-2 font-semibold text-gray-600">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($payments as $p)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 text-gray-900">{{ $p->payment_date->format('M d, Y') }}</td>
                            <td class="px-4 py-2 font-medium text-gray-900">₱ {{ number_format($p->amount_paid, 2) }}</td>
                            <td class="px-4 py-2 text-gray-600 font-mono text-xs">{{ $p->receipt_number }}</td>
                            <td class="px-4 py-2 text-gray-600 font-mono text-xs">{{ $p->ar_number ?? '—' }}</td>
                            <td class="px-4 py-2 text-gray-500 text-xs">{{ $p->cashier?->name }}</td>
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
@endsection
