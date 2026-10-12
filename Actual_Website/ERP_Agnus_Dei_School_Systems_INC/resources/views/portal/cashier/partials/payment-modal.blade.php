<div id="payment-modal" class="fixed inset-0 hidden items-center justify-center p-4" style="z-index: 110;">
    <div class="absolute inset-0 bg-black/50" style="backdrop-filter: blur(4px);" onclick="closePaymentModal()"></div>
    <div class="relative bg-white dark:bg-[#1A1E3B] rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] my-auto border border-gray-100 dark:border-[#2A2F58] flex flex-col overflow-hidden">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-[#2A2F58] flex-shrink-0">
            <h3 class="text-lg font-bold text-gray-900 dark:text-[#E8EAF6]" id="pm-title">Process Payment</h3>
            <button onclick="closePaymentModal()" class="text-2xl text-gray-400 hover:text-gray-700 leading-none">&times;</button>
        </div>
        <div id="pm-body" class="p-6 overflow-y-auto"></div>
    </div>
</div>

<style>
@keyframes payment-spin {
    from { transform: rotate(0deg); }
    to   { transform: rotate(360deg); }
}
.payment-spinner {
    width: 48px;
    height: 48px;
    border: 4px solid #e5e7eb;
    border-top-color: var(--navy, #1e2a5a);
    border-radius: 50%;
    animation: payment-spin 0.8s linear infinite;
}
</style>

<script>
var paymentModalAbort = null;

async function openPaymentModal(studentId, studentName, schoolYear) {
    var modal = document.getElementById('payment-modal');
    if (!modal) return;
    document.body.appendChild(modal);
    modal.dataset.studentId = studentId;
    modal.dataset.studentName = studentName || '';
    modal.dataset.schoolYear = schoolYear || '';
    document.getElementById('pm-title').textContent = 'Process Payment — ' + (studentName || '');
    document.getElementById('pm-body').innerHTML = '<div class="space-y-3"><div class="skelly sk-line-md"></div><div class="skelly sk-line-lg"></div><div class="skelly sk-line-md"></div><div class="skelly sk-line-sm"></div></div>';
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    if (paymentModalAbort) { try { paymentModalAbort.abort(); } catch (e) {} }
    paymentModalAbort = new AbortController();
    try {
        var yearParam = (schoolYear || modal.dataset.schoolYear || '');
        var url = '/cashier/payment/' + studentId + '?ajax=1' + (yearParam ? '&school_year=' + encodeURIComponent(yearParam) : '');
        const res = await fetch(url, { signal: paymentModalAbort.signal, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
        if (!res.ok) throw new Error('HTTP ' + res.status);
        const data = await res.json();
        if (String(modal.dataset.studentId) !== String(studentId)) return;
        var body = document.getElementById('pm-body');
        body.innerHTML = data.html || '';
        if (window.Alpine && typeof window.Alpine.initTree === 'function') {
            try { window.Alpine.initTree(body); } catch (e) { /* noop */ }
        }
    } catch (e) {
        if (e && e.name === 'AbortError') return;
        document.getElementById('pm-body').innerHTML = '<div class="p-3 rounded-lg bg-red-50 border border-red-200 text-sm text-red-700 flex items-center justify-between gap-3"><span>Could not load the payment form.</span><button type="button" onclick="openPaymentModal(document.getElementById(\'payment-modal\').dataset.studentId, document.getElementById(\'payment-modal\').dataset.studentName)" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-white border border-red-200 hover:bg-red-100">Retry</button></div>';
    }
}

function closePaymentModal() {
    var modal = document.getElementById('payment-modal');
    if (!modal) return;
    if (paymentModalAbort) { try { paymentModalAbort.abort(); } catch (e) {} }
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
        var modal = document.getElementById('payment-modal');
        if (modal && !modal.classList.contains('hidden')) closePaymentModal();
        var psModal = document.getElementById('payment-success-modal');
        if (psModal && !psModal.classList.contains('hidden')) closePaymentSuccessModal();
    }
});

// (removed duplicate openPaymentSuccessModal — see the active definition below)

function closePaymentSuccessModal() {
    var modal = document.getElementById('payment-success-modal');
    if (!modal) return;
    if (paymentSuccessModalAbort) { try { paymentSuccessModalAbort.abort(); } catch (e) {} }
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

document.addEventListener('submit', function (event) {
    var modal = document.getElementById('payment-modal');
    if (!modal || modal.classList.contains('hidden')) return;
    var form = event.target;
    if (!(form instanceof HTMLFormElement) || !modal.contains(form)) return;
    event.preventDefault();
    var submitter = event.submitter || form.querySelector('button[type="submit"], input[type="submit"]');
    if (submitter && submitter.disabled) return;
    if (submitter) {
        submitter.disabled = true;
        submitter.setAttribute('aria-disabled', 'true');
        submitter.classList.add('is-busy');
    }
    // Show Loading Modal on top of every other modal
    var loadingModal = document.getElementById('loading-payment-modal');
    if (loadingModal) {
        loadingModal.classList.remove('hidden');
        loadingModal.classList.add('flex');
    }
    // Hide the payment modal body while processing
    var paymentModal = document.getElementById('payment-modal');
    if (paymentModal) {
        paymentModal.classList.add('hidden');
        paymentModal.classList.remove('flex');
    }
    var release = function () {
        if (submitter) {
            submitter.disabled = false;
            submitter.removeAttribute('aria-disabled');
            submitter.classList.remove('is-busy');
        }
    };
    try {
        var keyInput = form.querySelector('input[name="_idempotency_key"]');
        if (!keyInput) {
            keyInput = document.createElement('input');
            keyInput.type = 'hidden';
            keyInput.name = '_idempotency_key';
            form.appendChild(keyInput);
        }
        keyInput.value = (window.crypto && typeof window.crypto.randomUUID === 'function')
            ? window.crypto.randomUUID()
            : 'key-' + Date.now().toString(36) + '-' + Math.random().toString(36).slice(2, 10);
    } catch (e) { /* noop: minting must never block a submit */ }
    var csrf = '';
    try {
        var meta = document.querySelector('meta[name="csrf-token"]');
        csrf = meta ? meta.getAttribute('content') : '';
    } catch (e) { /* noop */ }
    fetch(form.action, {
        method: 'POST',
        body: new FormData(form),
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf }
    }).then(function (res) {
        return res.json().then(function (data) { return { res: res, data: data }; }).catch(function () { return { res: res, data: {} }; });
    }).then(function (out) {
        // Hide loading modal and payment modal
        var loadingModal = document.getElementById('loading-payment-modal');
        if (loadingModal) {
            loadingModal.classList.add('hidden');
            loadingModal.classList.remove('flex');
        }
        var paymentModal = document.getElementById('payment-modal');
        if (paymentModal) {
            paymentModal.classList.add('hidden');
            paymentModal.classList.remove('flex');
        }
        var ok = out.res.ok && out.data && out.data.success;
        var message = (out.data && (out.data.message || (out.data.errors && Object.values(out.data.errors)[0] && Object.values(out.data.errors)[0][0]))) || (ok ? 'Saved.' : 'Action failed.');
        release();
        if (!ok) {
            // Re-show the payment modal so the user can see the error
            var paymentModal = document.getElementById('payment-modal');
            if (paymentModal) {
                paymentModal.classList.remove('hidden');
                paymentModal.classList.add('flex');
            }
            var pmBody = document.getElementById('pm-body');
            var errBox = document.createElement('div');
            errBox.className = 'mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm';
            errBox.textContent = message;
            pmBody.prepend(errBox);
            errBox.scrollIntoView({ block: 'nearest' });
            return;
        }
        // Show Payment Success Modal
        openPaymentSuccessModal(
            out.data.receipt_number ?? '',
            out.data.amount ?? 0,
            out.data.library_amount ?? 0,
            out.data.credited ?? 0,
            out.data.student_name ?? '',
            out.data.print_url ?? '',
            out.data.balance_after,
            out.data.percent_paid
        );
        var finModal = document.getElementById('financial-modal');
        if (finModal && !finModal.classList.contains('hidden')) {
            openFinancialModal(finModal.dataset.studentId, finModal.dataset.studentName, finModal.dataset.year, { type: 'ok', text: message, link: out.data.print_url || null, linkLabel: 'Print Receipt' });
        }
    }).catch(function () {
        // Hide loading modal
        var loadingModal = document.getElementById('loading-payment-modal');
        if (loadingModal) {
            loadingModal.classList.add('hidden');
            loadingModal.classList.remove('flex');
        }
        // Re-show the payment modal so the user can see the error
        var paymentModal = document.getElementById('payment-modal');
        if (paymentModal) {
            paymentModal.classList.remove('hidden');
            paymentModal.classList.add('flex');
        }
        var pmBody = document.getElementById('pm-body');
        var box = document.createElement('div');
        box.className = 'mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm';
        box.textContent = 'Action failed — check your connection and retry.';
        pmBody.prepend(box);
        release();
    });
});

// Payment Success Modal
var paymentSuccessModalAbort = null;
function formatDate(date) {
    var d = new Date(date);
    return d.getFullYear() + '-' + ('0' + (d.getMonth() + 1)).slice(-2) + '-' + ('0' + d.getDate()).slice(-2);
}
function formatPeso(n) {
    var v = parseFloat(n);
    if (isNaN(v)) v = 0;
    return '₱' + v.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
function openPaymentSuccessModal(receiptNumber, amountPaid, libraryAmount, creditedExcess, studentName, printUrl, balanceAfter, percentPaid) {
    var modal = document.getElementById('payment-success-modal');
    if (!modal) return;
    // Populate the modal content (spec: payment-success-modal-fix.md — true ledger numbers, never fixed 100%/0%)
    var amt = parseFloat(amountPaid);
    if (isNaN(amt)) amt = 0;
    var lib = parseFloat(libraryAmount);
    if (isNaN(lib)) lib = 0;
    var cred = parseFloat(creditedExcess);
    if (isNaN(cred)) cred = 0;
    var bal = (balanceAfter !== undefined && balanceAfter !== null && balanceAfter !== '') ? parseFloat(balanceAfter) : null;
    if (bal !== null && isNaN(bal)) bal = null;
    var pct = (percentPaid !== undefined && percentPaid !== null && percentPaid !== '') ? parseInt(percentPaid, 10) : NaN;
    if (isNaN(pct)) pct = (bal !== null) ? (bal <= 0 ? 100 : 0) : (amt > 0 ? 100 : 0);
    pct = Math.max(0, Math.min(100, pct));
    if (bal !== null && bal <= 0) pct = 100;
    document.getElementById('ps-amount').textContent = formatPeso(amt);
    var receiptNum = receiptNumber || 'RCP-' + formatDate(new Date()) + '-' + ('0000' + (Math.random()*10000|0)).slice(-4);
    document.getElementById('ps-receipt').textContent = 'Receipt Number: ' + receiptNum;
    var balEl = document.getElementById('ps-balance');
    if (balEl) {
        if (bal !== null) {
            balEl.textContent = 'Remaining balance: ' + formatPeso(bal);
            balEl.style.display = '';
        } else {
            balEl.style.display = 'none';
        }
    }
    document.getElementById('ps-progress').style.width = pct + '%';
    document.getElementById('ps-percent').textContent = pct + '% paid';
    var badge = document.getElementById('ps-badge');
    if (badge) badge.style.display = (bal !== null && bal <= 0) ? '' : 'none';
    var libEl = document.getElementById('ps-library');
    if (libEl) {
        if (lib > 0) {
            libEl.textContent = 'Library dues on this receipt: ' + formatPeso(lib);
            libEl.style.display = '';
        } else {
            libEl.style.display = 'none';
        }
    }
    var subLabel = document.getElementById('ps-sublabel');
    if (subLabel) subLabel.style.display = lib > 0 ? '' : 'none';
    var credEl = document.getElementById('ps-credited');
    if (credEl) {
        if (cred > 0) {
            credEl.textContent = formatPeso(cred) + ' credited to next term.';
            credEl.style.display = '';
        } else {
            credEl.style.display = 'none';
        }
    }
    // Set the print receipt button URL from the server response
    var printBtn = document.getElementById('ps-print');
    if (printBtn) {
        printBtn.onclick = function () { window.open(printUrl || '', '_blank'); };
    }
    modal.dataset.receiptNumber = receiptNumber;
    modal.dataset.amountPaid = amountPaid;
    modal.dataset.libraryAmount = libraryAmount;
    modal.dataset.creditedExcess = creditedExcess;
    modal.dataset.studentName = studentName || '';
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    if (window.Alpine && typeof window.Alpine.initTree === 'function') {
        try { window.Alpine.initTree(modal); } catch (e) { /* noop */ }
    }
}

// Close Loading Payment Modal
function closeLoadingPaymentModal() {
    var modal = document.getElementById('loading-payment-modal');
    if (!modal) return;
    if (loadingModalAbort) { try { loadingModalAbort.abort(); } catch (e) {} }
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

// Ensure the modal is hidden until opened
var psModal = document.getElementById('payment-success-modal');
if (psModal) psModal.classList.add('hidden');

// Payment Success Modal HTML skeleton (inserted body-first so it exists in the DOM)
var psModalHTML = document.createElement('div');
psModalHTML.id = 'payment-success-modal';
psModalHTML.className = 'fixed inset-0 hidden items-center justify-center p-4';
psModalHTML.style.zIndex = '120';
psModalHTML.innerHTML = '<div class="absolute inset-0 bg-black/80" style="backdrop-filter: blur(4px);" onclick="closePaymentSuccessModal()"></div>' +
    '<div class="relative bg-white dark:bg-[#1A1E3B] rounded-2xl shadow-2xl w-full max-w-md max-h-[90vh] my-auto border border-gray-100 dark:border-[#2A2F58] flex flex-col overflow-hidden">' +
    '    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-[#2A2F58] flex-shrink-0">' +
    '        <h3 class="text-lg font-bold text-gray-900 dark:text-[#E8EAF6]">Payment Successful</h3>' +
    '        <button onclick="closePaymentSuccessModal()" class="text-2xl text-gray-400 hover:text-gray-700 leading-none">&times;</button>' +
    '    </div>' +
    '    <div class="p-6 flex flex-col items-center gap-4">' +
    '        <div class="text-3xl font-bold text-green-600" id="ps-amount">₱0.00</div>' +
    '        <p class="text-gray-600 text-sm" id="ps-receipt">Receipt Number: --</p>' +
    '        <p class="text-gray-700 text-sm font-medium" id="ps-balance">Remaining balance: ₱0.00</p>' +
    '        <span id="ps-badge" class="text-xs font-semibold px-2 py-1 rounded-full bg-green-100 text-green-700" style="display: none;">Fully Paid</span>' +
    '        <p class="text-gray-500 text-xs" id="ps-sublabel">Tuition + Library Dues</p>' +
    '        <p class="text-gray-500 text-xs" id="ps-library" style="display: none;"></p>' +
    '        <p class="text-green-600 text-xs" id="ps-credited" style="display: none;"></p>' +
    '        <div class="w-full bg-gray-100 dark:bg-[#2A2F58] rounded-full h-2">' +
    '            <div class="bg-green-600 rounded-full h-2 transition-width" style="width: 100%" id="ps-progress"></div>' +
    '        </div>' +
    '        <p class="text-gray-500 text-xs" id="ps-percent">100% paid</p>' +
    '        <div class="w-full space-y-3 mt-4">' +
    '            <button onclick="window.open(\'\', \'_blank\')" id="ps-print" class="w-full px-4 py-2 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);" onmouseover="this.style.opacity=\'0.9\'" onmouseout="this.style.opacity=\'1\'">Print Receipt</button>' +
    '            <button onclick="closePaymentSuccessModal()" class="w-full bg-gray-200 dark:bg-[#2A2F58] text-gray-800 dark:text-gray-200 py-2 rounded-lg text-sm hover:bg-gray-300 transition-colors">Close</button>' +
    '        </div>' +
    '    </div>' +
    '</div>';
document.body.insertBefore(psModalHTML, document.body.firstChild);

// Loading Modal HTML skeleton (inserted body-first so it exists in the DOM, high z-index)
var loadingModalHTML = document.createElement('div');
loadingModalHTML.id = 'loading-payment-modal';
loadingModalHTML.className = 'fixed inset-0 hidden items-center justify-center p-4';
loadingModalHTML.style.zIndex = '130';
loadingModalHTML.innerHTML = '<div class="absolute inset-0 bg-black/80" style="backdrop-filter: blur(4px);"></div>' +
    '<div class="relative bg-white dark:bg-[#1A1E3B] rounded-2xl shadow-2xl w-full max-w-md max-h-[90vh] my-auto border border-gray-100 dark:border-[#2A2F58] flex flex-col overflow-hidden">' +
    '    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-[#2A2F58] flex-shrink-0">' +
    '        <h3 class="text-lg font-bold text-gray-900 dark:text-[#E8EAF6]">Processing Payment</h3>' +
    '        <button onclick="closeLoadingPaymentModal()" class="text-2xl text-gray-400 hover:text-gray-700 leading-none">&times;</button>' +
    '    </div>' +
    '    <div class="p-8 flex flex-col items-center gap-4">' +
    '        <div class="payment-spinner mb-4"></div>' +
    '        <span class="text-gray-700 text-xl font-medium">Processing Payment…</span>' +
    '        <p class="text-gray-500 text-sm">Your payment is being processed. This may take a few seconds.</p>' +
    '    </div>' +
    '</div>';
document.body.insertBefore(loadingModalHTML, document.body.firstChild);
</script>
