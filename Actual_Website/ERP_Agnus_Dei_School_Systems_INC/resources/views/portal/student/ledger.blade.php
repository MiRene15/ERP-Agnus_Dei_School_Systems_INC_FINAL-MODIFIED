@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('student.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <span class="current">Statement of Account</span>
@endsection

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">Statement of Account</h2>
    <p class="text-gray-600 mt-1">{{ $activeEnrollment->section->grade_level }} - {{ $activeEnrollment->section->section_name }} &middot; {{ $activeEnrollment->school_year }}</p>
</div>

<div x-data="ajaxTable('{{ route('student.ledger') }}')">
    <div x-show="loading" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-3">
            <div class="skelly sk-line-md w-32 mb-4"></div>
            <template x-for="i in 5" :key="i">
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <div class="skelly sk-line-sm w-24"></div>
                    <div class="skelly sk-line-sm w-16"></div>
                </div>
            </template>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-3">
            <div class="skelly sk-line-md w-32 mb-4"></div>
            <template x-for="i in 3" :key="i">
                <div class="skelly sk-card"></div>
            </template>
        </div>
    </div>
    <div x-show="!loading" x-cloak x-html="html" class="fade-in"></div>
</div>
@endsection
