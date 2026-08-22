@extends('portal.layouts.app')
@section('breadcrumbs')
    <span class="current">Admission</span>
@endsection
@section('content')
<div class="max-w-lg mx-auto mt-12 bg-white rounded-xl shadow-sm border border-gray-100 p-8 text-center">
    <div class="w-16 h-16 rounded-full bg-amber-100 flex items-center justify-center mx-auto mb-4">
        <svg class="w-8 h-8 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    </div>
    <h2 class="text-xl font-bold text-gray-900">Enrollment is Currently Closed</h2>
    <p class="text-sm text-gray-600 mt-2">The admission period is closed at this time. Please check back later or contact the registrar for assistance.</p>
    <a href="{{ route('student.dashboard') }}" class="inline-block mt-6 px-5 py-2 rounded-lg text-sm font-semibold text-white" style="background: var(--navy);">Back to Dashboard</a>
</div>
@endsection
