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

<script>
var paymentModalAbort = null;

async function openPaymentModal(studentId, studentName) {
    var modal = document.getElementById('payment-modal');
    if (!modal) return;
    document.body.appendChild(modal);
    modal.dataset.studentId = studentId;
    modal.dataset.studentName = studentName || '';
    document.getElementById('pm-title').textContent = 'Process Payment — ' + (studentName || '');
    document.getElementById('pm-body').innerHTML = '<div class="space-y-3"><div class="skelly sk-line-md"></div><div class="skelly sk-line-lg"></div><div class="skelly sk-line-md"></div><div class="skelly sk-line-sm"></div></div>';
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    if (paymentModalAbort) { try { paymentModalAbort.abort(); } catch (e) {} }
    paymentModalAbort = new AbortController();
    try {
        const res = await fetch('/cashier/payment/' + studentId + '?ajax=1', { signal: paymentModalAbort.signal, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
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
    }
});

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
        var ok = out.res.ok && out.data && out.data.success;
        var message = (out.data && (out.data.message || (out.data.errors && Object.values(out.data.errors)[0] && Object.values(out.data.errors)[0][0]))) || (ok ? 'Saved.' : 'Action failed.');
        release();
        if (!ok) {
            var errBox = document.createElement('div');
            errBox.className = 'mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm';
            errBox.textContent = message;
            var pmBody = document.getElementById('pm-body');
            pmBody.prepend(errBox);
            errBox.scrollIntoView({ block: 'nearest' });
            return;
        }
        closePaymentModal();
        try { window.dispatchEvent(new CustomEvent('payment-recorded')); } catch (e) { /* noop */ }
        var finModal = document.getElementById('financial-modal');
        if (finModal && !finModal.classList.contains('hidden')) {
            openFinancialModal(finModal.dataset.studentId, finModal.dataset.studentName, finModal.dataset.year, { type: 'ok', text: message, link: out.data.print_url || null, linkLabel: 'Print Receipt' });
        }
    }).catch(function () {
        var pmBody = document.getElementById('pm-body');
        var box = document.createElement('div');
        box.className = 'mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm';
        box.textContent = 'Action failed — check your connection and retry.';
        pmBody.prepend(box);
        release();
    });
});
</script>
