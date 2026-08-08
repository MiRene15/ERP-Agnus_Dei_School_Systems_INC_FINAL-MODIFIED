@php
    $categoryColors = [
        'Core'           => 'bg-blue-100 text-blue-800',
        'Contextualized'  => 'bg-green-100 text-green-800',
        'Specialized'     => 'bg-purple-100 text-purple-800',
        'TVL'             => 'bg-orange-100 text-orange-800',
    ];
@endphp

@foreach($gradeLevels as $gl)
    @php $glSubjects = $subjects->get($gl, collect()); @endphp
    @if($glSubjects->isEmpty())
        @continue
    @endif
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-4" x-data="{ open: true }">
        <button @click="open = !open" class="w-full flex items-center justify-between p-4 text-left hover:bg-gray-50 rounded-t-xl transition cursor-pointer">
            <div class="flex items-center gap-3">
                <h3 class="font-semibold text-gray-900">{{ $gl }}</h3>
                <span class="text-xs font-medium text-gray-500 bg-gray-100 px-2 py-0.5 rounded-full">{{ $glSubjects->count() }} {{ Str::plural('subject', $glSubjects->count()) }}</span>
            </div>
            <svg class="w-4 h-4 text-gray-500 transition-transform duration-200" :class="{ 'rotate-180': open }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-1" x-cloak style="display: none;">
            <div class="px-4 pb-4">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="text-left py-2 px-2 font-medium text-gray-500 text-xs uppercase">Code</th>
                                <th class="text-left py-2 px-2 font-medium text-gray-500 text-xs uppercase">Name</th>
                                <th class="text-left py-2 px-2 font-medium text-gray-500 text-xs uppercase">Category</th>
                                <th class="text-right py-2 px-2 font-medium text-gray-500 text-xs uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($glSubjects as $subject)
                            <tr class="border-b border-gray-50 hover:bg-gray-50 transition">
                                <td class="py-2.5 px-2 font-mono text-xs text-gray-700">{{ $subject->subject_code }}</td>
                                <td class="py-2.5 px-2 font-medium text-gray-900">{{ $subject->name }}</td>
                                <td class="py-2.5 px-2">
                                    @if($subject->category)
                                        <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium {{ $categoryColors[$subject->category] ?? 'bg-gray-100 text-gray-700' }}">{{ $subject->category }}</span>
                                    @else
                                        <span class="text-xs text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="py-2.5 px-2 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('admin.subjects.edit', $subject) }}" class="px-2 py-1 text-xs font-medium text-gray-600 hover:text-gray-800">Edit</a>
                                        <form method="POST" action="{{ route('admin.subjects.destroy', $subject) }}" onsubmit="return confirm('Delete {{ $subject->subject_code }}?')" class="inline">@csrf @method('DELETE')<button type="submit" class="px-2 py-1 text-xs font-medium text-red-600 hover:text-red-800">Delete</button></form>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endforeach

@empty($subjects)
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 text-center">
        <p class="text-sm text-gray-500 py-4">No subjects yet. <a href="{{ route('admin.subjects.create') }}" class="text-blue-600 font-medium">Add one</a>.</p>
    </div>
@endempty
