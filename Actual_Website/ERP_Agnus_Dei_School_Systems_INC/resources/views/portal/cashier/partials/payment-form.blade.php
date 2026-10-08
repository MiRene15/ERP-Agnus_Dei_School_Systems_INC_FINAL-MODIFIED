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
@endphp
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-semibold text-gray-900 mb-4">Process Payment</h3>
            <form method="POST" action="{{ route('cashier.payment.process', $student) }}" enctype="multipart/form-data"
                  x-data="{ discountType: '{{ $discountAlreadyApplied ? ($student->ledger->discount_type ?? 'other') : ($autoDiscountType ?? '') }}', totalAssessed: {{ $totalAssessed }}, totalPaid: {{ $student->ledger ? $student->ledger->total_paid : 0 }}, discountAmount: {{ $discountAlreadyApplied ? $student->ledger->discount_applied : $autoDiscountAmount }}, discountPercent: {{ $discountAlreadyApplied ? round($student->ledger->discount_applied / max($totalAssessed, 1) * 100) : (($autoDiscountType === 'honor') ? 10 : (($autoDiscountType === 'sibling') ? 5 : 0)) }}, isAutoDiscount: {{ ($autoDiscountType && !$discountAlreadyApplied) ? 'true' : 'false' }}, autoDiscountLabel: '{{ $autoDiscountType === 'esc' ? 'ESC Grant (Tuition Waived)' : ($autoDiscountType === 'honor' ? 'Honor Discount (10%)' : ($autoDiscountType === 'sibling' ? 'Sibling Discount (5%)' : '')) }}' }">
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
                            <select name="payment_plan" required
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
                        @error('amount_paid') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
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
                        Process Payment
                    </button>
                </div>
            </form>
        </div>
