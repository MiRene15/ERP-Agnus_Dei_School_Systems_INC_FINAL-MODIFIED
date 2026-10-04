<div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-6">
    <div class="flex flex-wrap items-center gap-x-10 gap-y-6">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-full bg-indigo-100 flex items-center justify-center">
                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-[#8A90B0] font-medium">Receivables</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-[#E8EAF6]">₱ {{ number_format($outstandingTotal ?? 0, 2) }}</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-full bg-amber-100 flex items-center justify-center">
                <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4m6 9H5a2 2 0 01-2-2V5a2 2 0 012-2h10a2 2 0 012 2v1"/></svg>
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-[#8A90B0] font-medium">Estimate</p>
                @if($hasCollections ?? false)
                    <p class="text-2xl font-bold text-gray-900 dark:text-[#E8EAF6]">₱ {{ number_format($estimate ?? 0, 2) }}</p>
                @else
                    <p class="text-2xl font-bold text-gray-500 dark:text-[#8A90B0]">No collections yet</p>
                @endif
            </div>
        </div>
    </div>
</div>