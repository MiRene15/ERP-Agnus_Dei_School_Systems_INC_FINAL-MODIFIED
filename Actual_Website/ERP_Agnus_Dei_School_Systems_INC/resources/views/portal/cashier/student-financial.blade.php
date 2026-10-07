@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('cashier.dashboard') }}" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <a href="{{ route('cashier.payments') }}" style="color: var(--muted);">Process Payments</a>
    <span class="opacity-40">/</span>
    <span class="current">Financial View — {{ $student->first_name }} {{ $student->last_name }}</span>
@endsection

@section('content')
@if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
@endif
<div x-data="ajaxTable('{{ route('cashier.student-financial', $student) }}')">
    <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] overflow-hidden">
        <div x-show="loading" class="p-4 space-y-3">
            <template x-for="i in 4" :key="i">
                <div class="skelly sk-card">
                    <div class="grid grid-cols-3 gap-4 px-2">
                        <div class="skelly sk-line-md col-span-2"></div>
                        <div class="skelly sk-line-md"></div>
                        <div class="skelly sk-line-sm"></div>
                        <div class="skelly sk-line-sm"></div>
                        <div class="skelly sk-line-sm"></div>
                    </div>
                </div>
            </template>
        </div>
        <div x-show="!loading" x-cloak x-ref="results" x-html="html" class="fade-in"></div>
    </div>
</div>

@include('portal.cashier.partials.financial-modal')
@include('portal.cashier.partials.payment-modal')
@endsection
