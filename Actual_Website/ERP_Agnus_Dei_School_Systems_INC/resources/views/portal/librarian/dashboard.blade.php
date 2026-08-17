@extends('portal.layouts.app')

@section('breadcrumbs')
    <span class="current">Librarian Dashboard</span>
@endsection

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">Library Management</h2>
    <p class="text-gray-600 mt-1">Manage library resources, track borrowings, and view holdings.</p>
</div>

@if(!auth()->user()->has_seen_welcome)
<div x-data="{ show: true }" x-show="show" x-transition class="mb-4 p-5 bg-gradient-to-r from-yellow-50 to-amber-50 border border-yellow-200 rounded-xl">
    <div class="flex items-start gap-4">
        <div class="w-12 h-12 rounded-full bg-yellow-100 flex items-center justify-center flex-shrink-0">
            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div class="flex-1">
            <h3 class="font-bold text-gray-900">Welcome to the Library Dashboard!</h3>
            <p class="text-sm text-gray-600 mt-1">Manage book inventory, track borrowings, and monitor overdue returns. Use the sidebar for all library tools.</p>
            <div class="flex gap-2 mt-3">
                <a href="{{ route('librarian.books.index') }}" class="text-xs font-semibold text-yellow-700 hover:text-yellow-900 underline">Manage Books &rarr;</a>
            </div>
        </div>
        <button @click="show = false; fetch('/dismiss-welcome', {method:'POST', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'}})" class="text-gray-400 hover:text-gray-600 flex-shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
</div>
@endif

<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
            <svg class="w-5 h-5 text-blue-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9.5a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
            Library Overview
        </h3>
        <div class="grid grid-cols-2 gap-4">
            <div class="p-4 bg-blue-50 rounded-lg text-center">
                <p class="text-2xl font-bold text-blue-700">{{ $totalBooks }}</p>
                <p class="text-sm text-gray-600">Total Books</p>
            </div>
            <div class="p-4 bg-green-50 rounded-lg text-center">
                <p class="text-2xl font-bold text-green-700">{{ $availableBooks }}</p>
                <p class="text-sm text-gray-600">Available</p>
            </div>
            <div class="p-4 bg-yellow-50 rounded-lg text-center">
                <p class="text-2xl font-bold text-yellow-700">{{ $borrowedBooks }}</p>
                <p class="text-sm text-gray-600">Borrowed</p>
            </div>
            <div class="p-4 bg-red-50 rounded-lg text-center">
                <p class="text-2xl font-bold text-red-700">{{ $overdueBooks }}</p>
                <p class="text-sm text-gray-600">Overdue</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
            <svg class="w-5 h-5 text-purple-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            Recent Activity
        </h3>
        @if($recentTransactions->isEmpty())
        <div class="p-4 bg-gray-50 rounded-lg text-center">
            <p class="text-gray-500 text-sm">No recent borrowing activity recorded.</p>
        </div>
        @else
        <div class="space-y-2">
            @foreach($recentTransactions as $txn)
            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                <div>
                    <p class="font-medium text-sm text-gray-900">{{ $txn->student->first_name ?? '' }} {{ $txn->student->last_name ?? '' }}</p>
                    <p class="text-xs text-gray-500">{{ $txn->book_title ?? 'N/A' }}</p>
                </div>
                <span class="text-xs {{ $txn->status === 'Returned' ? 'text-green-600' : 'text-yellow-600' }}">{{ $txn->status }}</span>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>
@endsection