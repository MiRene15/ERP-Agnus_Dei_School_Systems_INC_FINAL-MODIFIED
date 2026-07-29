@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('cashier.dashboard') }}" class="no-underline" style="color: var(--muted);">Cashier Dashboard</a>
    <span class="opacity-40">/</span>
    <span class="current">{{ $student->first_name }} {{ $student->last_name }}</span>
@endsection

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">Process Payment</h2>
    <p class="text-gray-600 mt-1">{{ $student->first_name }} {{ $student->last_name }} &middot; {{ $student->student_number }}</p>
</div>

@if(session('error'))
    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm">{{ session('error') }}</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-semibold text-gray-900 mb-4">Student Details</h3>
            <dl class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <dt class="text-gray-500">Full Name</dt>
                    <dd class="font-medium text-gray-900">{{ $student->first_name }} {{ $student->middle_name ? $student->middle_name . ' ' : '' }}{{ $student->last_name }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Student No.</dt>
                    <dd class="font-medium text-gray-900">{{ $student->student_number }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Grade Level</dt>
                    <dd class="font-medium text-gray-900">{{ $enrollment?->section?->grade_level ?? 'N/A' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Section</dt>
                    <dd class="font-medium text-gray-900">{{ $enrollment?->section?->section_name ?? 'N/A' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">School Year</dt>
                    <dd class="font-medium text-gray-900">{{ $enrollment?->school_year ?? 'N/A' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Clearance Status</dt>
                    <dd class="font-medium">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $student?->ledger?->clearance_status === 'Cleared' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                            {{ $student?->ledger?->clearance_status ?? 'Pending' }}
                        </span>
                    </dd>
                </div>
                @if($hasScholarship && $isSHS)
                <div>
                    <dt class="text-gray-500">Scholarship</dt>
                    <dd class="font-medium">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700">Tuition Waived</span>
                    </dd>
                </div>
                @endif
            </dl>
        </div>

        @if($feeSchedules->isNotEmpty())
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-semibold text-gray-900 mb-4">Fee Assessment</h3>
            <div class="space-y-3 text-sm">
                @foreach($feeSchedules as $fs)
                <div class="flex justify-between items-center py-1 border-b border-gray-50">
                    <span class="text-gray-600">{{ $fs->term }} Tuition</span>
                    <span class="font-medium text-gray-900">₱ {{ number_format($fs->tuition_fee, 2) }}</span>
                </div>
                <div class="flex justify-between items-center py-1 border-b border-gray-50">
                    <span class="text-gray-600">{{ $fs->term }} Misc. Fee</span>
                    <span class="font-medium text-gray-900">₱ {{ number_format($fs->misc_fee, 2) }}</span>
                </div>
                @endforeach
                <div class="flex justify-between items-center py-2 border-b border-gray-100 font-semibold">
                    <span class="text-gray-800">Total Tuition</span>
                    <span class="text-gray-900">₱ {{ number_format($totalTuition, 2) }}</span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-gray-100 font-semibold">
                    <span class="text-gray-800">Total Miscellaneous</span>
                    <span class="text-gray-900">₱ {{ number_format($totalMisc, 2) }}</span>
                </div>
                <div class="flex justify-between items-center py-2 text-base font-bold">
                    <span class="text-gray-900">Total Assessed</span>
                    <span class="text-gray-900">₱ {{ number_format($totalAssessed, 2) }}</span>
                </div>
                @if($student->ledger)
                <div class="flex justify-between items-center py-2">
                    <span class="text-gray-600">Total Paid</span>
                    <span class="font-medium text-green-600">₱ {{ number_format($student->ledger->total_paid, 2) }}</span>
                </div>
                @if($student->ledger->discount_applied > 0)
                <div class="flex justify-between items-center py-2">
                    <span class="text-gray-600">Discount Applied</span>
                    <span class="font-medium text-blue-600">-₱ {{ number_format($student->ledger->discount_applied, 2) }}</span>
                </div>
                @endif
                <div class="flex justify-between items-center py-2">
                    <span class="text-gray-600">Remaining Balance</span>
                    <span class="font-medium {{ $student->ledger->balance > 0 ? 'text-red-600' : 'text-green-600' }}">₱ {{ number_format($student->ledger->balance, 2) }}</span>
                </div>
                @endif
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-semibold text-gray-900 mb-4">Discounts</h3>
            <div class="space-y-4" x-data="{ discountType: '{{ $student?->ledger?->discount_applied > 0 ? 'other' : '' }}', totalAssessed: {{ $totalAssessed }}, totalPaid: {{ $student->ledger ? $student->ledger->total_paid : 0 }}, discountAmount: {{ $discountApplied }} }">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Discount Type</label>
                    <select name="discount_type" x-model="discountType"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                        <option value="">None</option>
                        @foreach($discountTypes as $val => $label)
                        <option value="{{ $val }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Discount Amount (₱)</label>
                    <input type="number" name="discount_amount" step="0.01" min="0" x-model="discountAmount"
                           placeholder="0.00"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                    @error('discount_amount') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="text-sm bg-gray-50 rounded-lg p-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Total Assessed</span>
                        <span class="font-medium" x-text="'₱ ' + totalAssessed.toFixed(2)"></span>
                    </div>
                    <div class="flex justify-between mt-1">
                        <span class="text-gray-600">Total Paid</span>
                        <span class="font-medium text-green-600" x-text="'₱ ' + totalPaid.toFixed(2)"></span>
                    </div>
                    <div class="flex justify-between mt-1">
                        <span class="text-gray-600">Discount</span>
                        <span class="font-medium text-blue-600" x-text="'-₱ ' + parseFloat(discountAmount || 0).toFixed(2)"></span>
                    </div>
                    <div class="flex justify-between mt-1 pt-2 border-t border-gray-200 font-bold">
                        <span>New Balance</span>
                        <span :class="(totalAssessed - totalPaid - parseFloat(discountAmount || 0)) > 0 ? 'text-red-600' : 'text-green-600'" x-text="'₱ ' + Math.max(0, totalAssessed - totalPaid - parseFloat(discountAmount || 0)).toFixed(2)"></span>
                    </div>
                </div>
            </div>
        </div>
        @endif

        @if($feeSchedules->isEmpty())
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <p class="text-sm text-gray-500 text-center py-4">No fee schedule found for this grade level and school year.</p>
        </div>
        @endif
    </div>

    <div class="lg:col-span-1">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-semibold text-gray-900 mb-4">Process Payment</h3>
            <form method="POST" action="{{ route('cashier.payment.process', $student) }}">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Payment Plan</label>
                        <select name="payment_plan" required
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                            <option value="">Select plan...</option>
                            <option value="full" {{ $student?->ledger?->payment_plan === 'full' ? 'selected' : '' }}>Full Payment</option>
                            <option value="installment" {{ $student?->ledger?->payment_plan === 'installment' ? 'selected' : '' }}>Installment</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Amount Paid (₱)</label>
                        <input type="number" name="amount_paid" required step="0.01" min="1"
                               placeholder="0.00"
                               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                        @error('amount_paid') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <button type="submit"
                            class="w-full px-4 py-2 rounded-lg text-sm font-semibold text-white transition"
                            style="background: var(--navy);" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                        Process Payment
                    </button>
                </div>
            </form>
        </div>

        @if($student->ledger && $student->ledger->payments->isNotEmpty())
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mt-4">
            <h3 class="font-semibold text-gray-900 mb-3">Payment History</h3>
            <ul class="divide-y divide-gray-100 text-sm">
                @foreach($student->ledger->payments->sortByDesc('created_at') as $payment)
                <li class="py-2 flex justify-between">
                    <div>
                        <p class="font-medium text-gray-900">₱ {{ number_format($payment->amount_paid, 2) }}</p>
                        <p class="text-xs text-gray-500">{{ $payment->payment_date->format('M d, Y') }}</p>
                    </div>
                    <span class="text-xs text-gray-400">{{ $payment->receipt_number }}</span>
                </li>
                @endforeach
            </ul>
        </div>
        @endif
    </div>
</div>
@endsection
