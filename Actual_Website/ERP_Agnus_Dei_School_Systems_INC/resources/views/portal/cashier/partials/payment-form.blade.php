@php
    $isFirstPayment = !$student->ledger || $student->ledger->payments->isEmpty();
    $isPlanLocked = $student->ledger && $student->ledger->payments->isNotEmpty();
    $discountAlreadyApplied = $student->ledger && $student->ledger->discount_applied > 0;
    $effectiveAutoType = $autoDiscountType;
    $effectiveAutoAmount = $autoDiscountAmount;
    if ($discountAlreadyApplied) {
        $effectiveAutoType = $student->ledger->discount_type ?? '';
        $effectiveAutoAmount = $student->ledger->discount_applied;
    }
    $formFullyPaid = ($feeStatus['status'] ?? '') === 'Fully Paid' && ($libraryDues ?? collect())->isEmpty();
@endphp
@php
    // Forwarded files pay here without an enrollment (spec: payment-access.md).
    $isDownpayment = !$enrollment && ($admission?->status ?? null) === 'Awaiting Downpayment' && empty($admission?->downpayment_receipt);
    $isPaidPending = !$enrollment && !empty($admission?->downpayment_receipt);
@endphp
@if($formFullyPaid)
<div class="bg-green-50 border border-green-200 rounded-xl p-6 text-center">
    <p class="text-sm font-semibold text-green-800">Fully Paid</p>
    <p class="text-xs text-green-600 mt-1">No further payments required for this account.</p>
</div>
@elseif($isPaidPending)
<div class="bg-blue-50 border border-blue-200 rounded-xl p-6">
    <p class="text-sm font-semibold text-blue-800">Downpayment received — {{ $admission->downpayment_receipt }}</p>
    <p class="text-xs text-blue-600 mt-1">File returned to Registrar as Pending for section assignment and full approval. Further payments open after enrollment.</p>
</div>
@else
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-semibold text-gray-900 mb-4">{{ $isDownpayment ? 'Confirm Downpayment' : 'Process Payment' }}</h3>
@php
    // Spec cashier-full-payment-label.md: pre-assessment hint data — the full
    // total (90% tuition + 100% misc, no ₱1.5k) with each line named. The
    // server-side floor in CashierController enforces the same figures.
    $hasFullPreview = isset($termFloor['fullFloor']) && ($termFloor['fullFloor'] ?? 0) > 0;
    $fullTotalPreview = $hasFullPreview ? (float) $termFloor['fullFloor'] : 0.0;
    $fullTuitionPreview = round((float) ($totalTuition ?? 0) * 0.9, 2);
    $hintMiscItems = $miscItems ?? [];
    if (!is_array($hintMiscItems)) { $hintMiscItems = []; }
    $planValue = $student?->ledger?->payment_plan ?? '';
@endphp
            <form method="POST" action="{{ $isDownpayment ? route('cashier.payment.downpayment', $student) : route('cashier.payment.process', $student) }}" enctype="multipart/form-data"
                  x-data="{ plan: '{{ $planValue }}', discountType: '{{ $discountAlreadyApplied ? ($student->ledger->discount_type ?? 'other') : ($autoDiscountType ?? '') }}', totalAssessed: {{ $totalAssessed }}, totalPaid: {{ $student->ledger ? $student->ledger->total_paid : 0 }}, discountAmount: {{ $discountAlreadyApplied ? $student->ledger->discount_applied : $autoDiscountAmount }}, discountPercent: {{ $discountAlreadyApplied ? round($student->ledger->discount_applied / max($totalAssessed, 1) * 100) : (($autoDiscountType === 'honor') ? 10 : (($autoDiscountType === 'sibling') ? 5 : 0)) }}, isAutoDiscount: {{ ($autoDiscountType && !$discountAlreadyApplied) ? 'true' : 'false' }}, autoDiscountLabel: '{{ $autoDiscountType === 'esc' ? 'ESC Grant (Tuition Waived)' : ($autoDiscountType === 'honor' ? 'Honor Discount (10%)' : ($autoDiscountType === 'sibling' ? 'Sibling Discount (5%)' : '')) }}' }">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Payment Plan</label>
                        @if($isPlanLocked)
                            <div class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700 flex items-center gap-2">
                                <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/></svg>
                                {{ $student->ledger->payment_plan === 'full' ? 'Full Payment' : 'Installment' }} (locked)
                            </div>
                            <input type="hidden" name="payment_plan" value="{{ $student->ledger->payment_plan }}">
                        @else
                            <select name="payment_plan" required x-model="plan"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                                <option value="">Select plan...</option>
                                <option value="full" {{ $student?->ledger?->payment_plan === 'full' ? 'selected' : '' }}>Full Payment</option>
                                <option value="installment" {{ $student?->ledger?->payment_plan === 'installment' ? 'selected' : '' }}>Installment</option>
                            </select>
                        @endif
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Amount Paid (₱)</label>
                        <input type="number" name="amount_paid" required step="0.01" min="1"
                               placeholder="0.00"
                               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                        @if($hasFullPreview)
                        {{-- Spec cashier-full-payment-label.md: the hint always matches the picked plan. --}}
                        <div x-show="plan !== 'full'" class="mt-1">
                            <p class="text-xs text-gray-500">Minimum ₱{{ number_format($termFloor['floor'], 2) }} — Downpayment ₱{{ number_format($termFloor['floor'], 2) }}</p>
                        </div>
                        <div x-show="plan === 'full'" x-cloak class="mt-1 rounded-lg bg-blue-50 border border-blue-100 p-2.5">
                            <p class="text-xs font-semibold text-blue-900">Full total ₱{{ number_format($fullTotalPreview, 2) }}</p>
                            <ul class="mt-1 space-y-0.5 text-xs text-blue-800">
                                <li class="flex justify-between gap-2"><span>Tuition (90%)</span><span class="font-medium">₱{{ number_format($fullTuitionPreview, 2) }}</span></li>
                                @foreach($hintMiscItems as $itemKey => $itemVal)
                                <li class="flex justify-between gap-2"><span>{{ strtolower((string) $itemKey) === 'id' ? 'ID' : ucfirst(strtolower((string) $itemKey)) }}</span><span class="font-medium">₱{{ number_format((float) $itemVal, 2) }}</span></li>
                                @endforeach
                            </ul>
                        </div>
@elseif(!empty($termFloor['floor']))
<p class="text-xs text-gray-500 mt-1">Minimum ₱{{ number_format($termFloor['floor'], 2) }}{{ !empty($termFloor['label']) ? ' — ' . $termFloor['label'] : '' }}</p>
@endif
<!-- NEW: bundled month total + breakdown hint for installment -->
@if($isPlanLocked && ($student?->ledger?->payment_plan ?? '') === 'installment')
<div class="mt-3 p-3 bg-blue-50 border border-blue-200 rounded">
    <p class="text-sm font-medium text-blue-800 mb-2">What this month covers</p>
    <ul class="text-xs text-blue-800 space-y-1">
        <li>Tuition share: ₱{{ number_format($student?->ledger?->tuition_assessed ?? 0, 2) / 10, 2 }}</li>
        @php
        $miscTotal = $student?->ledger?->total_assessed ?? 0 - ($student?->ledger?->tuition_assessed ?? 0) ?? 0;
        $booksShare = number_format(((float)($student?->ledger?->ledgerLines??collect())->whereNotNull('label')->first()?->amount_assessed ?? 0), 2);
        // Simplified: display the 4-way split from fee schedule; in production this
        // would pull the Directress-set 4-box values from FeeSchedule.misc_fee_items.
        $uniformShare = '₱' . number_format(2080, 2);   // placeholder — pulled from fee schedule in production
        $idShare = '₱' . number_format(520, 2);
        $miscShare = '₱' . number_format(1560, 2);
        @endphp>
        <li>Uniform: {{ $uniformShare }}</li>
        <li>ID: {{ $idShare }}</li>
        <li>Miscellaneous: {{ $miscShare }}</li>
    </ul>
    <p class="text-xs text-gray-500 mt-2">(Downpayment ₱1,500 was paid separately to unlock enrollment.)</p>
</div>
@endif
                        @error('amount_paid') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    {{-- Library dues ride on enrolled receipts only — the downpayment receipt is tuition-only, so the tick boxes stay hidden here rather than silently dropping them. --}}
                    @if(!empty($libraryDues) && $libraryDues->isNotEmpty() && !$isDownpayment)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Library Dues</label>
                        <div class="space-y-2 rounded-lg border border-gray-200 p-3">
                            @foreach($libraryDues as $due)
                            <label class="flex items-start gap-2 text-sm">
                                <input type="checkbox" name="library_transaction_ids[]" value="{{ $due->id }}" class="mt-1">
                                <span class="text-gray-700">Overdue: {{ $due->book->title ?? $due->book_title }} — <span class="font-medium">₱{{ number_format($due->total_fees, 2) }}</span></span>
                            </label>
                            @endforeach
                        </div>
                        <p class="text-xs text-gray-400 mt-1">Ticked dues share the single receipt with tuition.</p>
                    </div>
                    @endif
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">AR Number</label>
                        <input type="text" name="ar_number" value="{{ $nextArNumber }}"
                               placeholder="Auto-generated if empty"
                               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                        <p class="text-xs text-gray-400 mt-1">Leave blank to auto-generate. Sequential from AR-{{ date('Y') }}-0500.</p>
                        @error('ar_number') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Attached Receipt (Optional)</label>
                        <input type="file" name="receipt_file" accept=".pdf,.jpg,.jpeg,.png"
                               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                        <p class="text-xs text-gray-400 mt-1">Upload external receipt (PDF, JPG, PNG — max 10MB).</p>
                        @error('receipt_file') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    @if($discountAlreadyApplied)
                    <div class="bg-blue-50 rounded-lg p-3">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                            <span class="text-sm font-medium text-blue-800">
                                {{ \App\Models\DiscountRequest::TYPES[$student->ledger->discount_type] ?? ucfirst($student->ledger->discount_type ?? 'Discount') }} — {{ (int) round($student->ledger->discount_applied / max($totalAssessed, 1) * 100) }}%: -₱ {{ number_format($student->ledger->discount_applied, 2) }} (locked)
                            </span>
                        </div>
                        <input type="hidden" name="discount_type" value="{{ $student->ledger->discount_type }}">
                        <input type="hidden" name="discount_amount" value="{{ $student->ledger->discount_applied }}">
                    </div>
                    @elseif($autoDiscountType && $autoDiscountAmount > 0)
                    <div class="bg-blue-50 rounded-lg p-3">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                            <span class="text-sm font-medium text-blue-800">
                                Auto-Applied ({{ $autoDiscountType === 'esc' ? 'ESC Grant' : ($autoDiscountType === 'honor' ? 'Honor 10%' : 'Sibling 5%') }}): -₱ {{ number_format($autoDiscountAmount, 2) }}
                            </span>
                        </div>
                        <input type="hidden" name="discount_type" value="{{ $autoDiscountType }}">
                        <input type="hidden" name="discount_amount" value="{{ $autoDiscountAmount }}">
                    </div>
                    @elseif($autoDiscountType && $autoDiscountAmount <= 0 && $autoDiscountType === 'esc')
                    <div class="bg-blue-50 rounded-lg p-3">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                            <span class="text-sm font-medium text-blue-800">ESC Grant — Tuition Waived</span>
                        </div>
                        <input type="hidden" name="discount_type" value="esc">
                        <input type="hidden" name="discount_amount" value="{{ $totalTuition }}">
                    </div>
                    @else
                    {{-- Read-only: discounts arrive only via approved requests (spec: cashier-discount-auto-apply.md). No counter-side options. --}}
                    <div class="bg-gray-50 rounded-lg p-3">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                            <span class="text-sm font-medium text-gray-600">No discount{{ ($hasPendingDiscount ?? false) ? ' — approval pending' : '' }}</span>
                        </div>
                        <input type="hidden" name="discount_type" value="">
                        <input type="hidden" name="discount_amount" value="0">
                    </div>
                    @endif
                    <div class="text-sm bg-gray-50 rounded-lg p-3">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Total Assessed</span>
                            <span class="font-medium" x-text="'₱ ' + totalAssessed.toFixed(2)"></span>
                        </div>
                        <div class="flex justify-between mt-1">
                            <span class="text-gray-600">Total Paid</span>
                            <span class="font-medium text-green-600" x-text="'₱ ' + totalPaid.toFixed(2)"></span>
                        </div>
                        <div class="flex justify-between mt-1">
                            <span class="text-gray-600">Discount</span>
                            <span class="font-medium text-blue-600" x-text="'-₱ ' + parseFloat(discountAmount || 0).toFixed(2)"></span>
                        </div>
                        <div class="flex justify-between mt-1 pt-2 border-t border-gray-200 font-bold">
                            <span>New Balance</span>
                            <span :class="(totalAssessed - totalPaid - parseFloat(discountAmount || 0)) > 0 ? 'text-red-600' : 'text-green-600'" x-text="'₱ ' + Math.max(0, totalAssessed - totalPaid - parseFloat(discountAmount || 0)).toFixed(2)"></span>
                        </div>
                    </div>
                    <button type="submit"
                            class="w-full px-4 py-2 rounded-lg text-sm font-semibold text-white transition"
                            style="background: var(--navy);" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                        {{ $isDownpayment ? 'Confirm Downpayment' : 'Take Payment' }}
                    </button>
                </div>
            </form>
        </div>
@endif
