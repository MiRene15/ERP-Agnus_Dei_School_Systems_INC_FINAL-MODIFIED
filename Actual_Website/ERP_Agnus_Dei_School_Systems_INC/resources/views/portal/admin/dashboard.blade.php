@extends('portal.layouts.app')

@section('breadcrumbs')
    <span class="current">Admin Dashboard</span>
@endsection

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">Administrator Overview</h2>
    <p class="text-gray-600 mt-1">Manage users, system configurations, and school-wide settings.</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center text-blue-600 mb-4">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            <span class="ml-2 font-semibold">Total Users</span>
        </div>
        <div class="text-3xl font-bold text-gray-900">{{ number_format($totalUsers) }}</div>
        <div class="text-sm text-gray-500 mt-2 font-medium">Registered accounts</div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center text-purple-600 mb-4">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            <span class="ml-2 font-semibold">Active Departments</span>
        </div>
        <div class="text-3xl font-bold text-gray-900">{{ $activeRoles }}</div>
        <div class="text-sm text-gray-500 mt-2 font-medium">System roles active</div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center text-orange-600 mb-4">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            <span class="ml-2 font-semibold">IT Confirmations</span>
        </div>
        <div class="text-3xl font-bold text-gray-900">{{ $pendingConfirmations->count() }}</div>
        <div class="mt-2">
            <span class="text-sm text-green-600 font-medium">{{ $confirmedCount }} confirmed</span>
            @if($pendingConfirmations->isNotEmpty())
            <a href="{{ route('admin.pending-accounts') }}" class="text-sm text-blue-600 hover:text-blue-800 font-medium ml-2">View pending &rarr;</a>
            @endif
        </div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <h3 class="text-lg font-bold text-gray-900 mb-4">Recent Activity Logs</h3>
    @if($recentActivity->isEmpty())
        <p class="text-sm text-gray-500 text-center py-4">No recent activity recorded.</p>
    @else
    <div class="space-y-4">
        @foreach($recentActivity as $log)
        <div class="flex items-center justify-between border-b border-gray-50 pb-3">
            <div>
                <p class="font-medium text-gray-800">{{ $log->causer?->name ?? 'System' }} &mdash; {{ $log->event }}</p>
                <p class="text-sm text-gray-500">{{ $log->description }}</p>
            </div>
            <span class="text-sm text-gray-400">{{ $log->created_at->diffForHumans() }}</span>
        </div>
        @endforeach
    </div>
    @endif
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
    <h3 class="font-semibold text-gray-900 mb-4">Exports</h3>
    <div class="flex flex-wrap gap-3">
        <a href="{{ route('admin.exports.enrollments') }}" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg">Enrollments CSV</a>
        <a href="{{ route('admin.exports.grades') }}" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg">Grades CSV</a>
        <a href="{{ route('admin.exports.collections') }}" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg">Collections CSV</a>
    </div>
</div>
@endsection
