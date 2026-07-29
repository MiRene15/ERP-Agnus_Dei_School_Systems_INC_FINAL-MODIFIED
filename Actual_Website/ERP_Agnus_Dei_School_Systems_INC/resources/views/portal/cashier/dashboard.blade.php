@extends('portal.layouts.app')

@section('breadcrumbs') <span class="current">Cashier Dashboard</span> @endsection

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">Cashier's Office</h2>
    <p class="text-gray-600 mt-1">Manage student ledgers, process tuition fees, and generate receipts.</p>
</div>

@if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
@endif

<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <p class="text-sm text-gray-500 font-medium">Today's Collection</p>
                <p class="text-2xl font-bold text-gray-900">₱ {{ number_format($todayCollection, 2) }}</p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <div>
                <p class="text-sm text-gray-500 font-medium">Receipts Issued Today</p>
                <p class="text-2xl font-bold text-gray-900">{{ $receiptsToday }}</p>
            </div>
        </div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <h3 class="font-semibold text-gray-900 mb-4">Pending Payments</h3>
    @if($pendingPayments->isEmpty())
        <p class="text-sm text-gray-500 text-center py-4">All enrolled students have cleared their payments.</p>
    @else
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Student</th>
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Grade Level</th>
                    <th class="text-left py-3 px-2 font-medium text-gray-600">School Year</th>
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Plan</th>
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Paid</th>
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Balance</th>
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pendingPayments as $s)
                @php $ledger = $s->ledger; @endphp
                <tr class="border-b border-gray-100">
                    <td class="py-3 px-2">
                        <span class="font-medium text-gray-900">{{ $s->first_name }} {{ $s->last_name }}</span>
                        <p class="text-xs text-gray-400">{{ $s->student_number }}</p>
                    </td>
                    <td class="py-3 px-2 text-gray-700">{{ $s->enrollments->first()?->section?->grade_level ?? 'N/A' }}</td>
                    <td class="py-3 px-2 text-gray-700">{{ $s->enrollments->first()?->school_year ?? 'N/A' }}</td>
                    <td class="py-3 px-2">
                        <span class="text-xs font-medium px-2 py-0.5 rounded-full {{ $ledger?->payment_plan === 'full' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700' }}">
                            {{ $ledger ? ucfirst($ledger->payment_plan) : '—' }}
                        </span>
                    </td>
                    <td class="py-3 px-2 text-gray-700">₱ {{ number_format($ledger?->total_paid ?? 0, 2) }}</td>
                    <td class="py-3 px-2">
                        <span class="font-medium {{ ($ledger?->balance ?? 0) > 0 ? 'text-red-600' : 'text-green-600' }}">
                            ₱ {{ number_format($ledger?->balance ?? 0, 2) }}
                        </span>
                    </td>
                    <td class="py-3 px-2">
                        <a href="{{ route('cashier.payment', $s) }}"
                           class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium text-white transition"
                           style="background: var(--navy);" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                            Process Payment
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
@endsection
