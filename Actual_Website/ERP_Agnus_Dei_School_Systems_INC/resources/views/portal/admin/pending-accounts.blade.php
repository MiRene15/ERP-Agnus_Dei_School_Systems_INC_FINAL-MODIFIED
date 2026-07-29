@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('admin.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <span class="current">Pending IT Confirmation</span>
@endsection

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">Pending IT Confirmation</h2>
    <p class="text-gray-600 mt-1">Confirm student accounts after payment clearance to activate portal access.</p>
</div>

@if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm">{{ session('error') }}</div>
@endif

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    @if($pendingConfirmations->isEmpty())
        <p class="text-sm text-gray-500 text-center py-4">No pending IT confirmations. All cleared students have been confirmed.</p>
    @else
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Student</th>
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Student No.</th>
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Grade / Section</th>
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Payment Plan</th>
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Total Paid</th>
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pendingConfirmations as $ledger)
                <tr class="border-b border-gray-100">
                    <td class="py-3 px-2">
                        <span class="font-medium text-gray-900">{{ $ledger->student->first_name }} {{ $ledger->student->last_name }}</span>
                    </td>
                    <td class="py-3 px-2 text-gray-700">{{ $ledger->student->student_number }}</td>
                    <td class="py-3 px-2 text-gray-700">
                        {{ $ledger->student->enrollments->first()?->section?->grade_level ?? 'N/A' }} -
                        {{ $ledger->student->enrollments->first()?->section?->section_name ?? '' }}
                    </td>
                    <td class="py-3 px-2">
                        <span class="text-xs font-medium px-2 py-0.5 rounded-full {{ $ledger->payment_plan === 'full' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700' }}">
                            {{ ucfirst($ledger->payment_plan) }}
                        </span>
                    </td>
                    <td class="py-3 px-2 text-gray-700">₱ {{ number_format($ledger->total_paid, 2) }}</td>
                    <td class="py-3 px-2">
                        <form method="POST" action="{{ route('admin.confirm-account', $ledger) }}">
                            @csrf
                            <button type="submit"
                                    class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium text-white transition cursor-pointer"
                                    style="background: var(--navy);" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                                Confirm Account
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
@endsection
