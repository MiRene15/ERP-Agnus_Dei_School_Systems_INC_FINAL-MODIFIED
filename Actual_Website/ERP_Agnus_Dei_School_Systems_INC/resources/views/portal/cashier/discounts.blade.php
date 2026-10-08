@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('cashier.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <span class="current">Manage Discounts</span>
@endsection

@section('content')
<div class="mb-6 flex items-start justify-between gap-4 flex-wrap">
    <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-[#E8EAF6]">Manage Discounts</h2>
        <p class="text-gray-600 dark:text-[#C1C4DC] mt-1">Approvals post by themselves now. The list below is pre-change backlog only — nobody grants and collects alone.</p>
    </div>
    <a href="{{ route('cashier.requests', ['view' => 'requests']) }}" class="px-4 py-2 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">+ Request Discount</a>
</div>

@if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm">{{ session('error') }}</div>
@endif

@if(($approvedRequests ?? collect())->isNotEmpty())
<div class="bg-green-50 dark:bg-green-900/10 border border-green-200 dark:border-green-900/30 rounded-xl p-6 mb-6">
    <h3 class="font-semibold text-green-900 dark:text-green-200 mb-1">Approved — ready to apply ({{ $approvedRequests->count() }})</h3>
    <p class="text-xs text-green-700 dark:text-green-300 mb-4">These requests were approved by the Directress. Applying posts the discount to the ledger.</p>
    <div class="overflow-x-auto bg-white dark:bg-[#1A1E3B] rounded-lg border border-green-100 dark:border-[#2A2F58]">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 dark:bg-[#161A33]">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Student</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Type</th>
                    <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500 uppercase">Amount</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Approved by</th>
                    <th class="px-4 py-2 text-center text-xs font-semibold text-gray-500 uppercase">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($approvedRequests as $req)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2">
                        <div class="font-medium text-gray-900">{{ $req->ledger->student->first_name }} {{ $req->ledger->student->last_name }}</div>
                        <div class="text-xs text-gray-500">{{ $req->ledger->student->user->email ?? '' }}</div>
                    </td>
                    <td class="px-4 py-2 text-gray-600">{{ \App\Models\DiscountRequest::TYPES[$req->discount_type] ?? $req->discount_type }}</td>
                    <td class="px-4 py-2 text-right font-medium">₱{{ number_format($req->discount_amount, 2) }}</td>
                    <td class="px-4 py-2 text-xs text-gray-500">{{ $req->reviewer?->name ?? '—' }}</td>
                    <td class="px-4 py-2 text-center">
                        <form method="POST" action="{{ route('cashier.discounts.apply', $req) }}" onsubmit="return confirm('Apply this approved discount to the ledger?')" class="inline">
                            @csrf
                            <button type="submit" class="px-3 py-1.5 text-xs font-semibold text-white bg-green-600 hover:bg-green-700 rounded-lg">Apply</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<div x-data="ajaxTable('{{ route('cashier.discounts') }}', { search: '{{ request('search') }}' })">
    <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-6 mb-6">
        <form class="flex gap-4 items-end" @submit.prevent="reload()">
            <div class="flex-1">
                <label class="block text-xs font-semibold text-gray-500 dark:text-[#8A90B0] uppercase mb-1">Search Student</label>
                <input type="text" x-model="filters.search" @input="scheduleReload()" placeholder="Name or email..."
                       class="w-full border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700">Search</button>
            <button type="button" @click="reset()" class="px-4 py-2 bg-gray-100 dark:bg-[#23274C] text-gray-700 dark:text-[#C1C4DC] text-sm font-medium rounded-lg hover:bg-gray-200 dark:hover:bg-[#2A2F58]">Clear</button>
        </form>
    </div>

    <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] overflow-hidden">
        <div x-show="loading && !html" class="p-4 space-y-3">
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
        <div x-show="error" x-cloak class="m-4 p-3 rounded-lg bg-red-50 border border-red-200 text-sm text-red-700 flex items-center justify-between gap-3"><span x-text="error"></span><button type="button" @click="reload()" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-white border border-red-200 hover:bg-red-100">Refresh</button></div>
        <div x-show="html || !loading" x-cloak @click="handlePaginationClick($event)" x-ref="results" x-html="html" class="fade-in"></div>
    </div>
</div>
@endsection
