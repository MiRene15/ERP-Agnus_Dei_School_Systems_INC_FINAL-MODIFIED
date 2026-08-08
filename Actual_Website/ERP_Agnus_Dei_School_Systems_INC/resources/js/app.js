import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

/**
 * Reusable AJAX table/list component with skeleton loading.
 *
 * Usage:
 *   <div x-data="ajaxTable('{{ route('some.route') }}', { search: '', status: '' })">
 *     <form @submit.prevent="reload()">
 *       <input x-model="filters.search" @input.debounce.300ms="reload()">
 *       <select x-model="filters.status" @change="reload()">...</select>
 *       <button type="submit">Search</button>
 *       <button type="button" @click="reset()">Clear</button>
 *     </form>
 *
 *     <div x-show="loading" class="space-y-3"> skeleton blocks </div>
 *     <div x-show="!loading" x-cloak x-html="html" class="fade-in"></div>
 *   </div>
 *
 * The controller must return JSON { html: '<rendered partial>' } when the
 * `ajax` query param is present.
 */
Alpine.data('ajaxTable', (url, initialFilters = {}) => ({
    url,
    filters: { ...initialFilters },
    loading: true,
    html: '',
    lastKey: null,
    showAdvanced: false,
    init() {
        this.reload();
    },
    async reload() {
        // Auto-reset page when any real filter changes between requests.
        const baseEntries = Object.entries(this.filters).filter(([key]) => key !== 'page');
        const baseKey = JSON.stringify(baseEntries);
        if (this.lastKey !== null && this.lastKey !== baseKey) {
            this.filters.page = '';
        }
        this.lastKey = baseKey;

        this.loading = true;
        try {
            const params = new URLSearchParams();
            Object.entries(this.filters).forEach(([key, value]) => {
                if (value !== null && value !== undefined && value !== '') {
                    params.append(key, value);
                }
            });
            params.append('ajax', '1');

            const response = await fetch(`${this.url}?${params.toString()}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            });
            const data = await response.json();
            this.html = data.html || '';
        } catch (e) {
            console.error('AJAX load failed:', e);
            this.html = '<div class="py-8 text-center text-gray-500 text-sm">Failed to load data.</div>';
        } finally {
            this.loading = false;
        }
    },
    reset() {
        Object.keys(this.filters).forEach((key) => (this.filters[key] = ''));
        this.reload();
    },
    // Delegated handler for pagination links inside the injected HTML.
    // Usage: <div @click="handlePaginationClick($event)" x-html="html"></div>
    handlePaginationClick(event) {
        const link = event.target.closest('a[href]');
        if (!link || !link.closest('.pagination')) return;

        const url = new URL(link.href);
        const page = url.searchParams.get('page');

        if (link.href === window.location.href && !link.dataset.active) return;

        event.preventDefault();
        this.filters.page = page;
        this.reload();
        const container = this.$refs.results;
        if (container) container.scrollIntoView({ behavior: 'smooth', block: 'start' });
    },
}));

Alpine.start();
