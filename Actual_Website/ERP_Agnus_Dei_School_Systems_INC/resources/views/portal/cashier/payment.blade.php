@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('cashier.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <a href="{{ route('cashier.payments') }}" class="no-underline" style="color: var(--muted);">Process Payments</a>
    <span class="opacity-40">/</span>
    <span class="current">{{ $student->first_name }} {{ $student->last_name }}</span>
@endsection

@section('content')
<div class="mb-6 flex items-start justify-between gap-4">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Process Payment</h2>
        <p class="text-gray-600 mt-1">{{ $student->first_name }} {{ $student->last_name }} &middot; {{ $student->student_number }}</p>
    </div>
    <button type="button" onclick="openFinancialModal({{ $student->id }}, @js($student->first_name . ' ' . $student->last_name))" class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 transition flex-shrink-0">Financial View</button>
</div>

@if(session('error'))
    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm">{{ session('error') }}</div>
@endif

@php
    $isFirstPayment = !$student->ledger || $student->ledger->payments->isEmpty();
    $isPlanLocked = $student->ledger && $student->ledger->payments->isNotEmpty();
    $discountAlreadyApplied = $student->ledger && $student->ledger->discount_applied > 0;
    $effectiveAutoType = $autoDiscountType;
    $effectiveAutoAmount = $autoDiscountAmount;
    if ($discountAlreadyApplied) {
        $effectiveAutoType = $student->ledger->discount_type ?? '';
        $effectiveAutoAmount = $student->ledger->discount_applied;
    }
@endphp

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
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700">Tuition Waived (ESC)</span>
                    </dd>
                </div>
                @elseif($autoDiscountType === 'honor')
                <div>
                    <dt class="text-gray-500">Admission Type</dt>
                    <dd class="font-medium">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-700">Honor — 10% Discount</span>
                    </dd>
                </div>
                @elseif($autoDiscountType === 'sibling')
                <div>
                    <dt class="text-gray-500">Admission Type</dt>
                    <dd class="font-medium">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-700">Sibling — 5% Discount</span>
                    </dd>
                </div>
                @endif
            </dl>
        </div>

        @if($feeSchedules->isNotEmpty())
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-semibold text-gray-900 mb-4">Fee Assessment</h3>
            <div class="space-y-3 text-sm">
                @if($isSHS)
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
                @else
                    <div class="flex justify-between items-center py-1 border-b border-gray-50">
                        <span class="text-gray-600">Tuition (Full Year)</span>
                        <span class="font-medium text-gray-900">₱ {{ number_format($totalTuition, 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center py-1 border-b border-gray-50">
                        <span class="text-gray-600">Misc. Fee (Full Year)</span>
                        <span class="font-medium text-gray-900">₱ {{ number_format($totalMisc, 2) }}</span>
                    </div>
                @endif
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
        @endif

        @if($feeSchedules->isEmpty())
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <p class="text-sm text-gray-500 text-center py-4">No fee schedule found for this grade level and school year.</p>
        </div>
        @endif
    </div>

    <div class="lg:col-span-1">
        @include('portal.cashier.partials.payment-form')
    </div>

        @if($student->ledger && $student->ledger->payments->isNotEmpty())
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mt-4">
            <h3 class="font-semibold text-gray-900 mb-3">Payment History</h3>
            <ul class="divide-y divide-gray-100 text-sm">
                @foreach($student->ledger->payments->sortByDesc('created_at') as $payment)
                <li class="py-2 flex justify-between items-center">
                    <div>
                        <p class="font-medium text-gray-900">₱ {{ number_format($payment->amount_paid, 2) }}</p>
                        <p class="text-xs text-gray-500">{{ $payment->payment_date->format('M d, Y') }}</p>
                        <p class="text-xs text-gray-400">Receipt: {{ $payment->receipt_number }} | AR: {{ $payment->ar_number ?? '—' }}</p>
                    </div>
                    <div class="flex gap-2">
                        @if($payment->receipt_file_path)
                        <a href="{{ asset('storage/' . $payment->receipt_file_path) }}" target="_blank"
                           class="text-xs text-blue-600 hover:text-blue-800 font-medium">View Receipt</a>
                        @endif
                    </div>
                </li>
                @endforeach
            </ul>
        </div>
        @endif
    </div>
</div>

@if(session('payment_success'))
@php $ps = session('payment_success'); @endphp
<div x-data="{ show: true }" x-show="show" x-cloak
     class="fixed inset-0 z-[999] flex items-center justify-center"
     style="background: rgba(0,0,0,0.4); backdrop-filter: blur(4px);">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm mx-4 p-8 text-center" @click.stop>
        <div class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <h3 class="text-xl font-bold text-gray-900 mb-1">Payment Successful!</h3>
        <p class="text-sm text-gray-500 mb-1">₱{{ number_format($ps['amount'], 2) }} received from <strong>{{ $ps['student_name'] }}</strong></p>
        <p class="text-xs text-gray-400 mb-6">Receipt: {{ $ps['receipt_number'] }}</p>
        <div class="flex gap-3">
            <a href="{{ route('cashier.payments') }}"
               class="flex-1 py-2.5 rounded-lg text-sm font-semibold border border-gray-300 text-gray-700 hover:bg-gray-50 transition">
                Done
            </a>
            <a href="{{ route('cashier.receipt.print', $ps['payment_id']) }}" target="_blank"
               class="flex-1 py-2.5 rounded-lg text-sm font-semibold text-white transition hover:opacity-90"
               style="background: var(--navy);">
                Print Receipt
            </a>
        </div>
    </div>
</div>
@endif

@include('portal.cashier.partials.financial-modal')
@include('portal.cashier.partials.payment-modal')
@endsection
