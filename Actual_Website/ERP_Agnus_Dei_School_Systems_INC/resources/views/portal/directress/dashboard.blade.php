@extends('portal.layouts.app')

@section('breadcrumbs')
    <span class="current">Directress Dashboard</span>
@endsection

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">School Directress Dashboard</h2>
    <p class="text-gray-600 mt-1">Manage fees, graduation fees, and teacher profiles.</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <p class="text-sm text-gray-500">Total Teachers</p>
        <p class="text-2xl font-bold text-gray-900 mt-1">{{ $totalTeachers }}</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <p class="text-sm text-gray-500">Active Teachers</p>
        <p class="text-2xl font-bold text-green-600 mt-1">{{ $activeTeachers }}</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <p class="text-sm text-gray-500">Fee Schedules</p>
        <p class="text-2xl font-bold text-gray-900 mt-1">{{ $feeSchedules }}</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <p class="text-sm text-gray-500">Graduation Fees</p>
        <p class="text-2xl font-bold text-gray-900 mt-1">{{ $graduationFees }}</p>
    </div>
</div>
@endsection
