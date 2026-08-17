@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('teacher.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <span class="current">List of Classes</span>
@endsection

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">List of Classes</h2>
    <p class="text-gray-600 mt-1">Select a class to view its master list of students.</p>
</div>

@if($classes->isEmpty())
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 text-center">
    <p class="text-sm text-gray-500">No classes assigned to you yet.</p>
</div>
@else
<div x-data="{ search: '', grade: '' }">
    <div class="flex gap-2 mb-4">
        <input type="text" x-model="search" placeholder="Search subject..."
               class="flex-1 px-3 py-1.5 rounded-lg border border-gray-300 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
        <select x-model="grade"
                class="px-3 py-1.5 rounded-lg border border-gray-300 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
            <option value="">All Grades</option>
            @foreach($classes->pluck('grade_level')->unique()->sort() as $gl)
            <option value="{{ $gl }}">{{ $gl }}</option>
            @endforeach
        </select>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($classes as $class)
        <a href="{{ route('teacher.class-list.students', $class) }}"
           class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 hover:shadow-md transition block"
           x-show="(search === '' || '{{ strtolower($class->subject->name ?? '') }}'.includes(search.toLowerCase())) && (grade === '' || '{{ $class->grade_level }}' === grade)">
            <div class="mb-3">
                <h3 class="font-bold text-gray-900">{{ $class->subject->name ?? 'N/A' }}</h3>
                <p class="text-xs text-gray-500">{{ $class->subject->subject_code ?? '' }}</p>
            </div>
            <div class="text-sm text-gray-600 space-y-1 mb-3">
                <p><span class="font-medium">Grade/Section:</span> {{ $class->grade_level }} - {{ $class->section }}</p>
                <p><span class="font-medium">Students:</span> {{ $class->enrollments->where('status', 'Active')->count() }}</p>
            </div>
            <span class="text-xs font-semibold text-blue-600">View Master List &rarr;</span>
        </a>
        @endforeach
    </div>
</div>
@endif
@endsection
