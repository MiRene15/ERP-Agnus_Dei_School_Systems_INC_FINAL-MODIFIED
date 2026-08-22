@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('librarian.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <span class="current">Borrowing & Returns</span>
@endsection

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Borrowing & Returns</h2>
        <p class="text-gray-600 mt-1">Manage book borrowing and returns.</p>
    </div>
    <a href="{{ route('librarian.loans.borrow') }}" class="px-4 py-2 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">+ New Loan</a>
</div>

@if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
@endif

<div x-data="loansManager()">
<div class="mb-4 flex gap-2 flex-wrap items-center">
    <!-- Search Form -->
    <form @submit.prevent="performSearch()" class="flex gap-2 flex-1 flex-wrap">
        <input type="text" x-model="filters.search" placeholder="Search by student name or book title..."
               class="flex-1 min-w-[200px] rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
        <label class="flex items-center gap-2 text-sm text-gray-700">
            <input type="checkbox" x-model="filters.overdue" class="rounded border-gray-300 text-red-600 focus:ring-red-500">
            Overdue only
        </label>
        <button type="submit" class="px-4 py-2 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);">Filter</button>
        <button type="button" @click="resetFilters()" class="px-4 py-2 rounded-lg text-sm font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 transition">Clear</button>
    </form>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <!-- Skeleton Loading -->
    <div x-show="loading" class="space-y-3">
        <div class="skelly sk-line-md"></div>
        <div class="skelly sk-line-lg"></div>
        <div class="skelly sk-line-md"></div>
        <div class="skelly sk-line-sm"></div>
        <div class="skelly sk-line-md"></div>
    </div>

    <!-- Loans Table -->
    <div x-show="!loading" x-cloak>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="text-left py-3 px-2 font-medium text-gray-600">Student</th>
                        <th class="text-left py-3 px-2 font-medium text-gray-600">Book Title</th>
                        <th class="text-left py-3 px-2 font-medium text-gray-600">Borrowed</th>
                        <th class="text-left py-3 px-2 font-medium text-gray-600">Return Due</th>
                        <th class="text-left py-3 px-2 font-medium text-gray-600">Status</th>
                        <th class="text-left py-3 px-2 font-medium text-gray-600">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="txn in transactions" :key="txn.id">
                        <tr class="border-b border-gray-100" :class="isOverdue(txn) ? 'bg-red-50/50' : ''">
                            <td class="py-2 px-2 text-gray-900" x-text="(txn.student?.first_name || '') + ' ' + (txn.student?.last_name || '')"></td>
                            <td class="py-2 px-2 text-gray-600" x-text="txn.book_title"></td>
                            <td class="py-2 px-2 text-gray-600" x-text="formatDate(txn.borrow_date)"></td>
                            <td class="py-2 px-2 text-gray-600" x-text="formatDate(txn.return_date)"></td>
                             <td class="py-2 px-2">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                      :class="isOverdue(txn) ? 'bg-red-100 text-red-700' : (txn.status === 'Returned' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700')"
                                      x-text="isOverdue(txn) ? ('Overdue • ' + daysOverdue(txn) + 'd') : txn.status"></span>
                            </td>
                            <td class="py-2 px-2">
                                <template x-if="txn.status === 'Borrowed'">
                                    <a :href="'/librarian/loans/' + txn.id + '/return'" class="px-2 py-1 text-xs font-medium text-green-600 hover:text-green-800">Return Book</a>
                                </template>
                                <template x-if="txn.status !== 'Borrowed'">
                                    <span class="text-xs text-gray-400">Returned</span>
                                </template>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- Empty State -->
        <div x-show="transactions.length === 0" class="py-6 text-center text-gray-500 text-sm">
            No loans found. <span class="block text-xs mt-1 text-gray-400">Try adjusting filters or create a loan via [+ New Loan].</span>
        </div>

        <!-- Pagination -->
        <div class="mt-4 flex justify-between items-center" x-show="totalPages > 1">
            <span class="text-sm text-gray-500">
                Showing <span x-text="from"></span>-<span x-text="to"></span> of <span x-text="total"></span>
            </span>
            <div class="flex gap-1">
                <button @click="goToPage(currentPage - 1)" :disabled="currentPage <= 1"
                        class="px-3 py-1 text-sm rounded-lg border border-gray-300 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50">Previous</button>
                <template x-for="p in paginationRange" :key="p">
                    <button @click="goToPage(p)"
                            class="px-3 py-1 text-sm rounded-lg border"
                            :class="p === currentPage ? 'bg-[var(--navy)] text-white border-[var(--navy)]' : 'border-gray-300 hover:bg-gray-50'"
                            x-text="p"></button>
                </template>
                <button @click="goToPage(currentPage + 1)" :disabled="currentPage >= totalPages"
                        class="px-3 py-1 text-sm rounded-lg border border-gray-300 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50">Next</button>
            </div>
        </div>
    </div>
</div>
</div>

<script>
function loansManager() {
    return {
        transactions: [],
        loading: true,
        currentPage: 1,
        totalPages: 1,
        total: 0,
        from: 0,
        to: 0,
        filters: {
            search: '',
            overdue: false
        },
        init() {
            this.performSearch();
        },
        async performSearch() {
            this.loading = true;
            try {
                const params = new URLSearchParams();
                if (this.filters.search) params.append('search', this.filters.search);
                if (this.filters.overdue) params.append('overdue', '1');
                params.append('page', this.currentPage);

                const response = await fetch(`/librarian/loans/search?${params.toString()}`);
                const data = await response.json();
                this.transactions = data.data;
                this.currentPage = data.current_page;
                this.totalPages = data.last_page;
                this.total = data.total;
                this.from = data.from || 0;
                this.to = data.to || 0;
            } catch (e) {
                console.error('Search failed:', e);
                this.transactions = [];
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
            this.filters = { search: '', overdue: false };
            this.currentPage = 1;
            this.performSearch();
        },
        isOverdue(txn) {
            return txn.status === 'Borrowed' && new Date(txn.return_date) < new Date();
        },
        daysOverdue(txn) {
            return Math.max(0, Math.floor((new Date() - new Date(txn.return_date)) / 86400000));
        },
        formatDate(date) {
            return new Date(date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        }
    }
}
</script>
@endsection
