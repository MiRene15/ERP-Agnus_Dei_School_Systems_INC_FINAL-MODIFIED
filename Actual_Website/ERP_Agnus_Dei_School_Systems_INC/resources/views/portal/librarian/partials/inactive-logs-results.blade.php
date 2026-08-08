<table class="w-full text-sm">
    <thead class="bg-gray-50 border-b border-gray-200">
        <tr>
            <th class="text-left px-4 py-3 font-semibold text-gray-600">Title</th>
            <th class="text-left px-4 py-3 font-semibold text-gray-600">Author</th>
            <th class="text-left px-4 py-3 font-semibold text-gray-600">Serial No.</th>
            <th class="text-left px-4 py-3 font-semibold text-gray-600">Reason</th>
            <th class="text-left px-4 py-3 font-semibold text-gray-600">Deactivated</th>
            <th class="text-left px-4 py-3 font-semibold text-gray-600">By</th>
            <th class="text-left px-4 py-3 font-semibold text-gray-600">Actions</th>
        </tr>
    </thead>
    <tbody class="divide-y divide-gray-100">
        @forelse($books as $book)
        <tr class="hover:bg-gray-50">
            <td class="px-4 py-3 font-medium text-gray-900">{{ $book->title }}</td>
            <td class="px-4 py-3 text-gray-600">{{ $book->author }}</td>
            <td class="px-4 py-3 text-gray-600">{{ $book->serial_number ?? '—' }}</td>
            <td class="px-4 py-3 text-gray-600 max-w-[200px] truncate" title="{{ $book->inactive_reason }}">{{ $book->inactive_reason }}</td>
            <td class="px-4 py-3 text-gray-500 text-xs">{{ $book->inactive_at?->format('M d, Y h:i A') }}</td>
            <td class="px-4 py-3 text-gray-500 text-xs">{{ $book->deactivator?->name }}</td>
            <td class="px-4 py-3">
                <form method="POST" action="{{ route('librarian.books.reactivate', $book) }}" class="inline">
                    @csrf @method('PATCH')
                    <button type="submit" class="text-xs font-semibold text-green-700 bg-green-50 hover:bg-green-100 px-3 py-1 rounded-lg transition">Reactivate</button>
                </form>
            </td>
        </tr>
        @empty
        <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">No inactive books found.</td></tr>
        @endforelse
    </tbody>
</table>
<div class="p-4">{{ $books->links() }}</div>
