@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('cashier.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <span class="current">Requests</span>
@endsection

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900 dark:text-[#E8EAF6]">Requests</h2>
    <p class="text-gray-600 dark:text-[#C1C4DC] mt-1">Discounts to apply, requests to review, refunds to release — one queue.</p>
</div>

@if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm">{{ session('error') }}</div>
@endif

<div x-data="{ tab: ((v) => ['discounts','requests','refunds'].includes(v) ? v : 'discounts')(new URLSearchParams(window.location.search).get('view')), setTab(v) { this.tab = v; const u = new URL(window.location.href); if (v === 'discounts') { u.searchParams.delete('view'); } else { u.searchParams.set('view', v); } window.history.replaceState({}, '', u); } }">
    <div class="inline-flex gap-1 mb-6 p-1 rounded-xl bg-gray-100 dark:bg-[#23274C]" role="tablist" aria-label="Request type">
        <button @click="setTab('discounts')" :class="tab==='discounts' ? 'text-white shadow font-semibold' : 'text-gray-500 dark:text-[#8A90B0]'" :style="tab==='discounts' ? 'background: var(--navy);' : ''" class="px-4 py-2 text-sm rounded-lg transition" role="tab" :aria-selected="tab==='discounts'">Discounts @if($badgeCounts['discounts'] > 0)<span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-semibold bg-green-600 text-white">{{ $badgeCounts['discounts'] }}</span>@endif</button>
        <button @click="setTab('requests')" :class="tab==='requests' ? 'text-white shadow font-semibold' : 'text-gray-500 dark:text-[#8A90B0]'" :style="tab==='requests' ? 'background: var(--navy);' : ''" class="px-4 py-2 text-sm rounded-lg transition" role="tab" :aria-selected="tab==='requests'">Discount Requests @if($badgeCounts['requests'] > 0)<span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-semibold bg-amber-500 text-white">{{ $badgeCounts['requests'] }}</span>@endif</button>
        <button @click="setTab('refunds')" :class="tab==='refunds' ? 'text-white shadow font-semibold' : 'text-gray-500 dark:text-[#8A90B0]'" :style="tab==='refunds' ? 'background: var(--navy);' : ''" class="px-4 py-2 text-sm rounded-lg transition" role="tab" :aria-selected="tab==='refunds'">Refunds @if($badgeCounts['refunds'] > 0)<span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-semibold bg-red-500 text-white">{{ $badgeCounts['refunds'] }}</span>@endif</button>
    </div>

    <div x-show="tab==='discounts'" x-cloak>
        <div class="mb-6 flex items-start justify-between gap-4 flex-wrap">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-[#E8EAF6]">Manage Discounts</h2>
                <p class="text-gray-600 dark:text-[#C1C4DC] mt-1">Two-step process: request with proof → Directress approves → Cashier applies here. Nobody grants and collects alone.</p>
            </div>
            <button type="button" @click="setTab('requests')" class="px-4 py-2 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">+ Request Discount</button>
        </div>

        @if($approvedRequests->isNotEmpty())
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
    </div>

    <div x-show="tab==='requests'" x-cloak>
        <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-6 mb-6">
            <h3 class="font-semibold text-gray-900 dark:text-[#E8EAF6] mb-4">New Request</h3>
            <form method="POST" action="{{ route('discount-requests.store') }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @csrf
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-[#C1C4DC] mb-1">Student (active enrollment) *</label>
                    <select name="student_ledger_id" id="ledger-select" required class="w-full rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                        <option value="">Select student…</option>
                        @foreach($ledgers as $ledger)
                            <option value="{{ $ledger->id }}" data-assessed="{{ $ledger->total_assessed }}" {{ old('student_ledger_id') == $ledger->id ? 'selected' : '' }}>
                                {{ $ledger->student->first_name }} {{ $ledger->student->last_name }} — {{ $ledger->student->user->email ?? '' }} (₱{{ number_format($ledger->total_assessed, 2) }} assessed)
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-[#C1C4DC] mb-1">Discount Type *</label>
                    <select name="discount_type" required class="w-full rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                        @foreach($discountTypes as $key => $label)
                            <option value="{{ $key }}" {{ old('discount_type') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-[#C1C4DC] mb-1">Amount (₱) *</label>
                    <input type="number" name="discount_amount" id="discount-amount" value="{{ old('discount_amount') }}" required min="0" step="0.01" class="w-full rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                    <div class="flex gap-2 mt-2 flex-wrap">
                        <span class="text-xs text-gray-500 dark:text-[#8A90B0] self-center">Quick % of assessed:</span>
                        @foreach([5, 10, 15, 20, 30] as $pct)
                        <button type="button" onclick="setDiscountPreset({{ $pct }})" class="px-3 py-1 rounded-lg text-xs font-semibold border border-gray-300 dark:border-[#3B4172] text-gray-700 dark:text-[#C1C4DC] hover:border-indigo-500 hover:text-indigo-600 transition">{{ $pct }}%</button>
                        @endforeach
                        <span id="preset-hint" class="text-xs text-indigo-600 dark:text-[#8A90B0] self-center"></span>
                    </div>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-[#C1C4DC] mb-1">Proof Details *</label>
                    <textarea name="proof_details" required minlength="10" maxlength="1000" rows="2" placeholder="e.g. ESC certificate no. ESC-2026-0417; Honor roll Q3 posted; sibling: Juan Dela Cruz Grade 8-B" class="w-full rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">{{ old('proof_details') }}</textarea>
                </div>
                <div class="md:col-span-2">
                    <button type="submit" class="px-6 py-2.5 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">Send to Directress</button>
                </div>
            </form>
        </div>

        <script>
        function setDiscountPreset(pct) {
            var sel = document.getElementById('ledger-select');
            var hint = document.getElementById('preset-hint');
            var opt = sel.options[sel.selectedIndex];
            var assessed = opt ? parseFloat(opt.getAttribute('data-assessed') || '0') : 0;
            if (!opt || !opt.value || assessed <= 0) {
                hint.textContent = 'Select a student first.';
                return;
            }
            var amount = Math.round(assessed * pct) / 100;
            document.getElementById('discount-amount').value = amount.toFixed(2);
            hint.textContent = pct + '% of ₱' + assessed.toLocaleString('en-PH', {minimumFractionDigits: 2}) + ' = ₱' + amount.toLocaleString('en-PH', {minimumFractionDigits: 2});
        }
        </script>

        <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-6">
            <h3 class="font-semibold text-gray-900 dark:text-[#E8EAF6] mb-4">All Requests</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-[#2A2F58]">
                            <th class="text-left py-2 px-2 font-medium text-gray-600 dark:text-[#8A90B0]">Student</th>
                            <th class="text-left py-2 px-2 font-medium text-gray-600 dark:text-[#8A90B0]">Type / Amount</th>
                            <th class="text-left py-2 px-2 font-medium text-gray-600 dark:text-[#8A90B0]">Proof</th>
                            <th class="text-left py-2 px-2 font-medium text-gray-600 dark:text-[#8A90B0]">Requested by</th>
                            <th class="text-left py-2 px-2 font-medium text-gray-600 dark:text-[#8A90B0]">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($requests as $req)
                        <tr class="border-b border-gray-50 dark:border-[#2A2F58]">
                            <td class="py-2 px-2 font-medium text-gray-900 dark:text-[#E8EAF6]">{{ $req->ledger->student->first_name }} {{ $req->ledger->student->last_name }}</td>
                            <td class="py-2 px-2 text-gray-600 dark:text-[#C1C4DC]">{{ $discountTypes[$req->discount_type] ?? $req->discount_type }} — ₱{{ number_format($req->discount_amount, 2) }}</td>
                            <td class="py-2 px-2 text-gray-600 dark:text-[#C1C4DC] text-xs max-w-xs">{{ $req->proof_details }}</td>
                            <td class="py-2 px-2 text-xs text-gray-500">{{ $req->requester?->name ?? '—' }}</td>
                            <td class="py-2 px-2">
                                @php $badge = ['pending' => 'bg-amber-100 text-amber-700', 'approved' => 'bg-blue-100 text-blue-700', 'rejected' => 'bg-red-100 text-red-700', 'applied' => 'bg-green-100 text-green-700'][$req->status] ?? 'bg-gray-100 text-gray-600'; @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $badge }}">{{ ucfirst($req->status) }}</span>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="py-6 text-center text-gray-500 text-sm">No requests yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $requests->links() }}</div>
        </div>
    </div>

    <div x-show="tab==='refunds'" x-cloak>
        <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-6 mb-6">
            <h3 class="font-semibold text-gray-900 dark:text-[#E8EAF6] mb-4">Awaiting Release ({{ $pending->count() }})</h3>
            @if($pending->isEmpty())
                <p class="text-sm text-gray-500 py-4 text-center">No payouts waiting.</p>
            @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-[#2A2F58]">
                            <th class="text-left py-2 px-2 font-medium text-gray-600 dark:text-[#8A90B0]">Student</th>
                            <th class="text-left py-2 px-2 font-medium text-gray-600 dark:text-[#8A90B0]">Section</th>
                            <th class="text-right py-2 px-2 font-medium text-gray-600 dark:text-[#8A90B0]">Refund Due</th>
                            <th class="text-left py-2 px-2 font-medium text-gray-600 dark:text-[#8A90B0]">Approved by</th>
                            <th class="text-center py-2 px-2 font-medium text-gray-600 dark:text-[#8A90B0]">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pending as $w)
                        <tr class="border-b border-gray-50 dark:border-[#2A2F58]">
                            <td class="py-2 px-2 font-medium text-gray-900 dark:text-[#E8EAF6]">{{ $w->student->first_name }} {{ $w->student->last_name }}</td>
                            <td class="py-2 px-2 text-gray-600 dark:text-[#C1C4DC]">{{ $w->enrollment->section->section_name ?? '—' }}</td>
                            <td class="py-2 px-2 text-right font-medium text-red-600">₱{{ number_format($w->refund_amount, 2) }}</td>
                            <td class="py-2 px-2 text-xs text-gray-500">{{ $w->processor?->name ?? '—' }}</td>
                            <td class="py-2 px-2 text-center">
                                <form method="POST" action="{{ route('cashier.refunds.release', $w) }}" onsubmit="return confirm('Release refund payout of ₱{{ number_format($w->refund_amount, 2) }} to {{ $w->student->first_name }} {{ $w->student->last_name }}?')" class="inline">
                                    @csrf
                                    <button type="submit" class="px-3 py-1.5 text-xs font-semibold text-white bg-green-600 hover:bg-green-700 rounded-lg">Release Payout</button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>

        <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-6">
            <h3 class="font-semibold text-gray-900 dark:text-[#E8EAF6] mb-4">Recently Released</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-[#2A2F58]">
                            <th class="text-left py-2 px-2 font-medium text-gray-600 dark:text-[#8A90B0]">Student</th>
                            <th class="text-right py-2 px-2 font-medium text-gray-600 dark:text-[#8A90B0]">Amount</th>
                            <th class="text-left py-2 px-2 font-medium text-gray-600 dark:text-[#8A90B0]">Released</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($released as $w)
                        <tr class="border-b border-gray-50 dark:border-[#2A2F58]">
                            <td class="py-2 px-2 text-gray-900 dark:text-[#E8EAF6]">{{ $w->student->first_name }} {{ $w->student->last_name }}</td>
                            <td class="py-2 px-2 text-right">₱{{ number_format($w->refund_amount, 2) }}</td>
                            <td class="py-2 px-2 text-xs text-gray-500">{{ $w->refund_processed_at?->format('M d, Y h:i A') ?? '' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="py-6 text-center text-gray-500 text-sm">No payouts released yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
