@extends('portal.layouts.app')
@section('breadcrumbs')
    <a href="{{ route('librarian.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a><span class="opacity-40"> / </span><span class="current">History</span>
@endsection
@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">Borrowing History</h2>
    <p class="text-gray-600 mt-1">Full history of all book transactions.</p>
</div>
<div x-data="ajaxTable('{{ route('librarian.history') }}', { search: '', status: 'All' })">
    <div class="mb-4 flex gap-2 flex-wrap items-center">
        <input type="text" x-model="filters.search" @input.debounce.300ms="reload()" placeholder="Search by student or book..." class="flex-1 min-w-[200px] rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
        <select x-model="filters.status" @change="reload()" class="rounded-lg border border-gray-300 px-3 py-2 text-sm"><option value="All">All Status</option><option value="Borrowed">Borrowed</option><option value="Returned">Returned</option></select>
        <button type="button" @click="reload()" class="px-4 py-2 rounded-lg text-sm font-semibold text-white" style="background: var(--navy);">Filter</button>
        <button type="button" @click="reset()" class="px-4 py-2 rounded-lg text-sm font-semibold bg-gray-100 hover:bg-gray-200">Clear</button>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div x-show="loading" class="p-4 space-y-3"><template x-for="i in 5" :key="i"><div class="skelly sk-card"></div></template></div>
        <div x-show="!loading" x-cloak x-ref="results" x-html="html" class="fade-in" @click="handlePaginationClick($event)"></div>
    </div>
</div>
@endsection
