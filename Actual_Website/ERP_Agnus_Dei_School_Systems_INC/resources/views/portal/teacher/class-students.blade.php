@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('teacher.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <a href="{{ route('teacher.class-list') }}" class="no-underline" style="color: var(--muted);">List of Classes</a>
    <span class="opacity-40">/</span>
    <span class="current">{{ $class->subject->name ?? 'Class' }}</span>
@endsection

@section('content')
<div x-data="ajaxTable('{{ route('teacher.class-list.students', $class) }}')">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div x-show="loading" class="p-4 space-y-3">
            <template x-for="i in 5" :key="i">
                <div class="skelly sk-card">
                    <div class="grid grid-cols-5 gap-4 px-2">
                        <div class="skelly sk-line-sm"></div>
                        <div class="skelly sk-line-md col-span-2"></div>
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
