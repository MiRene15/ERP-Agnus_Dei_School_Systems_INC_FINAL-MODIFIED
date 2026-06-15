@extends('portal.layouts.app')

@section('breadcrumbs')
    <span class="current">Admin Dashboard</span>
@endsection

@section('sidebar-links')
    <a href="{{ route('admin.users.index') }}" class="sidebar-link">
        <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
        <span class="sidebar-label">Staff Accounts</span>
    </a>
    <a href="#" class="sidebar-link">
        <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        <span class="sidebar-label">System Settings</span>
    </a>
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
        <div class="text-3xl font-bold text-gray-900">1,204</div>
        <div class="text-sm text-green-600 mt-2 font-medium">+12 this week</div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center text-purple-600 mb-4">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            <span class="ml-2 font-semibold">Active Departments</span>
        </div>
        <div class="text-3xl font-bold text-gray-900">8</div>
        <div class="text-sm text-gray-500 mt-2 font-medium">All systems online</div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center text-orange-600 mb-4">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <span class="ml-2 font-semibold">System Alerts</span>
        </div>
        <div class="text-3xl font-bold text-gray-900">0</div>
        <div class="text-sm text-green-600 mt-2 font-medium">No critical issues</div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <h3 class="text-lg font-bold text-gray-900 mb-4">Recent Activity Logs</h3>
    <div class="space-y-4">
        <div class="flex items-center justify-between border-b border-gray-50 pb-3">
            <div>
                <p class="font-medium text-gray-800">New teacher account created</p>
                <p class="text-sm text-gray-500">System generated credentials for T-2026-004</p>
            </div>
            <span class="text-sm text-gray-400">10 mins ago</span>
        </div>
        <div class="flex items-center justify-between border-b border-gray-50 pb-3">
            <div>
                <p class="font-medium text-gray-800">Database backup completed</p>
                <p class="text-sm text-gray-500">Automated daily backup successful</p>
            </div>
            <span class="text-sm text-gray-400">2 hours ago</span>
        </div>
    </div>
</div>
@endsection
