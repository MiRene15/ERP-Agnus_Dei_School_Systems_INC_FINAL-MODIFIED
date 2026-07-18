@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('librarian.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <span class="current">Library Holdings</span>
@endsection

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Library Holdings</h2>
        <p class="text-gray-600 mt-1">Browse and manage the book collection.</p>
    </div>
    <a href="{{ route('librarian.books.create') }}" class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Add Book
    </a>
</div>

@if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
@endif

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <form method="GET" class="mb-4">
        <div class="flex gap-2 flex-wrap">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by title, author, or ISBN..." class="flex-1 min-w-[200px] rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
            <input type="text" name="publisher" value="{{ request('publisher') }}" placeholder="Publisher..." class="flex-1 min-w-[150px] rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
            <select name="availability" class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                <option value="">All</option>
                <option value="available" {{ request('availability') === 'available' ? 'selected' : '' }}>Available</option>
                <option value="unavailable" {{ request('availability') === 'unavailable' ? 'selected' : '' }}>Unavailable</option>
            </select>
            <button type="submit" class="px-4 py-2 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">Search</button>
            @if(request()->anyFilled(['search', 'publisher', 'availability']))
                <a href="{{ url()->current() }}" class="px-4 py-2 rounded-lg text-sm font-semibold text-gray-700 bg-gray-100">Clear</a>
            @endif
        </div>
    </form>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Title</th>
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Author</th>
                    <th class="text-left py-3 px-2 font-medium text-gray-600">ISBN</th>
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Publisher</th>
                    <th class="text-center py-3 px-2 font-medium text-gray-600">Year</th>
                    <th class="text-right py-3 px-2 font-medium text-gray-600">Price</th>
                    <th class="text-center py-3 px-2 font-medium text-gray-600">Qty</th>
                    <th class="text-center py-3 px-2 font-medium text-gray-600">Available</th>
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($books as $book)
                <tr class="border-b border-gray-100">
                    <td class="py-2 px-2 font-medium text-gray-900">{{ $book->title }}</td>
                    <td class="py-2 px-2 text-gray-600">{{ $book->author }}</td>
                    <td class="py-2 px-2 text-gray-500 text-xs">{{ $book->isbn ?? '—' }}</td>
                    <td class="py-2 px-2 text-gray-600">{{ $book->publisher ?? '—' }}</td>
                    <td class="py-2 px-2 text-center text-gray-600">{{ $book->year_published ?? '—' }}</td>
                    <td class="py-2 px-2 text-right text-gray-700">{{ $book->price ? '₱ ' . number_format($book->price, 2) : '—' }}</td>
                    <td class="py-2 px-2 text-center text-gray-900">{{ $book->quantity }}</td>
                    <td class="py-2 px-2 text-center">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $book->available_quantity > 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                            {{ $book->available_quantity }}
                        </span>
                    </td>
                    <td class="py-2 px-2">
                        <div class="flex gap-1">
                            <a href="{{ route('librarian.books.edit', $book) }}" class="px-2 py-1 text-xs font-medium text-gray-600 hover:text-gray-800">Edit</a>
                            <form method="POST" action="{{ route('librarian.books.destroy', $book) }}" onsubmit="return confirm('Delete this book?')" class="inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="px-2 py-1 text-xs font-medium text-red-600 hover:text-red-800">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="py-6 text-center text-gray-500 text-sm">No books found. <a href="{{ route('librarian.books.create') }}" class="text-blue-600 hover:underline">Add the first book</a>.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $books->links() }}
    </div>
</div>
@endsection