<div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-6" x-data="{ selected: [], allIds: @js($pendingConfirmations->pluck('id')->toArray()) }">
    @if($pendingConfirmations->isEmpty())
        <div class="py-12 text-center">
            <svg class="w-12 h-12 mx-auto mb-3 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <p class="text-sm font-medium text-gray-500 dark:text-[#8A90B0]">No pending accounts found.</p>
            <p class="text-xs text-gray-400 mt-1">All student accounts have been verified.</p>
        </div>
    @else
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-3">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" class="rounded border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] text-blue-600 focus:ring-blue-500"
                       :checked="selected.length === allIds.length && allIds.length > 0"
                       @click="selected.length === allIds.length ? selected = [] : selected = [...allIds]">
                <span class="text-sm text-gray-600 dark:text-[#C1C4DC]">Select All</span>
            </label>
            <span class="text-xs text-gray-400" x-text="selected.length + ' / ' + allIds.length + ' selected'"></span>
        </div>
        <form method="POST" action="{{ route('admin.confirm-batch') }}" x-show="selected.length > 0">
            @csrf
            <template x-for="id in selected" :key="id">
                <input type="hidden" name="ledger_ids[]" :value="id">
            </template>
            <button type="submit"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold text-white transition"
                    style="background: var(--navy);" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Confirm Selected (<span x-text="selected.length"></span>)
            </button>
        </form>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="text-left py-3 px-2 font-medium text-gray-600 dark:text-[#C1C4DC] w-10"></th>
                    <th class="text-left py-3 px-2 font-medium text-gray-600 dark:text-[#C1C4DC]">Student</th>
                    <th class="text-left py-3 px-2 font-medium text-gray-600 dark:text-[#C1C4DC]">Student No.</th>
                    <th class="text-left py-3 px-2 font-medium text-gray-600 dark:text-[#C1C4DC]">Grade / Section</th>
                    <th class="text-left py-3 px-2 font-medium text-gray-600 dark:text-[#C1C4DC]">Payment Plan</th>
                    <th class="text-left py-3 px-2 font-medium text-gray-600 dark:text-[#C1C4DC]">Total Paid</th>
                    <th class="text-left py-3 px-2 font-medium text-gray-600 dark:text-[#C1C4DC]">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pendingConfirmations as $ledger)
                <tr class="border-b border-gray-100 dark:border-[#2A2F58]" :class="selected.includes({{ $ledger->id }}) ? 'bg-blue-50' : ''">
                    <td class="py-3 px-2">
                        <input type="checkbox" class="rounded border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] text-blue-600 focus:ring-blue-500"
                               :checked="selected.includes({{ $ledger->id }})"
                               @click="selected.includes({{ $ledger->id }}) ? selected = selected.filter(x => x !== {{ $ledger->id }}) : selected.push({{ $ledger->id }})">
                    </td>
                    <td class="py-3 px-2">
                        <span class="font-medium text-gray-900 dark:text-[#E8EAF6]">{{ $ledger->student->first_name }} {{ $ledger->student->last_name }}</span>
                    </td>
                    <td class="py-3 px-2 text-gray-700">{{ $ledger->student->student_number }}</td>
                    <td class="py-3 px-2 text-gray-700">
                        {{ $ledger->student->enrollments->first()?->section?->grade_level ?? 'N/A' }} -
                        {{ $ledger->student->enrollments->first()?->section?->section_name ?? '' }}
                    </td>
                    <td class="py-3 px-2">
                        <span class="text-xs font-medium px-2 py-0.5 rounded-full {{ $ledger->payment_plan === 'full' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700' }}">
                            {{ ucfirst($ledger->payment_plan) }}
                        </span>
                    </td>
                    <td class="py-3 px-2 text-gray-700">₱ {{ number_format($ledger->total_paid, 2) }}</td>
                    <td class="py-3 px-2">
                        <form method="POST" action="{{ route('admin.confirm-account', $ledger) }}">
                            @csrf
                            <button type="submit"
                                    class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium text-white transition cursor-pointer"
                                    style="background: var(--navy);" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                                Confirm
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
