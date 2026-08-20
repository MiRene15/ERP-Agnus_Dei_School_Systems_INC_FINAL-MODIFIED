@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('admin.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <span class="current">Pending Onboarding</span>
@endsection

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">Pending Onboarding</h2>
    <p class="text-gray-600 mt-1">Confirm student accounts after payment clearance to activate portal access.</p>
</div>

@if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm">{{ session('error') }}</div>
@endif

<div x-data="ajaxTable('{{ route('admin.pending-accounts') }}')">
    <div x-show="loading" class="space-y-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-3">
            <div class="skelly sk-line-md w-32 mb-4"></div>
            <template x-for="i in 5" :key="i">
                <div class="skelly sk-card"></div>
            </template>
        </div>
    </div>
    <div x-show="!loading" x-cloak x-html="html" class="fade-in"></div>
</div>
@endsection