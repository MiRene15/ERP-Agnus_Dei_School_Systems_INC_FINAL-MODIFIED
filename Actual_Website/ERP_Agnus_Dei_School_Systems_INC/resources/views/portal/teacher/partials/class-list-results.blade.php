@if($classes->isEmpty())
<div class="p-8 text-center text-sm text-gray-500">No classes found.</div>
@else
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    @foreach($classes as $class)
    <a href="{{ route('teacher.class-list.students', $class) }}"
       class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 hover:shadow-md transition block">
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
@endif
