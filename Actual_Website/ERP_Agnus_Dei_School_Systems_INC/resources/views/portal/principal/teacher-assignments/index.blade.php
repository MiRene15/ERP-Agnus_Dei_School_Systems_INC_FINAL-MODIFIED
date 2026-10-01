@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('principal.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <span class="current">Teacher Assignments</span>
@endsection

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">Teacher Assignments</h2>
    <p class="text-gray-600 mt-1">You own who teaches what. Assigning a teacher here also powers schedule conflict detection for their classes.</p>
</div>

@if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm">{{ session('error') }}</div>
@endif

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6">
    <form method="GET" class="flex items-center gap-3 flex-wrap">
        <label class="text-sm font-medium text-gray-700">Grade Level:</label>
        <select name="grade_level" onchange="this.form.submit()" class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
            <option value="All" {{ $selectedGrade === 'All' ? 'selected' : '' }}>All Grades</option>
            @foreach($gradeLevels as $gl)
            <option value="{{ $gl }}" {{ $selectedGrade === $gl ? 'selected' : '' }}>{{ $gl }}</option>
            @endforeach
        </select>
    </form>
</div>

@if($classes->isEmpty())
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 text-center">
    <p class="text-sm text-gray-500 py-4">No active classes found.</p>
</div>
@else
@foreach($classes as $gradeLevel => $gradeClasses)
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-5">
    <h3 class="font-semibold text-gray-900 mb-4">{{ $gradeLevel }} <span class="text-sm font-normal text-gray-500">({{ $gradeClasses->count() }} class(es))</span></h3>
    <div class="overflow-x-auto">
        <table class="w-full min-w-[720px] text-sm">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="text-left py-2 px-2 font-medium text-gray-600">Section</th>
                    <th class="text-left py-2 px-2 font-medium text-gray-600">Subject</th>
                    <th class="text-left py-2 px-2 font-medium text-gray-600">Current Teacher</th>
                    <th class="text-left py-2 px-2 font-medium text-gray-600 w-64">Assign</th>
                </tr>
            </thead>
            <tbody>
                @foreach($gradeClasses as $class)
                <tr class="border-b border-gray-50 hover:bg-gray-50">
                    <td class="py-2 px-2 text-gray-700">{{ $class->section }}</td>
                    <td class="py-2 px-2 font-medium text-gray-900">{{ $class->subject->name ?? $class->subject_code ?? '—' }}</td>
                    <td class="py-2 px-2">
                        @if($class->teacher)
                            <span class="text-gray-900">{{ $class->teacher->name }}</span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">Unassigned</span>
                        @endif
                    </td>
                    <td class="py-2 px-2">
                        <form method="POST" action="{{ route('principal.teacher-assignments.assign', $class) }}" class="flex gap-2">
                            @csrf @method('PATCH')
                            <select name="teacher_id" class="flex-1 rounded-lg border border-gray-300 px-2 py-1.5 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                                <option value="">— Unassigned —</option>
                                @foreach($teachers as $teacher)
                                <option value="{{ $teacher->id }}" {{ (int) $class->teacher_id === (int) $teacher->id ? 'selected' : '' }}>{{ $teacher->name }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="px-3 py-1.5 text-xs font-semibold text-white transition rounded-lg" style="background: var(--navy);" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">Save</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endforeach
@endif
@endsection
