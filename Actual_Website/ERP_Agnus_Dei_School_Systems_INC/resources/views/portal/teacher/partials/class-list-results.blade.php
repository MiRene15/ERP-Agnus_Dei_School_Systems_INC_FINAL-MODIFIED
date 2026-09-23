@if($classes->isEmpty())
<div class="p-8 text-center text-sm text-gray-500 dark:text-[#8A90B0]">No classes found.</div>
@else
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    @foreach($classes as $class)
    <a href="{{ route('teacher.class-list.students', $class) }}"
       class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-5 hover:shadow-md dark:hover:shadow-lg transition block">
        <div class="mb-3">
            <h3 class="font-bold text-gray-900 dark:text-[#E8EAF6]">{{ $class->subject->name ?? 'N/A' }}</h3>
            <p class="text-xs text-gray-500 dark:text-[#8A90B0]">{{ $class->subject->subject_code ?? '' }}</p>
        </div>
        <div class="text-sm text-gray-600 dark:text-[#C1C4DC] space-y-1 mb-3">
            <p><span class="font-medium">Grade/Section:</span> {{ $class->grade_level }} - {{ $class->section }}</p>
            <p><span class="font-medium">Students:</span> {{ $class->enrollments->where('status', 'Active')->count() }}</p>
        </div>
        <span class="text-xs font-semibold text-blue-600">View Master List &rarr;</span>
    </a>
    @endforeach
</div>
@endif
