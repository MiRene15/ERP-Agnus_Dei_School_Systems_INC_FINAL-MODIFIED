<div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-gray-200">
                <th class="text-left py-3 px-2 font-medium text-gray-600">Title</th>
                <th class="text-left py-3 px-2 font-medium text-gray-600">Type</th>
                <th class="text-left py-3 px-2 font-medium text-gray-600">Date</th>
                <th class="text-left py-3 px-2 font-medium text-gray-600">Status</th>
                <th class="text-left py-3 px-2 font-medium text-gray-600">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($announcements as $a)
            <tr class="border-b border-gray-100">
                <td class="py-2 px-2 font-medium text-gray-900">{{ $a->title }}</td>
                <td class="py-2 px-2">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $a->type === 'event' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }}">
                        {{ ucfirst($a->type) }}
                    </span>
                </td>
                <td class="py-2 px-2 text-gray-600">{{ \Carbon\Carbon::parse($a->date)->format('M d, Y') }}</td>
                <td class="py-2 px-2">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $a->is_published ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                        {{ $a->is_published ? 'Published' : 'Draft' }}
                    </span>
                </td>
                <td class="py-2 px-2">
                    <div class="flex gap-1">
                        <a href="{{ route('principal.announcements.edit', $a) }}" class="px-2 py-1 text-xs font-medium text-gray-600 hover:text-gray-800">Edit</a>
                        <form method="POST" action="{{ route('principal.announcements.destroy', $a) }}" onsubmit="return confirm('Delete this announcement?')" class="inline">
                            @csrf @method('DELETE')
                            <button type="submit" class="px-2 py-1 text-xs font-medium text-red-600 hover:text-red-800">Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="py-6 text-center text-gray-500 text-sm">No announcements yet. <a href="{{ route('principal.announcements.create') }}" class="text-blue-600 hover:underline">Create one</a>.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">
    {{ $announcements->links() }}
</div>
