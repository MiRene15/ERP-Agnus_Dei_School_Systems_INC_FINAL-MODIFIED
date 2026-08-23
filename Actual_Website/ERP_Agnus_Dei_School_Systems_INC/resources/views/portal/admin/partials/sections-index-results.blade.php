@forelse($gradeLevels as $gl)
    @php $glSections = $sections->get($gl, collect()); @endphp
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-4">
        <h3 class="font-semibold text-gray-900 mb-3">{{ $gl }} <span class="text-sm font-normal text-gray-500">({{ $glSections->count() }})</span></h3>
        @if($glSections->isEmpty())
            <p class="text-sm text-gray-400">No sections for this grade level.</p>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr><th class="text-left py-3 px-2 font-medium text-gray-600">Section</th><th class="text-left py-3 px-2 font-medium text-gray-600">Adviser</th><th class="text-left py-3 px-2 font-medium text-gray-600">Status</th><th class="text-left py-3 px-2 font-medium text-gray-600">Actions</th></tr></thead>
                <tbody>
                    @foreach($glSections as $section)
                    <tr class="border-b border-gray-100">
                        <td class="py-3 px-2 font-medium text-gray-900">{{ $section->section_name }}</td>
                        <td class="py-3 px-2 text-gray-600 text-xs">{{ $section->adviser?->name ?? '—' }}</td>
                        <td class="py-3 px-2">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $section->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                {{ $section->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="py-3 px-2">
                            <div class="flex gap-1">
                                <a href="{{ route('registrar.sections.edit', $section) }}" class="px-2 py-1 text-xs font-medium text-gray-600 hover:text-gray-800">Edit</a>
                                <form method="POST" action="{{ route('registrar.sections.destroy', $section) }}" onsubmit="return confirm('Delete {{ $section->section_name }}?')" class="inline">@csrf @method('DELETE')<button type="submit" class="px-2 py-1 text-xs font-medium text-red-600 hover:text-red-800">Delete</button></form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
@empty
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 text-center"><p class="text-sm text-gray-500 py-4">No sections yet.</p></div>
@endforelse
