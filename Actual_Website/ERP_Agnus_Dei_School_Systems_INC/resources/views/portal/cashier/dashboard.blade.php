@extends('portal.layouts.app')

@section('breadcrumbs')
    <span class="current">Cashier Dashboard</span>
@endsection

@section('sidebar-links')
    <a href="#" class="sidebar-link">
        <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span class="sidebar-label">Process Payments</span>
    </a>
    <a href="#" class="sidebar-link">
        <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        <span class="sidebar-label">Financial Reports</span>
    </a>
@endsection

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">Cashier's Office</h2>
    <p class="text-gray-600 mt-1">Manage student ledgers, process tuition fees, and generate receipts.</p>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
    <h3 class="text-lg font-bold text-gray-900 mb-4">Quick POS (Point of Sale)</h3>
    <div class="flex gap-4">
        <input type="text" placeholder="Scan or enter Student Number (e.g. 2026-001)" class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
        <button class="bg-blue-600 text-white px-6 py-2 rounded-md font-medium hover:bg-blue-700 transition-colors">Find Account</button>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <p class="text-sm text-gray-500 font-medium mb-1">Today's Collection</p>
        <p class="text-2xl font-bold text-gray-900">₱ 45,200.00</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <p class="text-sm text-gray-500 font-medium mb-1">Receipts Issued</p>
        <p class="text-2xl font-bold text-gray-900">18</p>
    </div>
</div>
@endsection
