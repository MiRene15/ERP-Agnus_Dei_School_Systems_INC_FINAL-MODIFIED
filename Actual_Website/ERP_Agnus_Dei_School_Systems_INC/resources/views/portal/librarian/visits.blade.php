@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('librarian.dashboard') }}" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <span class="current">Library Visits</span>
@endsection

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">Library Visits</h2>
    <p class="text-gray-600 mt-1">Time-in / time-out log of students visiting the library.</p>
</div>

@if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
@endif

@if(session('error'))
    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm">{{ session('error') }}</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="font-semibold text-gray-900 mb-3">Clock In Student</h3>
        <form method="POST" action="{{ route('librarian.visits.clock-in') }}" class="space-y-3" x-data="clockInForm()">
            @csrf
            <div>
                <input type="hidden" name="student_id" :value="selectedStudentId" required>
                <label class="block text-xs font-medium text-gray-500 mb-1">Search Student</label>
                <div class="relative">
                    <input type="text" x-model="studentQuery" @input.debounce.600ms="searchStudents()" @focus="showResults = true"
                           placeholder="Type name, student number, or LRN..." autocomplete="off"
                           class="w-full rounded-lg border-gray-300 text-sm focus:ring-2 focus:ring-blue-500">

                    <div x-show="searching" class="absolute right-3 top-2.5">
                        <svg class="animate-spin h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>

                    <div x-show="selectedStudentId" class="mt-2">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                            <span x-text="selectedStudentName"></span>
                            <button type="button" @click="clearStudent()" class="ml-2 text-blue-600 hover:text-blue-800">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </span>
                    </div>

                    <div x-show="showResults && students.length > 0 && !selectedStudentId" x-cloak
                         class="absolute z-10 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                        <template x-for="s in students" :key="s.id">
                            <div @click="selectStudent(s)" class="px-3 py-2 cursor-pointer hover:bg-gray-50 border-b border-gray-100 last:border-0">
                                <div class="font-medium text-gray-900 text-sm" x-text="s.first_name + ' ' + s.last_name"></div>
                                <div class="text-xs text-gray-500">
                                    <span x-text="'No: ' + s.student_number"></span>
                                    <span x-show="s.legacy_lrn" x-text="' | LRN: ' + s.legacy_lrn"></span>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div x-show="showResults && students.length === 0 && !searching && studentQuery.length >= 2 && !selectedStudentId" x-cloak
                         class="absolute z-10 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg">
                        <div class="px-3 py-2 text-sm text-gray-500">No students found</div>
                    </div>

                    <div x-show="searchError" x-cloak class="mt-1 text-xs text-red-600" x-text="searchError"></div>
                </div>
            </div>
            <button type="submit" class="w-full px-4 py-2 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);">Clock In</button>
        </form>
    </div>

    <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 p-4"
         x-data="ajaxTable('{{ route('librarian.visits') }}', { search: '{{ request('search') }}', date_from: '{{ request('date_from') }}', date_to: '{{ request('date_to') }}' })">
        <form method="GET" class="flex flex-wrap gap-3 items-end mb-4" @submit.prevent="if(!(filters.date_from&&filters.date_to&&filters.date_to<filters.date_from))reload()">
            <div class="flex-1 min-w-[150px]">
                <label class="block text-xs font-medium text-gray-500 mb-1">Search</label>
                <input type="text" x-model="filters.search" @input.debounce.600ms="reload()" placeholder="Student name..."
                       class="w-full rounded-lg border-gray-300 text-sm focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="min-w-[130px]">
                <label class="block text-xs font-medium text-gray-500 mb-1">From</label>
                <input type="date" x-model="filters.date_from" :max="filters.date_to || '{{ date('Y-m-d') }}'" max="{{ date('Y-m-d') }}" min="1987-01-01" @change="if(!(filters.date_from&&filters.date_to&&filters.date_to<filters.date_from))reload()" class="w-full rounded-lg border-gray-300 text-sm">
            </div>
            <div class="min-w-[130px]">
                <label class="block text-xs font-medium text-gray-500 mb-1">To</label>
                <input type="date" x-model="filters.date_to" :min="filters.date_from || '1987-01-01'" max="{{ date('Y-m-d') }}" @change="if(!(filters.date_from&&filters.date_to&&filters.date_to<filters.date_from))reload()" class="w-full rounded-lg border-gray-300 text-sm">
            </div>
            <button type="submit" class="px-4 py-2 rounded-lg text-sm font-semibold text-white" style="background: var(--navy);">Filter</button>
            <button type="button" @click="reset()" class="px-4 py-2 rounded-lg text-sm font-semibold bg-gray-100 text-gray-700 hover:bg-gray-200">Clear</button>
            <p x-show="filters.date_from && filters.date_to && filters.date_to < filters.date_from" class="w-full text-red-500 text-xs">End date can't be before start date.</p>
        </form>

        <!-- Skeleton loading -->
        <div x-show="loading && !html" class="space-y-3">
            <template x-for="i in 5" :key="i">
                <div class="skelly sk-card">
                    <div class="grid grid-cols-5 gap-4 px-2">
                        <div class="skelly sk-line-md col-span-2"></div>
                        <div class="skelly sk-line-md"></div>
                        <div class="skelly sk-line-md"></div>
                        <div class="skelly sk-line-sm"></div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Results injected via AJAX -->
        <div x-show="error" x-cloak class="m-4 p-3 rounded-lg bg-red-50 border border-red-200 text-sm text-red-700 flex items-center justify-between gap-3"><span x-text="error"></span><button type="button" @click="reload()" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-white border border-red-200 hover:bg-red-100">Refresh</button></div>

        <div x-show="html || !loading" x-cloak @click="handlePaginationClick($event)" x-ref="results" x-html="html" class="fade-in"></div>
    </div>
</div>

<script>
function clockInForm() {
    return {
        studentQuery: '',
        students: [],
        searching: false,
        showResults: false,
        selectedStudentId: null,
        selectedStudentName: '',
        searchError: '',
        retryAfter: 0,
        _controller: null,
        _seq: 0,
        _countdown: null,
        startCountdown() {
            if (this._countdown) { try { clearInterval(this._countdown); } catch (e) {} this._countdown = null; }
            this._countdown = setInterval(() => {
                if (this.retryAfter > 0) { this.retryAfter--; this.searchError = `Too many searches - wait ${this.retryAfter}s.`; }
                if (this.retryAfter <= 0) {
                    if (this._countdown) { try { clearInterval(this._countdown); } catch (e) {} this._countdown = null; }
                    this.searchError = '';
                    this.searchStudents();
                }
            }, 1000);
        },
        async searchStudents() {
            if ((this.studentQuery || '').trim().length < 2) {
                if (this._controller) { try { this._controller.abort(); } catch (e) {} }
                if (this._countdown) { try { clearInterval(this._countdown); } catch (e) {} this._countdown = null; }
                this._seq++;
                this.students = [];
                this.searchError = '';
                this.retryAfter = 0;
                this.searching = false;
                return;
            }
            if (this._controller) { try { this._controller.abort(); } catch (e) {} }
            this._controller = new AbortController();
            const signal = this._controller.signal;
            const mySeq = ++this._seq;
            // While throttled, coalesce: no new fetch — countdown requeues latest at 0.
            if (this.retryAfter > 0) {
                try { this._controller.abort(); } catch (e) {}
                this.searching = false;
                if (!this._countdown) this.startCountdown();
                return;
            }
            this.searching = true;
            try {
                const response = await fetch(`/librarian/students/search?search=${encodeURIComponent(this.studentQuery)}`, { signal });
                if (signal.aborted || mySeq !== this._seq) return;
                if (response.status === 429) {
                    const retry = parseInt(response.headers.get('Retry-After') || '20', 10);
                    this.retryAfter = Number.isFinite(retry) && retry > 0 ? retry : 20;
                    this.searchError = `Too many searches - wait ${this.retryAfter}s.`;
                    console.info(`[search] 429 throttled, retry in ${this.retryAfter}s — showing wait note.`);
                    this.startCountdown();
                    return;
                }
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                const data = await response.json();
                if (signal.aborted || mySeq !== this._seq) return;
                if (this._countdown) { try { clearInterval(this._countdown); } catch (e) {} this._countdown = null; }
                this.students = Array.isArray(data) ? data : (data.data || []);
                this.searchError = '';
                this.retryAfter = 0;
            } catch (e) {
                if (e && e.name === 'AbortError') return;
                if (signal.aborted || mySeq !== this._seq) return;
                console.error('Search failed:', e);
            } finally {
                if (mySeq === this._seq) this.searching = false;
            }
        },
        selectStudent(student) {
            this.selectedStudentId = student.id;
            this.selectedStudentName = student.first_name + ' ' + student.last_name + ' (' + student.student_number + ')';
            this.studentQuery = '';
            this.students = [];
            this.showResults = false;
        },
        clearStudent() {
            this.selectedStudentId = null;
            this.selectedStudentName = '';
        }
    }
}
</script>
@endsection
