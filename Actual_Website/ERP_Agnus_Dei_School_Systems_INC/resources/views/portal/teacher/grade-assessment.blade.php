@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('teacher.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <span class="current">Grade Assessment</span>
@endsection

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">Grade Assessment</h2>
    <p class="text-gray-600 mt-1">Enter scores for Written Work, Quiz, Seatwork, and Exam per student.</p>
</div>

<div x-data="ajaxTable('{{ route('teacher.grade-assessment') }}', { class_id: '{{ request('class_id') }}', grading_period: '{{ request('grading_period', '1st Term') }}' })">
    <div x-show="loading" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-3">
        <div class="skelly sk-line-md w-48 mb-4"></div>
        <template x-for="i in 4" :key="i">
            <div class="skelly sk-card">
                <div class="flex items-center justify-between px-2 py-3">
                    <div class="skelly sk-line-md w-48"></div>
                    <div class="skelly sk-line-sm w-16"></div>
                </div>
            </div>
        </template>
    </div>
    <div x-show="!loading" x-cloak x-html="html" class="fade-in"></div>
</div>
@endsection
