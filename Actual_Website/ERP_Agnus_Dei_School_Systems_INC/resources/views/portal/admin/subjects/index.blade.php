@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('admin.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <span class="current">Subjects</span>
@endsection

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Subjects</h2>
        <p class="text-gray-600 mt-1">View and manage subjects by grade level.</p>
    </div>
    <a href="{{ route('admin.subjects.create') }}" class="px-4 py-2 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">+ Add Subject</a>
</div>

@if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm">{{ session('error') }}</div>
@endif

<div x-data="ajaxTable('{{ route('admin.subjects.index') }}', { search: '{{ request('search') }}', grade_level: '{{ request('grade_level') }}' })">
    <div class="mb-4 flex gap-2 flex-wrap items-center">
        <form method="GET" class="flex gap-2 flex-1 flex-wrap" @submit.prevent="reload()">
            <input type="text" x-model="filters.search" @input.debounce.300ms="reload()"
                   placeholder="Search by name or code..."
                   class="flex-1 min-w-[200px] rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
            <select x-model="filters.grade_level" @change="reload()" class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                <option value="All">All Grade Levels</option>
                @foreach($gradeLevels as $gl)
                    <option value="{{ $gl }}">{{ $gl }}</option>
                @endforeach
            </select>
            <button type="submit" class="px-4 py-2 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);">Search</button>
            <button type="button" @click="reset()" class="px-4 py-2 rounded-lg text-sm font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 transition">Clear</button>
        </form>
    </div>

    <div class="mb-4 bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <details>
            <summary class="cursor-pointer text-sm font-semibold text-gray-700">Import from CSV (hybrid — manual stays)</summary>
            <div class="mt-3 flex flex-col gap-3">
                <p class="text-xs text-gray-500">CSV: <code>subject_code,name,grade_level,category</code> — category = Core/Contextualized/Specialized/TVL. Duplicates skipped.</p>
                <div class="flex gap-2 items-center flex-wrap">
                    <a href="{{ route('admin.subjects.template') }}" class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold bg-gray-100 text-gray-700 hover:bg-gray-200">Download template</a>
                </div>
                <form method="POST" action="{{ route('admin.subjects.import') }}" enctype="multipart/form-data" class="flex gap-2 items-center flex-wrap">
                    @csrf
                    <input type="file" name="file" accept=".csv,.txt" required class="text-sm border border-gray-300 rounded-lg px-3 py-1.5">
                    <button type="submit" class="px-4 py-1.5 rounded-lg text-sm font-semibold text-white" style="background: var(--navy);">Import CSV</button>
                </form>
                @if(session('import_errors') || session('import_skipped'))
                    <div class="text-xs space-y-1">
                        @if(session('import_errors'))<p class="font-semibold text-red-600">Errors:</p><ul class="list-disc ml-4 text-red-600">@foreach(session('import_errors') as $e)<li>{{ $e }}</li>@endforeach</ul>@endif
                        @if(session('import_skipped'))<p class="font-semibold text-amber-600 mt-2">Skipped:</p><ul class="list-disc ml-4 text-amber-600">@foreach(session('import_skipped') as $s)<li>{{ $s }}</li>@endforeach</ul>@endif
                    </div>
                @endif
            </div>
        </details>
    </div>

    <!-- Skeleton loading -->
    <div x-show="loading" class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 space-y-3">
        <template x-for="i in 5" :key="i">
            <div class="skelly sk-card">
                <div class="grid grid-cols-4 gap-4 px-2">
                    <div class="skelly sk-line-md col-span-2"></div>
                    <div class="skelly sk-line-md"></div>
                    <div class="skelly sk-line-sm"></div>
                    <div class="skelly sk-line-sm"></div>
                </div>
            </div>
        </template>
    </div>

    <!-- Results injected via AJAX -->
    <div x-show="!loading" x-cloak @click="handlePaginationClick($event)" x-ref="results" x-html="html" class="fade-in"></div>
</div>
@endsection
