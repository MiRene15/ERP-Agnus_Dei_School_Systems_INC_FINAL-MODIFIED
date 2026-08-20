@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('student.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <span class="current">Class Schedule</span>
@endsection

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">Class Schedule</h2>
    <p class="text-gray-600 mt-1">{{ $activeEnrollment->section->grade_level }} - {{ $activeEnrollment->section->section_name }} &middot; {{ $activeEnrollment->school_year }}</p>
</div>

<div x-data="ajaxTable('{{ route('student.schedule') }}')">
    <div x-show="loading" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="grid grid-cols-6 gap-3">
            <template x-for="i in 6" :key="i">
                <div class="space-y-2">
                    <div class="skelly sk-line-sm rounded-lg"></div>
                    <template x-for="j in 4" :key="j">
                        <div class="skelly sk-card"></div>
                    </template>
                </div>
            </template>
        </div>
    </div>
    <div x-show="!loading" x-cloak x-html="html" class="fade-in"></div>
</div>
@endsection
