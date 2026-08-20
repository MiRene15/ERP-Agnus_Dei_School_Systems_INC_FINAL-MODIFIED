@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('cashier.dashboard') }}" style="color: var(--muted);">Cashier Dashboard</a>
    <span class="opacity-40">/</span>
    <span class="current">Financial View — {{ $student->first_name }} {{ $student->last_name }}</span>
@endsection

@section('content')
<div x-data="ajaxTable('{{ route('cashier.student-financial', $student) }}')">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
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
@endsection
