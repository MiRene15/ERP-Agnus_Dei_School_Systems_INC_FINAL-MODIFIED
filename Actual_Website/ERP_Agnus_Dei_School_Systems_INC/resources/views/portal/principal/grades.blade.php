@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('principal.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <span class="current">Student Grades</span>
@endsection

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">Student Grades</h2>
    <p class="text-gray-600 mt-1">View student grades by grade level.</p>
</div>

<div class="mb-4 flex gap-2 flex-wrap">
    @foreach($gradeLevels as $gl)
    <a href="{{ route('principal.grades', ['grade_level' => $gl]) }}"
       class="px-3 py-1.5 rounded-lg text-sm font-medium transition {{ $selectedGrade === $gl ? 'text-white' : 'text-gray-600 bg-gray-100 hover:bg-gray-200' }}"
       style="{{ $selectedGrade === $gl ? 'background: var(--navy);' : '' }}">
        {{ $gl }}
    </a>
    @endforeach
</div>

<div class="mb-4 flex gap-2 flex-wrap items-center">
    <form method="GET" class="flex gap-2 flex-1 flex-wrap">
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="Search by student name or section..."
               class="flex-1 min-w-[200px] rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
        <button type="submit" class="px-4 py-2 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);">Search</button>
        @if(request()->filled('search'))
            <a href="{{ url()->current() }}" class="px-4 py-2 rounded-lg text-sm font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 transition">Clear</a>
        @endif
    </form>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <h3 class="font-semibold text-gray-900 mb-4">{{ $selectedGrade }} — {{ active_school_year() }}</h3>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Student</th>
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Section</th>
                    @foreach($subjects as $subject)
                    <th class="text-center py-3 px-2 font-medium text-gray-600 text-xs">{{ Str::limit($subject->name, 12) }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($enrollments as $enrollment)
                <tr class="border-b border-gray-100">
                    <td class="py-2 px-2 font-medium text-gray-900">{{ $enrollment->student->first_name }} {{ $enrollment->student->last_name }}</td>
                    <td class="py-2 px-2 text-gray-600">{{ $enrollment->section->section_name ?? '—' }}</td>
                    @foreach($subjects as $subject)
                    @php $class = $enrollment->subjects->firstWhere('subject_id', $subject->id); @endphp
                    @php $grade = $class ? $enrollment->grades->firstWhere('class_id', $class->id) : null; @endphp
                    <td class="py-2 px-2 text-center text-gray-700">
                        {{ $grade ? number_format($grade->final_grade, 1) : '—' }}
                    </td>
                    @endforeach
                </tr>
                @empty
                <tr>
                    <td colspan="{{ 2 + $subjects->count() }}" class="py-6 text-center text-gray-500 text-sm">No students found for {{ $selectedGrade }}.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">
        {{ $enrollments->links() }}
    </div>
</div>
@endsection
