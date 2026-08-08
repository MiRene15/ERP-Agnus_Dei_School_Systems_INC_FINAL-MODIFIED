@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('librarian.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <a href="{{ route('librarian.loans') }}" class="no-underline" style="color: var(--muted);">Borrowing & Returns</a>
    <span class="opacity-40">/</span>
    <span class="current">New Loan</span>
@endsection

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">New Book Loan</h2>
    <p class="text-gray-600 mt-1">Issue a book to a student.</p>
</div>

@if(session('error'))
    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm">{{ session('error') }}</div>
@endif

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 max-w-lg" x-data="borrowForm()">
    <form method="POST" action="{{ route('librarian.loans.store') }}">
        @csrf
        <div class="space-y-4">
            <!-- Student Search -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Student *</label>
                <div class="relative">
                    <input type="text" x-model="studentQuery" @input.debounce.300ms="searchStudents()" @focus="showResults = true" placeholder="Type name, student number, or LRN..."
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none" autocomplete="off">
                    <input type="hidden" name="student_id" :value="selectedStudentId" required>

                    <!-- Loading indicator -->
                    <div x-show="searching" class="absolute right-3 top-2.5">
                        <svg class="animate-spin h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>

                    <!-- Selected student chip -->
                    <div x-show="selectedStudentId" class="mt-2">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                            <span x-text="selectedStudentName"></span>
                            <button type="button" @click="clearStudent()" class="ml-2 text-blue-600 hover:text-blue-800">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </span>
                    </div>

                    <!-- Search results dropdown -->
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

                    <!-- No results -->
                    <div x-show="showResults && students.length === 0 && !searching && studentQuery.length >= 2 && !selectedStudentId" x-cloak
                         class="absolute z-10 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg">
                        <div class="px-3 py-2 text-sm text-gray-500">No students found</div>
                    </div>
                </div>
                @error('student_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <!-- Book Selection -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Book *</label>
                <select name="book_id" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                    <option value="">Select book</option>
                    @foreach($books as $book)
                    <option value="{{ $book->id }}" {{ old('book_id') == $book->id ? 'selected' : '' }}>
                        {{ $book->title }} — {{ $book->author }} ({{ $book->available_quantity }} available)
                    </option>
                    @endforeach
                </select>
                @error('book_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Borrow Date *</label>
                    <input type="date" name="borrow_date" value="{{ old('borrow_date', date('Y-m-d')) }}" required
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                    @error('borrow_date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Return Date *</label>
                    <input type="date" name="return_date" value="{{ old('return_date') }}" required
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                    @error('return_date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Condition at Borrow *</label>
                <select name="condition_at_borrow" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                    <option value="Good" {{ old('condition_at_borrow') === 'Good' ? 'selected' : '' }}>Good</option>
                    <option value="Minor Damage" {{ old('condition_at_borrow') === 'Minor Damage' ? 'selected' : '' }}>Minor Damage</option>
                    <option value="Major Damage" {{ old('condition_at_borrow') === 'Major Damage' ? 'selected' : '' }}>Major Damage</option>
                    <option value="Lost" {{ old('condition_at_borrow') === 'Lost' ? 'selected' : '' }}>Lost</option>
                </select>
                @error('condition_at_borrow') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="flex items-center gap-2 pt-2">
                <button type="submit" class="px-5 py-2 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">Issue Book</button>
                <a href="{{ route('librarian.loans') }}" class="px-5 py-2 rounded-lg text-sm font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 transition">Cancel</a>
            </div>
        </div>
    </form>
</div>

<script>
function borrowForm() {
    return {
        studentQuery: '',
        students: [],
        searching: false,
        showResults: false,
        selectedStudentId: null,
        selectedStudentName: '',
        async searchStudents() {
            if (this.studentQuery.length < 2) {
                this.students = [];
                return;
            }
            this.searching = true;
            try {
                const response = await fetch(`/librarian/students/search?search=${encodeURIComponent(this.studentQuery)}`);
                this.students = await response.json();
            } catch (e) {
                console.error('Search failed:', e);
                this.students = [];
            } finally {
                this.searching = false;
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
