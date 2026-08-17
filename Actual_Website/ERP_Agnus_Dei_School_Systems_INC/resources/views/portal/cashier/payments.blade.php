@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('cashier.dashboard') }}" class="no-underline" style="color: var(--muted);">Cashier Dashboard</a>
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

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6" x-data="searchPayments()">
    <h3 class="font-semibold text-gray-900 mb-4">Search Student</h3>
    <div class="flex gap-3">
        <input type="text" x-model="searchQuery" @input.debounce.300ms="performSearch()" placeholder="Search by name, student number, or LRN..."
               class="flex-1 rounded-lg border-gray-300 text-sm focus:ring-2 focus:ring-blue-500">
        <button type="button" @click="performSearch()" class="px-4 py-2 rounded-lg text-sm font-semibold text-white" style="background: var(--navy);">Search</button>
    </div>

    <!-- Skeleton Loading -->
    <div x-show="loading" class="mt-4 space-y-3">
        <div class="skelly sk-line-md"></div>
        <div class="skelly sk-line-lg"></div>
        <div class="skelly sk-line-md"></div>
        <div class="skelly sk-line-sm"></div>
    </div>

    <!-- Search Results -->
    <div x-show="!loading && searchQuery.length >= 2" class="mt-4" x-cloak>
        <template x-if="students.length > 0">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="text-left py-3 px-2 font-medium text-gray-600">Student</th>
                            <th class="text-left py-3 px-2 font-medium text-gray-600">Student No.</th>
                            <th class="text-left py-3 px-2 font-medium text-gray-600">LRN</th>
                            <th class="text-left py-3 px-2 font-medium text-gray-600">Grade Level</th>
                            <th class="text-left py-3 px-2 font-medium text-gray-600">Balance</th>
                            <th class="text-left py-3 px-2 font-medium text-gray-600">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="s in students" :key="s.id">
                            <tr class="border-b border-gray-100 hover:bg-gray-50">
                                <td class="py-3 px-2">
                                    <span class="font-medium text-gray-900" x-text="s.first_name + ' ' + s.last_name"></span>
                                </td>
                                <td class="py-3 px-2 text-gray-600" x-text="s.student_number"></td>
                                <td class="py-3 px-2 text-gray-600" x-text="s.legacy_lrn || '—'"></td>
                                <td class="py-3 px-2 text-gray-700" x-text="s.enrollments?.[0]?.section?.grade_level || 'N/A'"></td>
                                <td class="py-3 px-2">
                                    <span class="font-medium" :class="s.computed_balance > 0 ? 'text-red-600' : 'text-green-600'" x-text="'₱ ' + s.computed_balance.toFixed(2)"></span>
                                </td>
                                <td class="py-3 px-2 flex gap-2">
                                    <a :href="'/cashier/payment/' + s.id" class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium text-white transition" style="background: var(--navy);" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                                        Process Payment
                                    </a>
                                    <a :href="'/cashier/financial/' + s.id" class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 transition">
                                        Financial View
                                    </a>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </template>
        <template x-if="students.length === 0">
            <p class="text-sm text-gray-500 text-center py-4" x-text="'No students found matching &quot;' + searchQuery + '&quot;.'"></p>
        </template>
    </div>
</div>

<script>
function searchPayments() {
    return {
        searchQuery: '',
        students: [],
        loading: false,
        async performSearch() {
            if (this.searchQuery.length < 2) {
                this.students = [];
                return;
            }
            this.loading = true;
            try {
                const response = await fetch(`/cashier/search?search=${encodeURIComponent(this.searchQuery)}`);
                this.students = await response.json();
            } catch (e) {
                console.error('Search failed:', e);
                this.students = [];
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>
@endsection
