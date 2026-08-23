@extends('portal.layouts.app')

@section('breadcrumbs')
    <span class="current">Nurse Dashboard</span>
@endsection

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">Health & Wellness</h2>
    <p class="text-gray-600 mt-1">Track student health records, consultations, and medications.</p>
</div>

<div x-data="ajaxTable('{{ route('nurse.dashboard') }}')">
    <div x-show="loading" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-3">
                <div class="skelly sk-line-md w-32 mb-4"></div>
                <div class="grid grid-cols-2 gap-4">
                    <template x-for="i in 4" :key="i">
                        <div class="bg-gray-50 rounded-lg p-4 text-center">
                            <div class="skelly sk-line-sm w-16 mx-auto mb-2"></div>
                            <div class="skelly sk-line-md w-12 mx-auto"></div>
                        </div>
                    </template>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-3">
                <div class="skelly sk-line-md w-32 mb-4"></div>
                <template x-for="i in 3" :key="i">
                    <div class="skelly sk-card"></div>
                </template>
            </div>
        </div>
    </div>
    <div x-show="!loading" x-cloak x-html="html" class="fade-in"></div>
</div>
@endsection