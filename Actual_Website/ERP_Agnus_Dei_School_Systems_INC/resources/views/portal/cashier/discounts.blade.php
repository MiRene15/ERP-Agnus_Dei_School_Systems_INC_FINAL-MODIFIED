@extends('portal.layouts.app')

@section('breadcrumbs')
    <span class="current">Manage Discounts</span>
@endsection

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">Manage Discounts</h2>
    <p class="text-gray-600 mt-1">Grant or update discounts for enrolled students. Discounts persist across payments.</p>
</div>

<div x-data="ajaxTable('{{ route('cashier.discounts') }}', { search: '{{ request('search') }}' })">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
        <form class="flex gap-4 items-end" @submit.prevent="reload()">
            <div class="flex-1">
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Search Student</label>
                <input type="text" x-model="filters.search" @input.debounce.300ms="reload()" placeholder="Name or email..."
                       class="w-full border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700">Search</button>
            <button type="button" @click="reset()" class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200">Clear</button>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div x-show="loading" class="p-4 space-y-3">
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
        <div x-show="!loading" x-cloak @click="handlePaginationClick($event)" x-ref="results" x-html="html" class="fade-in"></div>
    </div>
</div>

<div x-data="{ open: false, ledgerId: null, discountType: 'honor', discountAmount: 0, totalAssessed: 0 }"
     x-show="open" x-cloak class="fixed inset-0 z-50"
     @open-discount-modal.window="ledgerId = $event.detail.id; discountType = $event.detail.type || 'honor'; discountAmount = $event.detail.amount || 0; totalAssessed = $event.detail.total; open = true;">
    <div class="fixed inset-0 bg-black/40" @click="open = false"></div>
    <div class="fixed inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6" @click.stop>
            <h3 class="text-lg font-bold text-gray-900 mb-4">Update Discount</h3>
            <form :action="'{{ url('/cashier/discounts') }}/' + ledgerId" method="POST">
                @csrf
                @method('POST')
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Discount Type</label>
                        <select name="discount_type" x-model="discountType" required
                                class="w-full border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="honor">Honor</option>
                            <option value="sibling">Sibling</option>
                            <option value="esc">ESC Grant</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Discount Amount (₱)</label>
                        <input type="number" name="discount_amount" x-model.number="discountAmount" min="0" step="0.01"
                               :max="totalAssessed" required
                               class="w-full border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <p class="text-xs text-gray-400 mt-1">Max: ₱<span x-text="totalAssessed.toLocaleString('en-PH', {minimumFractionDigits: 2})"></span></p>
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" @click="open = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">Save Discount</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openDiscountModal(id, type, amount, total) {
    window.dispatchEvent(new CustomEvent('open-discount-modal', { detail: { id, type, amount, total } }));
}
</script>
@endsection
