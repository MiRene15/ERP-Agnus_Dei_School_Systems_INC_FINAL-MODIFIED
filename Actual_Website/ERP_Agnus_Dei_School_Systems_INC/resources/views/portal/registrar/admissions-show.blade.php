@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('registrar.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <a href="{{ route('registrar.admissions.index') }}" class="no-underline" style="color: var(--muted);">Admissions Queue</a>
    <span class="opacity-40">/</span>
    <span class="current">{{ $admission->application_number }}</span>
@endsection

@section('content')
@if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm">{{ session('error') }}</div>
@endif

<div x-data="ajaxTable('{{ route('registrar.admissions.show', $admission) }}')">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div x-show="loading" class="p-4 space-y-3">
            <template x-for="i in 4" :key="i">
                <div class="skelly sk-card">
                    <div class="grid grid-cols-2 gap-4 px-2">
                        <div class="skelly sk-line-md col-span-2"></div>
                        <div class="skelly sk-line-md"></div>
                        <div class="skelly sk-line-md"></div>
                    </div>
                </div>
            </template>
        </div>
        <div x-show="!loading" x-cloak x-ref="results" x-html="html" class="fade-in"></div>
    </div>
</div>

<script>
{{-- Checklist + approve components live here (not in the AJAX partial):
     scripts injected via x-html never execute, so definitions inside the
     partial never registered and count/status stayed blank. --}}
document.addEventListener('alpine:init', () => {
    Alpine.data('requirementsChecklist', () => ({
        requirements: [],
        busy: {},
        verifyAllBusy: false,
        init() {
            try {
                const el = document.getElementById('requirements-data');
                this.requirements = el ? JSON.parse(el.textContent) : [];
            } catch (e) { this.requirements = []; }
        },
        get verifiedCount() { return this.requirements.filter(r => r.status === 'Verified').length },
        get totalCount() { return this.requirements.length },
        toggleVerify(reqId) {
            if (this.busy[reqId]) return;
            const form = document.getElementById('verify-form-' + reqId);
            if (!form) return;
            const hiddenInput = form.querySelector('input[name=verify]');
            const req = this.requirements.find(r => r.id === reqId);
            if (!req) return;
            hiddenInput.value = req.status === 'Verified' ? '0' : '1';
            const formData = new FormData(form);
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
            this.busy[reqId] = true;
            fetch(form.action, {
                method: 'POST', body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
            })
            .then(r => { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
            .then(data => { if (data.success) req.status = data.status; })
            .catch(err => { console.error('Verify failed:', err); alert('Verify failed: ' + err.message); })
            .finally(() => { this.busy[reqId] = false; });
        },
        verifyAll() {
            if (this.verifyAllBusy) return;
            const form = document.getElementById('verify-all-form');
            if (!form) return;
            const formData = new FormData(form);
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
            this.verifyAllBusy = true;
            fetch(form.action, {
                method: 'POST', body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
            })
            .then(r => { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
            .then(data => { if (data.success) this.requirements.forEach(r => r.status = 'Verified'); })
            .catch(err => { console.error('Verify All failed:', err); alert('Verify All failed: ' + err.message); })
            .finally(() => { this.verifyAllBusy = false; });
        }
    }));

    Alpine.data('approveForm', () => ({
        selectedSection: '',
        sectionMap: {},
        allClasses: [],
        selectedIds: [],
        init() {
            try {
                const clsEl = document.getElementById('classes-data');
                const secEl = document.getElementById('section-map');
                this.allClasses = clsEl ? JSON.parse(clsEl.textContent) : [];
                this.sectionMap = secEl ? JSON.parse(secEl.textContent) : {};
            } catch (e) { this.allClasses = []; this.sectionMap = {}; }
        },
        get selectedSectionName() { return this.sectionMap[this.selectedSection] || ''; },
        get filteredClasses() { return this.allClasses.filter(c => c.section === this.selectedSectionName); },
        get allSelected() { return this.filteredClasses.length > 0 && this.selectedIds.length === this.filteredClasses.length; },
        toggleAll() {
            if (this.allSelected) { this.selectedIds = []; }
            else { this.selectedIds = this.filteredClasses.map(c => c.id); }
        },
        isSelected(id) { return this.selectedIds.includes(id); },
        toggleId(id) {
            if (this.isSelected(id)) { this.selectedIds = this.selectedIds.filter(x => x !== id); }
            else { this.selectedIds.push(id); }
        }
    }));
});
</script>
@endsection
