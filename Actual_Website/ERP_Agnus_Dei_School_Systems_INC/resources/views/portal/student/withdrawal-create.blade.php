@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('student.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <span class="current">Withdraw</span>
@endsection

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">Request Withdrawal</h2>
    <p class="text-gray-600 mt-1">Submit a request to withdraw from your current enrollment.</p>
</div>

@if($existingRequest)
<div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 mb-4">
    <p class="text-sm text-yellow-800">You already have a pending withdrawal request. Please wait for the registrar to process it.</p>
</div>
@else
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 max-w-lg">
    <div class="mb-4 p-4 bg-gray-50 rounded-lg text-sm">
        <p class="font-medium text-gray-900">{{ $activeEnrollment->section->grade_level }} - {{ $activeEnrollment->section->section_name }}</p>
        <p class="text-gray-500">{{ $activeEnrollment->school_year }}</p>
    </div>
    <form method="POST" action="{{ route('student.withdrawal.store') }}">
        @csrf
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Reason for Withdrawal *</label>
            <textarea name="reason" rows="4" required maxlength="1000"
                      class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                      placeholder="Please explain your reason for withdrawing...">{{ old('reason') }}</textarea>
            @error('reason') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div class="flex items-center gap-2">
            <button type="submit" class="px-5 py-2 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">Submit Request</button>
            <a href="{{ route('student.dashboard') }}" class="px-5 py-2 rounded-lg text-sm font-semibold text-gray-600 bg-gray-100 hover:bg-gray-200 transition">Cancel</a>
        </div>
    </form>
</div>
@endif
@endsection
