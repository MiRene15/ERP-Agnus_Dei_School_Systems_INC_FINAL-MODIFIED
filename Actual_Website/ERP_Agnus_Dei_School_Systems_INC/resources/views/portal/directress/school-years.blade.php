@extends('portal.layouts.app')
@section('breadcrumbs')
    <a href="{{ route('directress.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a><span class="opacity-40"> / </span><span class="current">School Years</span>
@endsection
@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">School Years</h2>
    <p class="text-gray-600 mt-1">Manage academic years. Adding a year makes it available for fees and enrollments.</p>
</div>
@if(session('success'))<div class="mb-4 p-3 bg-green-50 border border-green-200 rounded text-sm text-green-700">{{ session('success') }}</div>@endif
@if($errors->any())<div class="mb-4 p-3 bg-red-50 border border-red-200 rounded text-sm text-red-700">{{ $errors->first() }}</div>@endif

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 max-w-lg mb-6">
    <form method="POST" action="{{ route('directress.school-years.store') }}">
        @csrf
        <label class="block text-sm font-medium text-gray-700 mb-1">New School Year (YYYY-YYYY)</label>
        <div class="flex gap-2">
            <input type="text" name="school_year" placeholder="e.g. 2027-2028" pattern="\d{4}-\d{4}" required class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <button type="submit" class="px-5 py-2 rounded-lg text-sm font-semibold text-white" style="background: var(--navy);">Add</button>
        </div>
    </form>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <table class="w-full text-sm">
        <thead><tr class="border-b bg-gray-50"><th class="text-left py-3 px-4 font-medium text-gray-600">School Year</th><th class="text-right py-3 px-4 font-medium text-gray-600">Fee Schedules</th></tr></thead>
        <tbody>
            @forelse($years as $row)
            <tr class="border-b border-gray-50"><td class="py-3 px-4 font-medium">{{ $row['year'] }}</td><td class="text-right py-3 px-4">{{ $row['count'] }}</td></tr>
            @empty
            <tr><td colspan="2" class="py-6 text-center text-gray-400">No school years yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
