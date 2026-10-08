@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ auth()->user()->role_id === 3 ? route('cashier.dashboard') : route('registrar.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <span class="current">Discount Requests</span>
@endsection

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900 dark:text-[#E8EAF6]">Discount Requests</h2>
    <p class="text-gray-600 dark:text-[#C1C4DC] mt-1">Request with proof (ESC, Honor, Sibling, Other) → Directress approves → discount posts by itself. Direct grants are disabled.</p>
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
                @foreach(\App\Models\DiscountRequest::DISCOUNT_PERCENTS as $pct)
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

<div x-data="ajaxTable('{{ route('discount-requests.index') }}', { search: '{{ request('search') }}', status: '{{ request('status') }}', school_year: '{{ request('school_year', active_school_year()) }}' })">
    <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-gray-900 dark:text-[#E8EAF6]">Search Discount Requests</h3>
        </div>
    <div class="flex gap-2 flex-wrap items-center">
        <form method="GET" class="flex gap-2 flex-1 flex-wrap" @submit.prevent="reload()">
            <select name="school_year" x-model="filters.school_year" @change="reload()" class="rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                @foreach($schoolYears as $sy)
                    <option value="{{ $sy }}" {{ $sy === (request('school_year', active_school_year())) ? 'selected' : '' }}>{{ $sy }}</option>
                @endforeach
            </select>
            <input type="text" x-model="filters.search" @input="scheduleReload()"
                   placeholder="Search by student name or type..."
                   class="flex-1 min-w-[200px] rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
            <select name="status" x-model="filters.status" @change="reload()" class="rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                <option value="All">All Status</option>
                <option value="Pending">Pending</option>
                <option value="Approved">Approved</option>
                <option value="Rejected">Rejected</option>
                <option value="Applied">Applied</option>
            </select>
            <button type="submit" class="px-4 py-2 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);">Search</button>
            <button type="button" @click="reset()" class="px-4 py-2 rounded-lg text-sm font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 transition">Clear</button>
        </form>
    </div>
    </div>

    <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-6">
        <h3 class="font-semibold text-gray-900 dark:text-[#E8EAF6] mb-4">All Requests</h3>
        <div x-show="loading && !html" class="space-y-3">
            <template x-for="i in 3" :key="i">
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
        <div x-show="(loading || isRateLimited) && html" x-cloak class="mb-2 px-3 py-2 rounded-lg bg-amber-50 dark:bg-[rgba(245,158,11,0.12)] border border-amber-200 dark:border-[rgba(245,158,11,0.3)] text-xs text-amber-800 dark:text-[#FCD34D]">Showing results for &quot;<span class="font-semibold" x-text="displayedSearch"></span>&quot; &mdash; searching for &quot;<span class="font-semibold" x-text="filters.search"></span>&quot;&hellip;</div>
        <div x-show="html || !loading" x-cloak x-html="html" :class="((loading || isRateLimited) && html) ? 'opacity-50 transition-opacity' : 'opacity-100 transition-opacity'"></div>
    </div>
</div>
@endsection
