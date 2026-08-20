@if($classes->isEmpty())
<div class="p-8 text-center text-sm text-gray-500">No classes found.</div>
@else
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    @foreach($classes as $class)
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 hover:shadow-md transition">
        <div class="flex items-start justify-between mb-3">
            <div>
                <h3 class="font-bold text-gray-900">{{ $class->subject->name ?? 'N/A' }}</h3>
                <p class="text-sm text-gray-500">{{ $class->subject->subject_code ?? '' }}</p>
            </div>
        </div>
        <div class="text-sm text-gray-600 space-y-1 mb-4">
            <p><span class="font-medium">Grade/Section:</span> {{ $class->grade_level }} - {{ $class->section }}</p>
            <p><span class="font-medium">Room:</span> {{ $class->room ?? 'N/A' }}</p>
            <p><span class="font-medium">Schedule:</span> {{ $class->schedules->count() }} session(s)</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('teacher.classes.show', $class) }}" class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);">
                Enter Grades
                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
            <a href="{{ route('teacher.assessments', $class) }}" class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-semibold text-blue-700 bg-blue-50 hover:bg-blue-100 transition">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                Assessments
            </a>
        </div>
    </div>
    @endforeach
</div>
@endif
