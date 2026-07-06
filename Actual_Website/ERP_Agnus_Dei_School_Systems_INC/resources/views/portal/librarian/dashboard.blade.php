@extends('portal.layouts.app')

@section('breadcrumbs')
    <span class="current">Librarian Dashboard</span>
@endsection

@section('sidebar-links')
    <a href="{{ route('librarian.books') }}" class="sidebar-link">
        <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
        <span class="sidebar-label">Library Holdings</span>
    </a>
    <a href="#" class="sidebar-link">
        <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        <span class="sidebar-label">Borrowing Records</span>
    </a>
    <a href="#" class="sidebar-link">
        <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
        <span class="sidebar-label">Overdue Books</span>
    </a>
@endsection

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">Library Management</h2>
    <p class="text-gray-600 mt-1">Manage library resources, track borrowings, and view holdings.</p>
</div>

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