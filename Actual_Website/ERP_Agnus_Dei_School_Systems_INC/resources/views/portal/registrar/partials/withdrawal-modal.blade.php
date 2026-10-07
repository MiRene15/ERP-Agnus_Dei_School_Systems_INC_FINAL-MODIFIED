<div id="withdrawal-modal" class="fixed inset-0 z-[100] hidden items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/40" style="backdrop-filter: blur(4px);" onclick="closeWithdrawalModal()"></div>
    <div class="relative bg-white dark:bg-[#1A1E3B] rounded-2xl shadow-2xl w-full max-w-md max-h-[90vh] overflow-y-auto my-auto p-6 border border-gray-100 dark:border-[#2A2F58]">
        <button onclick="closeWithdrawalModal()" class="absolute top-3 right-4 text-2xl text-gray-400 hover:text-gray-700 leading-none">&times;</button>
        <h3 class="text-lg font-bold text-gray-900 dark:text-[#E8EAF6] mb-1">Withdrawal Request</h3>
        <p class="text-xs text-gray-500 mb-4">Filed <span id="wm-filed"></span></p>
        <dl class="space-y-2.5 text-sm">
            <div class="flex justify-between gap-4"><dt class="text-gray-500">Student</dt><dd class="font-medium text-gray-900 dark:text-[#E8EAF6] text-right" id="wm-student"></dd></div>
            <div class="flex justify-between gap-4"><dt class="text-gray-500">Student No.</dt><dd class="font-medium text-gray-900 dark:text-[#E8EAF6] text-right" id="wm-number"></dd></div>
            <div class="flex justify-between gap-4"><dt class="text-gray-500">Section</dt><dd class="font-medium text-gray-900 dark:text-[#E8EAF6] text-right" id="wm-section"></dd></div>
            <div><dt class="text-gray-500 mb-1">Reason</dt><dd class="text-gray-900 dark:text-[#E8EAF6] bg-gray-50 dark:bg-[#23274C] rounded-lg px-3 py-2" id="wm-reason"></dd></div>
            <div class="flex justify-between gap-4"><dt class="text-gray-500">Status</dt><dd class="font-medium text-right" id="wm-status"></dd></div>
            <div class="flex justify-between gap-4"><dt class="text-gray-500">Refund</dt><dd class="font-medium text-gray-900 dark:text-[#E8EAF6] text-right"><span id="wm-refund"></span> <span class="text-xs text-gray-400" id="wm-refund-date"></span></dd></div>
            <div class="flex justify-between gap-4"><dt class="text-gray-500">Decided by</dt><dd class="font-medium text-gray-900 dark:text-[#E8EAF6] text-right" id="wm-decided"></dd></div>
            <div><dt class="text-gray-500 mb-1">Remarks</dt><dd class="text-gray-900 dark:text-[#E8EAF6] text-right" id="wm-remarks"></dd></div>
        </dl>
    </div>
</div>

<script>
function openWithdrawalModal(btn) {
    var d = btn.dataset;
    document.getElementById('wm-student').textContent = d.student || '—';
    document.getElementById('wm-number').textContent = d.number || '—';
    document.getElementById('wm-section').textContent = d.section || '—';
    document.getElementById('wm-reason').textContent = d.reason || '—';
    document.getElementById('wm-status').textContent = d.status || '—';
    document.getElementById('wm-refund').textContent = d.refund || '—';
    document.getElementById('wm-refund-date').textContent = d.refundDate && d.refundDate !== '—' ? '(' + d.refundDate + ')' : '';
    document.getElementById('wm-decided').textContent = (d.decidedBy && d.decidedBy !== '—' ? 'Head Registrar — ' + d.decidedBy : '—');
    document.getElementById('wm-remarks').textContent = d.remarks || '—';
    document.getElementById('wm-filed').textContent = d.filed || '';
    var modal = document.getElementById('withdrawal-modal');
    document.body.appendChild(modal);
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}
function closeWithdrawalModal() {
    var modal = document.getElementById('withdrawal-modal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}
</script>
