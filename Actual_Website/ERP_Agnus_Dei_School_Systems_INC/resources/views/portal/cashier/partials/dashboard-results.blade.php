<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-6">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-[#8A90B0] font-medium">Today's Collection</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-[#E8EAF6]">₱ {{ number_format($todayCollection, 2) }}</p>
            </div>
        </div>
    </div>
    <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-6">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-[#8A90B0] font-medium">Receipts Issued Today</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-[#E8EAF6]">{{ $receiptsToday }}</p>
            </div>
        </div>
    </div>
    <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-6">
        <a href="{{ route('cashier.collections-report') }}" class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-full bg-purple-100 flex items-center justify-center">
                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-[#8A90B0] font-medium">Collections Report</p>
                <p class="text-lg font-bold text-gray-900 dark:text-[#E8EAF6]">View by Date Range</p>
            </div>
        </a>
    </div>
    <a href="{{ route('cashier.discounts') }}" class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-6 hover:shadow-md transition block">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-full bg-amber-100 flex items-center justify-center">
                <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3-2-1.343-2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-[#8A90B0] font-medium">Discounts to Apply</p>
                <p class="text-2xl font-bold {{ ($approvedDiscounts ?? 0) > 0 ? 'text-amber-600' : 'text-gray-900 dark:text-[#E8EAF6]' }}">{{ $approvedDiscounts ?? 0 }}</p>
            </div>
        </div>
    </a>
    <a href="{{ route('cashier.refunds.index') }}" class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-6 hover:shadow-md transition block">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 15v-1a3 3 0 00-3-3H6a3 3 0 00-3 3v1m18 0v1a3 3 0 01-3 3H6a3 3 0 01-3-3v-1m18 0V9a2 2 0 00-2-2H5a2 2 0 00-2 2v6m18 0h-2"/></svg>
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-[#8A90B0] font-medium">Refunds to Release</p>
                <p class="text-2xl font-bold {{ ($pendingRefunds ?? 0) > 0 ? 'text-red-600' : 'text-gray-900 dark:text-[#E8EAF6]' }}">{{ $pendingRefunds ?? 0 }}</p>
            </div>
        </div>
    </a>
    @include('portal.cashier.partials.projection-summary-cards')
</div>
