@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('registrar.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <span class="current">Requests</span>
@endsection

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900 dark:text-[#E8EAF6]">Requests</h2>
    <p class="text-gray-600 dark:text-[#C1C4DC] mt-1">Discount requests to file, withdrawals to decide, promotions to prepare — one queue.</p>
</div>

@if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm">{{ session('error') }}</div>
@endif
@if($errors->any())
    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm">
        <ul class="list-disc list-inside">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif

<div x-data="{ tab: ((v) => ['discount-requests','withdrawals','promotion'].includes(v) ? v : 'discount-requests')(new URLSearchParams(window.location.search).get('view')), setTab(v) { this.tab = v; const u = new URL(window.location.href); if (v === 'discount-requests') { u.searchParams.delete('view'); } else { u.searchParams.set('view', v); } window.history.replaceState({}, '', u); } }">
    <div class="inline-flex gap-1 mb-6 p-1 rounded-xl bg-gray-100 dark:bg-[#23274C]" role="tablist" aria-label="Request type">
        <button @click="setTab('discount-requests')" :class="tab==='discount-requests' ? 'text-white shadow font-semibold' : 'text-gray-500 dark:text-[#8A90B0]'" :style="tab==='discount-requests' ? 'background: var(--navy);' : ''" class="px-4 py-2 text-sm rounded-lg transition" role="tab" :aria-selected="tab==='discount-requests'">Discount Requests @if($badgeCounts['discount-requests'] > 0)<span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-semibold bg-amber-500 text-white">{{ $badgeCounts['discount-requests'] }}</span>@endif</button>
        <button @click="setTab('withdrawals')" :class="tab==='withdrawals' ? 'text-white shadow font-semibold' : 'text-gray-500 dark:text-[#8A90B0]'" :style="tab==='withdrawals' ? 'background: var(--navy);' : ''" class="px-4 py-2 text-sm rounded-lg transition" role="tab" :aria-selected="tab==='withdrawals'">Withdrawals @if($badgeCounts['withdrawals'] > 0)<span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-semibold bg-red-500 text-white">{{ $badgeCounts['withdrawals'] }}</span>@endif</button>
        <button @click="setTab('promotion')" :class="tab==='promotion' ? 'text-white shadow font-semibold' : 'text-gray-500 dark:text-[#8A90B0]'" :style="tab==='promotion' ? 'background: var(--navy);' : ''" class="px-4 py-2 text-sm rounded-lg transition" role="tab" :aria-selected="tab==='promotion'">End-of-Year Promotion @if($badgeCounts['promotion'] > 0)<span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-semibold bg-green-600 text-white">{{ $badgeCounts['promotion'] }}</span>@endif</button>
    </div>

    <div x-show="tab==='discount-requests'" x-cloak>
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-[#E8EAF6]">Discount Requests</h2>
            <p class="text-gray-600 dark:text-[#C1C4DC] mt-1">Request with proof (ESC, Honor, Sibling) → Directress approves → Cashier applies. Direct grants are disabled.</p>
        </div>

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

    <div x-show="tab==='withdrawals'" x-cloak>
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Withdrawal Requests</h2>
            <p class="text-gray-600 mt-1">Review and process student withdrawal requests.</p>
        </div>

        <div x-data="ajaxTable('{{ route('registrar.withdrawals.index') }}', { search: '{{ request('search') }}', status: '{{ request('status') }}' })">
            <div class="mb-4 flex gap-2 flex-wrap items-center">
                <form method="GET" class="flex gap-2 flex-1 flex-wrap" @submit.prevent="reload()">
                    <input type="text" x-model="filters.search" @input="scheduleReload()"
                           placeholder="Search by student name or section..."
                           class="flex-1 min-w-[200px] rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                    <select name="status" x-model="filters.status" @change="reload()" class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                        <option value="All">All Status</option>
                        <option value="Pending">Pending</option>
                        <option value="Approved">Approved</option>
                        <option value="Rejected">Rejected</option>
                    </select>
                    <button type="submit" class="px-4 py-2 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);">Search</button>
                    <button type="button" @click="reset()" class="px-4 py-2 rounded-lg text-sm font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 transition">Clear</button>
                </form>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
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

    <div x-show="tab==='promotion'" x-cloak>
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-[#E8EAF6]">End-of-Year Promotion — Prepare Proposals</h2>
            <p class="text-gray-600 dark:text-[#C1C4DC] mt-1">You propose an action per student. The Principal approves, then the Directress signs off and the system moves the students. Nothing executes from this page.</p>
        </div>

        @if($enrollments->isEmpty())
        <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-6 text-center">
            <p class="text-sm text-gray-500 dark:text-[#8A90B0] py-4">No active enrollments found.</p>
        </div>
        @else
        <form method="POST" action="{{ route('registrar.promotion.propose') }}" onsubmit="return confirm('Send the selected proposals to the Principal for approval?')">
            @csrf
            <div class="mb-5 flex items-center gap-3 flex-wrap bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-4">
                <label class="text-sm font-medium text-gray-700 dark:text-[#C1C4DC] whitespace-nowrap">New School Year:</label>
                <select name="school_year" required class="rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                    <option value="">Select school year</option>
                    @foreach($schoolYears as $sy)
                    <option value="{{ $sy }}">{{ $sy }}</option>
                    @endforeach
                    <option value="{{ date('Y') . '-' . (date('Y') + 1) }}">{{ date('Y') . '-' . (date('Y') + 1) }} (New)</option>
                </select>
                <button type="submit" class="px-6 py-2.5 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">Send Proposals to Principal</button>
            </div>

            @foreach($enrollments as $gradeLevel => $gradeEnrollments)
            <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-6 mb-5">
                <h3 class="font-semibold text-gray-900 dark:text-[#E8EAF6] mb-1">{{ $gradeLevel }} <span class="text-sm font-normal text-gray-500 dark:text-[#8A90B0]">({{ $gradeEnrollments->count() }} student(s))</span></h3>
                <p class="text-xs text-gray-400 dark:text-[#8A90B0] mb-4">GWA ≥ {{ $passingGrade ?? 75 }} and no failing subject (&lt;{{ $passingGrade ?? 75 }}) = qualified. Leave the action blank to skip a student.</p>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[900px] text-sm">
                        <thead>
                            <tr class="border-y border-gray-200 dark:border-[#2A2F58] bg-gray-50 dark:bg-[#161A33]">
                                <th class="text-left py-3 px-4 font-medium text-gray-600 dark:text-[#C1C4DC]">Student</th>
                                <th class="text-left py-3 px-4 font-medium text-gray-600 dark:text-[#C1C4DC]">Section</th>
                                <th class="text-left py-3 px-4 font-medium text-gray-600 dark:text-[#C1C4DC]">GWA</th>
                                <th class="text-left py-3 px-4 font-medium text-gray-600 dark:text-[#C1C4DC]">Qualification</th>
                                <th class="text-left py-3 px-4 font-medium text-gray-600 dark:text-[#C1C4DC]">Proposal Status</th>
                                <th class="text-left py-3 px-4 font-medium text-gray-600 dark:text-[#C1C4DC] w-56">Proposed Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($gradeEnrollments as $enrollment)
                            @php
                                $isGrade12 = $gradeLevel === 'Grade 12';
                                $passing = $passingGrade ?? 75;
                                $grades = $enrollment->grades ?? collect();
                                $grouped = $grades->groupBy('class_id');
                                $finals = $grouped->map(fn($g) => round($g->avg('final_grade'), 2));
                                $avg = $finals->isNotEmpty() ? round($finals->avg(), 2) : null;
                                $failCount = $finals->filter(fn($f) => $f < $passing)->count();
                                $qualified = $avg !== null && $avg >= $passing && $failCount === 0;
                                $open = $openProposals[$enrollment->id] ?? null;
                            @endphp
                            <tr class="border-b border-gray-100 dark:border-[#2A2F58] hover:bg-gray-50 dark:hover:bg-[#1E2447]">
                                <td class="py-3 px-4">
                                    <span class="font-medium text-gray-900 dark:text-[#E8EAF6]">{{ $enrollment->student->first_name }} {{ $enrollment->student->last_name }}</span>
                                    <span class="block text-xs text-gray-400 dark:text-[#8A90B0]">{{ $enrollment->student->student_number }}</span>
                                </td>
                                <td class="py-3 px-4 text-gray-700 dark:text-[#C1C4DC]">{{ $enrollment->section->section_name ?? 'N/A' }}</td>
                                <td class="py-3 px-4">
                                    @if($avg === null)
                                        <span class="text-gray-400 text-xs">No grades</span>
                                    @else
                                        <span class="font-semibold {{ $avg >= $passing ? 'text-gray-900 dark:text-[#E8EAF6]' : 'text-red-600' }}">{{ number_format($avg, 2) }}</span>
                                        @if($failCount > 0)<span class="block text-xs text-red-500">{{ $failCount }} failing</span>@endif
                                    @endif
                                </td>
                                <td class="py-3 px-4">
                                    @if($avg === null)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600 dark:bg-[#23274C] dark:text-[#C1C4DC]">No grades</span>
                                    @elseif($qualified)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300">Qualified</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300">Not qualified</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4">
                                    @if($open)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700">{{ ucfirst(str_replace('_', ' ', $open->status)) }}</span>
                                    @else
                                        <span class="text-xs text-gray-400">—</span>
                                    @endif
                                    @if(!empty($holdMap[$enrollment->student_id] ?? []))
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300 mt-1" title="@foreach($holdMap[$enrollment->student_id] as $h)[{{ ucfirst($h['source']) }}] {{ $h['reason'] }}&#10;@endforeach">HOLD ({{ count($holdMap[$enrollment->student_id]) }})</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4">
                                    @if($open)
                                        <span class="text-xs text-gray-400">Locked by open proposal</span>
                                    @else
                                        <div class="flex flex-col gap-1.5" x-data="{ act: '' }">
                                        <select name="actions[{{ $enrollment->id }}]" @change="act = $event.target.value" class="w-full rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-2 py-1.5 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                                            <option value="">Skip</option>
                                            @if(!$isGrade12)
                                            <option value="promote" @if($qualified) selected @endif>Promote</option>
                                            <option value="retain" @if(!$qualified && $avg !== null) selected @endif>Retain</option>
                                            @endif
                                            <option value="graduate" {{ $isGrade12 ? 'selected' : '' }}>Graduate</option>
                                            <option value="transfer">Transfer Out</option>
                                            <option value="dropped">Dropped Out</option>
                                        </select>
                                        <input type="text" name="reasons[{{ $enrollment->id }}]" placeholder="Reason (required for Transfer/Dropped)" x-show="act === 'transfer' || act === 'dropped'" x-cloak class="w-full rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-2 py-1.5 text-xs focus:ring-2 focus:ring-blue-500 outline-none" maxlength="500">
                                        </div>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endforeach
        </form>
        @endif
    </div>
</div>
@endsection
