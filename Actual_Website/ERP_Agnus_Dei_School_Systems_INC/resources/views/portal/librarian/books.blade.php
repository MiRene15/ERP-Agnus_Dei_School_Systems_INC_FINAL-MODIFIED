@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('librarian.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <span class="current">Catalog</span>
@endsection

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-[#E8EAF6]">Catalog</h2>
        <p class="text-gray-600 dark:text-[#C1C4DC] mt-1">Browse and manage the book collection.</p>
    </div>
    <a href="{{ route('librarian.books.create') }}" class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Add Book
    </a>
</div>

@if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
@endif

<div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-6" x-data="booksManager()">
    <!-- Basic Filters -->
    <form @submit.prevent="performSearch()" class="mb-4">
        <div class="flex gap-2 items-center flex-wrap">
            <input type="text" x-model="filters.search" @input.debounce.300ms="performSearch()" placeholder="Search by title, author, or ISBN..." class="flex-1 min-w-[200px] rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
            <select x-model="filters.active" @change="performSearch()" class="rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                <option value="active">Active Only</option>
                <option value="inactive">Inactive Only</option>
                <option value="all">All</option>
            </select>
            <button type="button" @click="showAdvanced = !showAdvanced" class="inline-flex items-center gap-1 px-3 py-2 rounded-lg text-sm font-medium text-gray-600 dark:text-[#C1C4DC] bg-gray-100 hover:bg-gray-200 transition">
                <svg class="w-4 h-4 transition-transform duration-200" :class="showAdvanced ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                Filters
            </button>
            <button type="button" @click="resetFilters()" class="px-3 py-2 rounded-lg text-sm font-medium text-gray-600 dark:text-[#C1C4DC] bg-gray-100 hover:bg-gray-200 transition">Clear</button>
        </div>

        <!-- Advanced Filters (collapsible) -->
        <div x-show="showAdvanced" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2" class="mt-3 pt-3 border-t border-gray-100 dark:border-[#2A2F58]" style="display: none;">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-3">
                <input type="text" x-model="filters.serial_number" @input.debounce.300ms="performSearch()" placeholder="Serial number..." class="rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                <input type="text" x-model="filters.publisher" @input.debounce.300ms="performSearch()" placeholder="Publisher..." class="rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                <select x-model="filters.availability" @change="performSearch()" class="rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                    <option value="">All Availability</option>
                    <option value="available">Available</option>
                    <option value="unavailable">Unavailable</option>
                </select>
                <div></div>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <input type="number" x-model="filters.year_from" @input.debounce.300ms="performSearch()" placeholder="Year from..." min="1900" max="2099" class="rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                <input type="number" x-model="filters.year_to" @input.debounce.300ms="performSearch()" placeholder="Year to..." min="1900" max="2099" class="rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                <input type="number" x-model="filters.price_min" @input.debounce.300ms="performSearch()" placeholder="Min price (₱)..." min="0" step="0.01" class="rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                <input type="number" x-model="filters.price_max" @input.debounce.300ms="performSearch()" placeholder="Max price (₱)..." min="0" step="0.01" class="rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
            </div>
            <div class="mt-3 flex justify-end">
                <button type="submit" class="px-4 py-2 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);">Apply Filters</button>
            </div>
        </div>
    </form>

    <!-- Skeleton Loading -->
    <div x-show="loading" class="space-y-3">
        <div class="skelly sk-line-md"></div>
        <div class="skelly sk-line-lg"></div>
        <div class="skelly sk-line-md"></div>
        <div class="skelly sk-line-sm"></div>
        <div class="skelly sk-line-md"></div>
    </div>

    <!-- Books Table -->
    <div x-show="!loading" x-cloak>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="text-left py-3 px-2 font-medium text-gray-600 dark:text-[#C1C4DC]">Title</th>
                        <th class="text-left py-3 px-2 font-medium text-gray-600 dark:text-[#C1C4DC]">Author</th>
                        <th class="text-left py-3 px-2 font-medium text-gray-600 dark:text-[#C1C4DC]">ISBN</th>
                        <th class="text-left py-3 px-2 font-medium text-gray-600 dark:text-[#C1C4DC]">Serial No.</th>
                        <th class="text-left py-3 px-2 font-medium text-gray-600 dark:text-[#C1C4DC]">Publisher</th>
                        <th class="text-center py-3 px-2 font-medium text-gray-600 dark:text-[#C1C4DC]">Year</th>
                        <th class="text-right py-3 px-2 font-medium text-gray-600 dark:text-[#C1C4DC]">Price</th>
                        <th class="text-center py-3 px-2 font-medium text-gray-600 dark:text-[#C1C4DC]">Qty</th>
                        <th class="text-center py-3 px-2 font-medium text-gray-600 dark:text-[#C1C4DC]">Available</th>
                        <th class="text-center py-3 px-2 font-medium text-gray-600 dark:text-[#C1C4DC]">Status</th>
                        <th class="text-left py-3 px-2 font-medium text-gray-600 dark:text-[#C1C4DC]">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="book in books" :key="book.id">
                        <tr class="border-b border-gray-100 dark:border-[#2A2F58]" :class="book.is_active ? '' : 'bg-gray-50 dark:bg-[#161A33]'">
                            <td class="py-2 px-2 font-medium text-gray-900 dark:text-[#E8EAF6]" x-text="book.title"></td>
                            <td class="py-2 px-2 text-gray-600 dark:text-[#C1C4DC]" x-text="book.author"></td>
                            <td class="py-2 px-2 text-gray-500 dark:text-[#8A90B0] text-xs" x-text="book.isbn || '—'"></td>
                            <td class="py-2 px-2 text-gray-500 dark:text-[#8A90B0] text-xs" x-text="book.serial_number || '—'"></td>
                            <td class="py-2 px-2 text-gray-600 dark:text-[#C1C4DC]" x-text="book.publisher || '—'"></td>
                            <td class="py-2 px-2 text-center text-gray-600 dark:text-[#C1C4DC]" x-text="book.year_published || '—'"></td>
                            <td class="py-2 px-2 text-right text-gray-700" x-text="book.price ? '₱ ' + parseFloat(book.price).toFixed(2) : '—'"></td>
                            <td class="py-2 px-2 text-center text-gray-900 dark:text-[#E8EAF6]" x-text="book.quantity"></td>
                            <td class="py-2 px-2 text-center">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                      :class="book.available_quantity > 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                                      x-text="book.available_quantity"></span>
                            </td>
                            <td class="py-2 px-2 text-center">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                      :class="book.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                                      x-text="book.is_active ? 'Active' : 'Inactive'"></span>
                            </td>
                            <td class="py-2 px-2">
                                <div class="flex gap-1 flex-wrap">
                                    <template x-if="book.is_active">
                                        <div class="flex gap-1">
                                            <a :href="'/librarian/books/' + book.id + '/edit'" class="px-2 py-1 text-xs font-medium text-gray-600 dark:text-[#C1C4DC] hover:text-gray-800">Edit</a>
                                            <button type="button" @click="openReplaceModal(book)" class="px-2 py-1 text-xs font-medium text-blue-600 hover:text-blue-800">Replace</button>
                                            <button type="button" @click="openDeactivateModal(book)" class="px-2 py-1 text-xs font-medium text-orange-600 hover:text-orange-800">Deactivate</button>
                                            <form method="POST" :action="'/librarian/books/' + book.id" onsubmit="return confirm('Permanently delete this book? This cannot be undone.')" class="inline">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="px-2 py-1 text-xs font-medium text-red-600 hover:text-red-800">Delete</button>
                                            </form>
                                        </div>
                                    </template>
                                    <template x-if="!book.is_active">
                                        <div class="flex gap-1">
                                            <form method="POST" :action="'/librarian/books/' + book.id + '/reactivate'" class="inline">
                                                @csrf @method('PATCH')
                                                <button type="submit" class="px-2 py-1 text-xs font-medium text-green-600 hover:text-green-800">Reactivate</button>
                                            </form>
                                            <form method="POST" :action="'/librarian/books/' + book.id" onsubmit="return confirm('Permanently delete this book? This cannot be undone.')" class="inline">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="px-2 py-1 text-xs font-medium text-red-600 hover:text-red-800">Delete</button>
                                            </form>
                                        </div>
                                    </template>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- Empty State -->
        <div x-show="books.length === 0" class="py-6 text-center text-gray-500 dark:text-[#8A90B0] text-sm">
            No books found. <a href="{{ route('librarian.books.create') }}" class="text-blue-600 hover:underline">Add the first book</a>.
        </div>

        <!-- Pagination -->
        <div class="mt-4 flex justify-between items-center" x-show="totalPages > 1">
            <span class="text-sm text-gray-500 dark:text-[#8A90B0]">
                Showing <span x-text="from"></span>-<span x-text="to"></span> of <span x-text="total"></span>
            </span>
            <div class="flex gap-1">
                <button @click="goToPage(currentPage - 1)" :disabled="currentPage <= 1"
                        class="px-3 py-1 text-sm rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 dark:hover:bg-[#161A33]">Previous</button>
                <template x-for="p in paginationRange" :key="p">
                    <button @click="goToPage(p)"
                            class="px-3 py-1 text-sm rounded-lg border"
                            :class="p === currentPage ? 'bg-[var(--navy)] text-white border-[var(--navy)]' : 'border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] hover:bg-gray-50 dark:hover:bg-[#161A33]'"
                            x-text="p"></button>
                </template>
                <button @click="goToPage(currentPage + 1)" :disabled="currentPage >= totalPages"
                        class="px-3 py-1 text-sm rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 dark:hover:bg-[#161A33]">Next</button>
            </div>
        </div>
    </div>
</div>

<!-- Deactivate Modal -->
<div x-data="deactivateModal()" x-show="open" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" @click="open = false">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
        <div class="inline-block align-bottom bg-white dark:bg-[#1A1E3B] rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form method="POST" :action="'/librarian/books/' + bookId + '/deactivate'">
                @csrf @method('PATCH')
                <div class="bg-white dark:bg-[#1A1E3B] px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-[#E8EAF6] mb-2">Deactivate Book</h3>
                    <p class="text-sm text-gray-600 dark:text-[#C1C4DC] mb-4">You are about to deactivate: <strong x-text="bookTitle"></strong></p>
                    <p class="text-sm text-gray-500 dark:text-[#8A90B0] mb-4">This book will be hidden from the borrow dropdown and marked as inactive.</p>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Reason for Deactivation *</label>
                        <textarea name="inactive_reason" required rows="3" placeholder="e.g., Damaged beyond repair, Lost, Outdated edition..."
                                  class="w-full rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"></textarea>
                    </div>
                </div>
                <div class="bg-gray-50 dark:bg-[#161A33] px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                    <button type="submit" class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-orange-600 text-base font-medium text-white hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 sm:w-auto sm:text-sm">
                        Deactivate
                    </button>
                    <button type="button" @click="open = false" class="mt-3 w-full inline-flex justify-center rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] shadow-sm px-4 py-2 bg-white dark:bg-[#1A1E3B] text-base font-medium text-gray-700 hover:bg-gray-50 dark:hover:bg-[#161A33] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function booksManager() {
    return {
        books: [],
        loading: true,
        currentPage: 1,
        totalPages: 1,
        total: 0,
        from: 0,
        to: 0,
        showAdvanced: false,
        filters: {
            search: '',
            serial_number: '',
            publisher: '',
            availability: '',
            active: 'active',
            year_from: '',
            year_to: '',
            price_min: '',
            price_max: ''
        },
        init() {
            this.performSearch();
        },
        async performSearch() {
            this.loading = true;
            try {
                const params = new URLSearchParams();
                if (this.filters.search) params.append('search', this.filters.search);
                if (this.filters.serial_number) params.append('serial_number', this.filters.serial_number);
                if (this.filters.publisher) params.append('publisher', this.filters.publisher);
                if (this.filters.availability) params.append('availability', this.filters.availability);
                if (this.filters.active) params.append('active', this.filters.active);
                if (this.filters.year_from) params.append('year_from', this.filters.year_from);
                if (this.filters.year_to) params.append('year_to', this.filters.year_to);
                if (this.filters.price_min) params.append('price_min', this.filters.price_min);
                if (this.filters.price_max) params.append('price_max', this.filters.price_max);
                params.append('page', this.currentPage);

                const response = await fetch(`/librarian/books/search?${params.toString()}`);
                const data = await response.json();
                this.books = data.data;
                this.currentPage = data.current_page;
                this.totalPages = data.last_page;
                this.total = data.total;
                this.from = data.from || 0;
                this.to = data.to || 0;
            } catch (e) {
                console.error('Search failed:', e);
                this.books = [];
            } finally {
                this.loading = false;
            }
        },
        goToPage(page) {
            if (page < 1 || page > this.totalPages) return;
            this.currentPage = page;
            this.performSearch();
        },
        get paginationRange() {
            const range = [];
            const start = Math.max(1, this.currentPage - 2);
            const end = Math.min(this.totalPages, this.currentPage + 2);
            for (let i = start; i <= end; i++) range.push(i);
            return range;
        },
        resetFilters() {
            this.filters = { search: '', serial_number: '', publisher: '', availability: '', active: 'active', year_from: '', year_to: '', price_min: '', price_max: '' };
            this.currentPage = 1;
            this.performSearch();
        },
        openDeactivateModal(book) {
            window.dispatchEvent(new CustomEvent('open-deactivate', { detail: { id: book.id, title: book.title } }));
        },
        openReplaceModal(book) {
            window.dispatchEvent(new CustomEvent('open-replace', { detail: { id: book.id, title: book.title } }));
        }
    }
}

function deactivateModal() {
    return {
        open: false,
        bookId: null,
        bookTitle: '',
        init() {
            window.addEventListener('open-deactivate', (e) => {
                this.bookId = e.detail.id;
                this.bookTitle = e.detail.title;
                this.open = true;
            });
        }
    }
}
</script>

<!-- Replace Modal -->
<div x-data="replaceModal()" x-show="open" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" @click="open = false">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
        <div class="inline-block align-bottom bg-white dark:bg-[#1A1E3B] rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form method="POST" :action="'/librarian/books/' + bookId + '/replace'">
                @csrf
                <div class="bg-white dark:bg-[#1A1E3B] px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-[#E8EAF6] mb-2">Replace Book</h3>
                    <p class="text-sm text-gray-600 dark:text-[#C1C4DC] mb-4">Create a replacement for: <strong x-text="bookTitle"></strong></p>
                    <p class="text-sm text-gray-500 dark:text-[#8A90B0] mb-4">The original book will be deactivated and this new book will take its place.</p>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">New Title *</label>
                            <input type="text" name="title" required :value="bookTitle"
                                   class="w-full rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Author *</label>
                            <input type="text" name="author" required
                                   class="w-full rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">ISBN</label>
                            <input type="text" name="isbn" maxlength="20"
                                   class="w-full rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Quantity *</label>
                            <input type="number" name="quantity" required min="1" value="1"
                                   class="w-full rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 dark:bg-[#161A33] px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                    <button type="submit" class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:w-auto sm:text-sm">
                        Create Replacement
                    </button>
                    <button type="button" @click="open = false" class="mt-3 w-full inline-flex justify-center rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] shadow-sm px-4 py-2 bg-white dark:bg-[#1A1E3B] text-base font-medium text-gray-700 hover:bg-gray-50 dark:hover:bg-[#161A33] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function replaceModal() {
    return {
        open: false,
        bookId: null,
        bookTitle: '',
        init() {
            window.addEventListener('open-replace', (e) => {
                this.bookId = e.detail.id;
                this.bookTitle = e.detail.title;
                this.open = true;
            });
        }
    }
}
</script>
@endsection
