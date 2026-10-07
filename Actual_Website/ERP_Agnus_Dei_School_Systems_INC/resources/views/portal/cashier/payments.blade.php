@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('cashier.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <span class="current">Process Payments</span>
@endsection

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">Process Payments</h2>
    <p class="text-gray-600 mt-1">Search for a student to process tuition and fee payments.</p>
</div>

@if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
@endif

<div x-data="searchPayments()">
    <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-6 mb-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="font-semibold text-gray-900 dark:text-[#E8EAF6]">Search Student</h3>
    </div>
    <form @submit.prevent="searchNow()" class="flex gap-3 items-center">
        <select x-model="selectedYear" @change="searchNow()" class="rounded-lg border border-gray-300 dark:border-[#3B4172] bg-white dark:bg-[#23274C] text-gray-900 dark:text-[#E8EAF6] text-sm px-3 py-2.5 focus:ring-2 focus:ring-blue-500 outline-none">
            @foreach($schoolYears as $sy)
                <option value="{{ $sy }}" {{ $sy === $schoolYear ? 'selected' : '' }}>{{ $sy }}</option>
            @endforeach
        </select>
        <input type="text" x-model="searchQuery" @input="performSearch()" placeholder="Search by name, student number, or LRN..."
               class="flex-1 rounded-lg border border-gray-300 dark:border-[#3B4172] bg-white dark:bg-[#23274C] text-gray-900 dark:text-[#E8EAF6] placeholder-gray-400 dark:placeholder-[#6A7094] text-sm px-3 py-2.5 focus:ring-2 focus:ring-blue-500 outline-none">
        <button type="submit" class="px-4 py-2 rounded-lg text-sm font-semibold text-white whitespace-nowrap" style="background: var(--navy);">Search</button>
        <button type="button" @click="clearSearch()" class="px-3 py-2 rounded-lg text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 transition">Clear</button>
    </form>
    <div x-show="loading && students.length === 0" class="mt-4 text-xs text-gray-500">Searching…</div>

    <!-- Skeleton Loading (first load only; list kept while typing) -->
    <div x-show="loading && students.length === 0" class="mt-4 space-y-3">
        <div class="skelly sk-line-md"></div>
        <div class="skelly sk-line-lg"></div>
        <div class="skelly sk-line-md"></div>
        <div class="skelly sk-line-sm"></div>
    </div>

    <div x-show="error" x-cloak class="mt-4 p-3 rounded-lg bg-red-50 border border-red-200 text-sm text-red-700 flex items-center justify-between gap-3">
        <span x-text="error"></span>
        <button type="button" @click="searchNow()" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-white border border-red-200 hover:bg-red-100">Refresh</button>
    </div>

    <div x-show="(loading || retryAfter > 0) && students.length > 0" x-cloak x-transition
         class="mt-4 px-3 py-2 rounded-lg bg-amber-50 dark:bg-[rgba(245,158,11,0.12)] border border-amber-200 dark:border-[rgba(245,158,11,0.3)] text-xs text-amber-800 dark:text-[#FCD34D]">
        Showing results for &quot;<span class="font-semibold" x-text="displayedQuery"></span>&quot; &mdash; searching for &quot;<span class="font-semibold" x-text="searchQuery"></span>&quot;&hellip;
    </div>

    <!-- Search Results (kept while typing/loading; never blanked) -->
    <div x-show="searchQuery.trim().length >= 2" class="mt-4" x-cloak x-transition
         :class="(loading || retryAfter > 0) && students.length > 0 ? 'opacity-50 transition-opacity' : 'opacity-100 transition-opacity'">
        <template x-if="students.length > 0">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-[#2A2F58]">
                            <th class="text-left py-3 px-2 font-medium text-gray-600 dark:text-[#8A90B0]">Student</th>
                            <th class="text-left py-3 px-2 font-medium text-gray-600 dark:text-[#8A90B0]">Student No.</th>
                            <th class="text-left py-3 px-2 font-medium text-gray-600 dark:text-[#8A90B0]">LRN</th>
                            <th class="text-left py-3 px-2 font-medium text-gray-600 dark:text-[#8A90B0]">Grade Level</th>
                            <th class="text-left py-3 px-2 font-medium text-gray-600 dark:text-[#8A90B0]">Balance</th>
                            <th class="text-left py-3 px-2 font-medium text-gray-600 dark:text-[#8A90B0]">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="s in students" :key="s.id">
                            <tr class="border-b border-gray-100 dark:border-[#2A2F58] hover:bg-gray-50 dark:hover:bg-[#23274C]">
                                <td class="py-3 px-2">
                                    <span class="font-medium text-gray-900 dark:text-[#E8EAF6]" x-text="s.first_name + ' ' + s.last_name"></span>
                                </td>
                                <td class="py-3 px-2 text-gray-600 dark:text-[#C1C4DC]" x-text="s.student_number"></td>
                                <td class="py-3 px-2 text-gray-600 dark:text-[#C1C4DC]" x-text="s.legacy_lrn || '—'"></td>
                                <td class="py-3 px-2 text-gray-700 dark:text-[#C1C4DC]" x-text="s.enrollments?.[0]?.section?.grade_level || 'N/A'"></td>
                                <td class="py-3 px-2">
                                    <span class="font-medium" :class="s.computed_balance > 0 ? 'text-red-600' : 'text-green-600'" x-text="'₱ ' + s.computed_balance.toFixed(2)"></span>
                                </td>
                                <td class="py-3 px-2 flex gap-2">
                                    <button type="button" @click="openPaymentModal(s.id, s.first_name + ' ' + s.last_name)" class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium text-white transition" style="background: var(--navy);" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                                        Process Payment
                                    </button>
                                    <button type="button" @click="openFinancialModal(s.id, s.first_name + ' ' + s.last_name)" class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 transition">
                                        Financial View
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </template>
        <template x-if="students.length === 0 && !loading && !error">
            <div class="py-12 text-center">
                <svg class="w-10 h-10 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <p class="text-sm font-medium text-gray-500" x-text="'No students found matching &quot;' + searchQuery + '&quot;.'"></p>
                <p class="text-xs text-gray-400 mt-1">Try searching by student name, number, or LRN.</p>
            </div>
        </template>
    </div>
    </div>
</div>

<script>
function searchPayments() {
    const component = {
        searchQuery: '',
        displayedQuery: '',
        selectedYear: '{{ $schoolYear }}',
        students: [],
        loading: false,
        error: '',
        retryAfter: 0,
        _controller: null,
        _seq: 0,
        _countdown: null,
        init() {
            window.addEventListener('payment-recorded', () => { this.searchNow(); });
        },
        startCountdown() {
            if (this._countdown) { try { clearInterval(this._countdown); } catch (e) {} this._countdown = null; }
            this._countdown = setInterval(() => {
                if (this.retryAfter > 0) { this.retryAfter--; this.error = `Too many searches - wait ${this.retryAfter}s.`; }
                if (this.retryAfter <= 0) {
                    if (this._countdown) { try { clearInterval(this._countdown); } catch (e) {} this._countdown = null; }
                    this.error = '';
                    this.searchNow();
                }
            }, 1000);
        },
        clearSearch() {
            if (this._controller) { try { this._controller.abort(); } catch (e) {} }
            if (this._countdown) { try { clearInterval(this._countdown); } catch (e) {} this._countdown = null; }
            this._seq++;
            this.searchQuery = '';
            this.displayedQuery = '';
            this.students = [];
            this.error = '';
            this.retryAfter = 0;
            this.loading = false;
        },
        async searchNow() {
            const q = (this.searchQuery || '').trim();
            if (q.length < 2) {
                if (this._controller) { try { this._controller.abort(); } catch (e) {} }
                this._seq++;
                this.students = [];
                this.displayedQuery = '';
                this.error = '';
                this.loading = false;
                return;
            }
            if (this._controller) { try { this._controller.abort(); } catch (e) {} }
            this._controller = new AbortController();
            const signal = this._controller.signal;
            const mySeq = ++this._seq;
            // While throttled, coalesce: no new fetch — countdown requeues latest at 0.
            if (this.retryAfter > 0) {
                try { this._controller.abort(); } catch (e) {}
                this.loading = false;
                if (!this._countdown) this.startCountdown();
                return;
            }
            this.loading = true;
            try {
                const response = await fetch(`/cashier/search?search=${encodeURIComponent(this.searchQuery)}&school_year=${encodeURIComponent(this.selectedYear)}`, { signal });
                if (signal.aborted || mySeq !== this._seq) return;
                if (response.status === 429) {
                    const retry = parseInt(response.headers.get('Retry-After') || '20', 10);
                    this.retryAfter = Number.isFinite(retry) && retry > 0 ? retry : 20;
                    this.error = `Too many searches - wait ${this.retryAfter}s.`;
                    console.info(`[search] 429 throttled, retry in ${this.retryAfter}s — showing wait box.`);
                    this.startCountdown();
                    return;
                }
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                const data = await response.json();
                if (signal.aborted || mySeq !== this._seq) return;
                if (this._countdown) { try { clearInterval(this._countdown); } catch (e) {} this._countdown = null; }
                this.students = Array.isArray(data) ? data : (data.data || []);
                this.displayedQuery = q;
                this.error = '';
                this.retryAfter = 0;
            } catch (e) {
                if (e && e.name === 'AbortError') return;
                if (signal.aborted || mySeq !== this._seq) return;
                console.error('Search failed:', e);
                this.error = 'Search failed — Refresh.';
            } finally {
                if (mySeq === this._seq) this.loading = false;
            }
        }
    };

    component.performSearch = window.AgnusSearch.debounce(function () {
        return this.searchNow();
    });

    return component;
}
</script>

@include('portal.cashier.partials.financial-modal')
@include('portal.cashier.partials.payment-modal')
@endsection
