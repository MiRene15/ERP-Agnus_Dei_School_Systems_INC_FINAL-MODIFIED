<div id="financial-modal" class="fixed inset-0 z-[100] hidden items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/40" style="backdrop-filter: blur(4px);" onclick="closeFinancialModal()"></div>
    <div class="relative bg-white dark:bg-[#1A1E3B] rounded-2xl shadow-2xl w-full max-w-[880px] max-h-[90vh] my-auto border border-gray-100 dark:border-[#2A2F58] flex flex-col overflow-hidden">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-[#2A2F58] flex-shrink-0">
            <h3 class="text-lg font-bold text-gray-900 dark:text-[#E8EAF6]" id="fm-title">Financial View</h3>
            <button onclick="closeFinancialModal()" class="text-2xl text-gray-400 hover:text-gray-700 leading-none">&times;</button>
        </div>
        <div id="fm-body" class="p-8 overflow-y-auto"></div>
    </div>
</div>

<script>
var financialModalAbort = null;

async function openFinancialModal(studentId, studentName, year, notice) {
    var modal = document.getElementById('financial-modal');
    if (!modal) return;
    document.body.appendChild(modal);
    modal.dataset.studentId = studentId;
    modal.dataset.studentName = studentName || '';
    modal.dataset.year = year || 'all';
    document.getElementById('fm-title').textContent = 'Financial View — ' + (studentName || '');
    document.getElementById('fm-body').innerHTML = '<div class="space-y-3"><div class="skelly sk-line-md"></div><div class="skelly sk-line-lg"></div><div class="skelly sk-line-md"></div><div class="skelly sk-line-sm"></div></div>';
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    if (financialModalAbort) { try { financialModalAbort.abort(); } catch (e) {} }
    financialModalAbort = new AbortController();
    try {
        var url = '/cashier/financial/' + studentId + '?ajax=1';
        if (year && year !== 'all') url += '&payment_year=' + encodeURIComponent(year);
        const res = await fetch(url, { signal: financialModalAbort.signal, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
        if (!res.ok) throw new Error('HTTP ' + res.status);
        const data = await res.json();
        if (String(modal.dataset.studentId) !== String(studentId)) return;
        var body = document.getElementById('fm-body');
        body.innerHTML = data.html || '';
        if (notice && notice.text) {
            var box = document.createElement('div');
            box.className = notice.type === 'error'
                ? 'mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm'
                : 'mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm flex items-center justify-between gap-3';
            var span = document.createElement('span');
            span.textContent = notice.text;
            box.appendChild(span);
            if (notice.link) {
                var anchor = document.createElement('a');
                anchor.href = notice.link;
                anchor.target = '_blank';
                anchor.rel = 'noopener';
                anchor.className = 'px-3 py-1.5 rounded-lg text-xs font-semibold text-white flex-shrink-0';
                anchor.style.background = 'var(--navy)';
                anchor.textContent = notice.linkLabel || 'Print Receipt';
                box.appendChild(anchor);
            }
            body.prepend(box);
        }
    } catch (e) {
        if (e && e.name === 'AbortError') return;
        document.getElementById('fm-body').innerHTML = '<div class="p-3 rounded-lg bg-red-50 border border-red-200 text-sm text-red-700 flex items-center justify-between gap-3"><span>Could not load the financial record.</span><button type="button" onclick="openFinancialModal(document.getElementById(\'financial-modal\').dataset.studentId, document.getElementById(\'financial-modal\').dataset.studentName)" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-white border border-red-200 hover:bg-red-100">Retry</button></div>';
    }
}

function closeFinancialModal() {
    var modal = document.getElementById('financial-modal');
    if (!modal) return;
    if (financialModalAbort) { try { financialModalAbort.abort(); } catch (e) {} }
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

function financialYearChanged(select, baseUrl) {
    var modal = document.getElementById('financial-modal');
    var inModal = modal && !modal.classList.contains('hidden') && modal.contains(select);
    if (inModal) {
        openFinancialModal(modal.dataset.studentId, modal.dataset.studentName, select.value);
    } else {
        window.location.href = baseUrl + '?payment_year=' + select.value;
    }
}

document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') closeFinancialModal();
});

document.addEventListener('submit', function (event) {
    var modal = document.getElementById('financial-modal');
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
        if (ok) {
            openFinancialModal(modal.dataset.studentId, modal.dataset.studentName, modal.dataset.year, { type: 'ok', text: message });
        } else {
            var body = document.getElementById('fm-body');
            var box = document.createElement('div');
            box.className = 'mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm';
            box.textContent = message;
            body.prepend(box);
            box.scrollIntoView({ block: 'nearest' });
        }
        release();
    }).catch(function () {
        var body = document.getElementById('fm-body');
        var box = document.createElement('div');
        box.className = 'mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm';
        box.textContent = 'Action failed — check your connection and retry.';
        body.prepend(box);
        release();
    });
});
</script>
