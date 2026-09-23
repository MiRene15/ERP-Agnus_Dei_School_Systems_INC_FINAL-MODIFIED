@extends('portal.layouts.app')
@section('breadcrumbs')
    <a href="{{ route('directress.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a><span class="opacity-40"> / </span><span class="current">Library Reports</span>
@endsection
@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">Library Reports</h2>
    <p class="text-gray-600 mt-1">Overview of library operations and book statistics.</p>
</div>
<div x-data="ajaxTable('{{ route('directress.library-reports') }}')">
    <div x-show="loading" class="p-4 space-y-3">
        <template x-for="i in 3" :key="i">
            <div class="skelly sk-card"><div class="skelly sk-line-md"></div></div>
        </template>
    </div>
    <div x-show="!loading" x-cloak x-ref="results" x-html="html" class="fade-in"></div>
</div>
@endsection
