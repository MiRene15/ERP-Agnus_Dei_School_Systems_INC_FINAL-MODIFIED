import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

export const SEARCH_PAUSE_MS = 600;

/**
 * Builds a search method that waits for a pause in typing before it runs.
 *
 * The pause lives here, in the function, rather than in the markup's
 * `@input.debounce` modifier. A modifier can be forgotten or typed wrong and the
 * search then fires on every keystroke. Debouncing inside the function means the
 * binding cannot get it wrong: `@input="scheduleSearch()"` behaves correctly
 * whether or not `.debounce` is present in the attribute.
 *
 * Usage inside a component returned from x-data:
 *   performSearch: debounceSearch(function () { return this.runSearch(); }),
 *   scheduleSearch() { this.performSearch(); },
 *
 * Anything that is an explicit user action rather than typing - submitting the
 * form, changing a dropdown, pressing Refresh, a rate-limit countdown retry -
 * must call `performSearch.run()` so it searches immediately instead of waiting.
 *
 * @param {Function} search
 * @param {number} wait
 * @returns {Function & { run: Function }}
 */
function debounceSearch(search, wait = SEARCH_PAUSE_MS) {
    let timer = null;

    const debounced = function () {
        if (timer) clearTimeout(timer);
        timer = setTimeout(() => {
            timer = null;
            search.apply(this, arguments);
        }, wait);
    };

    debounced.run = function () {
        if (timer) { clearTimeout(timer); timer = null; }
        search.apply(this, arguments);
    };

    // Cancel a pending wait without firing (e.g. an explicit Search/Enter
    // already ran it). Call sites use arrow closures over their component,
    // so neither path depends on what `this` the template gives them.
    debounced.cancel = function () {
        if (timer) { clearTimeout(timer); timer = null; }
    };

    return debounced;
}

window.AgnusSearch = { debounce: debounceSearch, pauseMs: SEARCH_PAUSE_MS };

/**
 * Reusable AJAX table/list component with skeleton loading.
 *
 * Usage:
 *   <div x-data="ajaxTable('{{ route('some.route') }}', { search: '', status: '' })">
 *     <form @submit.prevent="reload()">
 *       <input x-model="filters.search" @input.debounce.600ms="reload()">
 *       <select x-model="filters.status" @change="reload()">...</select>
 *       <button type="submit">Search</button>
 *       <button type="button" @click="reset()">Clear</button>
 *     </form>
 *
 *     <div x-show="loading && !html" class="space-y-3"> skeleton blocks </div>
 *     <div x-show="error" x-cloak><span x-text="error"></span><button @click="reload()">Refresh</button></div>
 *     <div x-show="html || !loading" x-cloak x-html="html" class="fade-in"></div>
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
    error: '',
    isRateLimited: false,
    retryAfter: 0,
    lastKey: null,
    _controller: null,
    _seq: 0,
    _countdown: null,
    _debouncedReload: null,
    showAdvanced: Object.values(initialFilters || {}).some((v) => v !== '' && v !== null && v !== undefined),
    init() {
        // The wait stays inside the debounced function (see debounceSearch),
        // so a screen author cannot forget it. Typing calls scheduleReload();
        // explicit actions call reload() directly and stay instant.
        this._debouncedReload = debounceSearch(function () { return this.reload(); });
        this.reload();
    },
    // Typing path (safe-actions-calm-search): waits for a pause automatically,
    // so a screen author cannot forget it. Explicit actions (submit, select,
    // Refresh, pagination) keep calling reload() directly and stay instant.
    scheduleReload() {
        if (!this._debouncedReload) {
            this._debouncedReload = debounceSearch(function () { return this.reload(); });
        }
        return this._debouncedReload();
    },
    startCountdown() {
        if (this._countdown) { try { clearInterval(this._countdown); } catch (e) {} this._countdown = null; }
        this._countdown = setInterval(() => {
            if (this.retryAfter > 0) {
                this.retryAfter--;
                this.error = `Too many searches - wait ${this.retryAfter}s.`;
            }
            if (this.retryAfter <= 0) {
                if (this._countdown) { try { clearInterval(this._countdown); } catch (e) {} this._countdown = null; }
                this.isRateLimited = false;
                this.error = '';
                this.reload();
            }
        }, 1000);
    },
    async reload() {
        // An explicit Search/Enter/select already expresses the latest
        // intent: drop any pending typed wait so one press means one request.
        if (this._debouncedReload && this._debouncedReload.cancel) {
            try { this._debouncedReload.cancel(); } catch (e) { /* noop */ }
        }
        // Auto-reset page when any real filter changes between requests.
        const baseEntries = Object.entries(this.filters).filter(([key]) => key !== 'page');
        const baseKey = JSON.stringify(baseEntries);
        if (this.lastKey !== null && this.lastKey !== baseKey) {
            this.filters.page = '';
        }
        this.lastKey = baseKey;

        // While throttled, coalesce: drop stale work, keep latest filters,
        // no new fetch — the running countdown requeues one retry at 0.
        if (this.isRateLimited && this.retryAfter > 0) {
            if (this._controller) {
                try { this._controller.abort(); } catch (e) { /* noop */ }
            }
            this._seq++;
            this.loading = false;
            if (!this._countdown) this.startCountdown();
            return;
        }

        // Drop prior unfinished work so only the latest text wins.
        if (this._controller) {
            try { this._controller.abort(); } catch (e) { /* noop */ }
        }
        this._controller = new AbortController();
        const signal = this._controller.signal;
        const mySeq = ++this._seq;

        this.loading = true;
        // Keep last good list on screen; surface status via error hint only.
        try {
            const params = new URLSearchParams();
            Object.entries(this.filters).forEach(([key, value]) => {
                if (value !== null && value !== undefined && value !== '') {
                    params.append(key, value);
                }
            });
            params.append('ajax', '1');

            const response = await fetch(`${this.url}?${params.toString()}`, {
                signal,
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            });
            if (signal.aborted || mySeq !== this._seq) return;
            if (response.status === 429) {
                const retry = parseInt(response.headers.get('Retry-After') || '20', 10);
                this.retryAfter = Number.isFinite(retry) && retry > 0 ? retry : 20;
                this.isRateLimited = true;
                this.error = `Too many searches - wait ${this.retryAfter}s.`;
                console.info(`[search] 429 throttled, retry in ${this.retryAfter}s — showing wait box.`);
                this.startCountdown();
                return;
            }
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            const data = await response.json();
            if (signal.aborted || mySeq !== this._seq) return;
            if (this._countdown) { try { clearInterval(this._countdown); } catch (e) {} this._countdown = null; }
            this.html = data.html || '';
            this.error = '';
            this.isRateLimited = false;
            this.retryAfter = 0;
        } catch (e) {
            if (e && e.name === 'AbortError') return;
            if (signal.aborted || mySeq !== this._seq) return;
            console.error('AJAX load failed:', e);
            // Keep last good list; show Refresh hint (list never blanked).
            if (!this.html) {
                this.html = '';
            }
            this.error = 'Search failed — Refresh.';
        } finally {
            // Only the latest request clears the loading hint.
            if (mySeq === this._seq) this.loading = false;
        }
    },
    reset() {
        // Clear always works instantly (Child 1): drop wait + countdown too.
        if (this._controller) {
            try { this._controller.abort(); } catch (e) { /* noop */ }
        }
        if (this._countdown) { try { clearInterval(this._countdown); } catch (e) {} this._countdown = null; }
        this._seq++;
        this.error = '';
        this.isRateLimited = false;
        this.retryAfter = 0;
        Object.keys(this.filters).forEach((key) => (this.filters[key] = ''));
        this.reload();
    },
    // Delegated handler for pagination links inside the injected HTML.
    // Usage: <div @click="handlePaginationClick($event)" x-html="html"></div>
    handlePaginationClick(event) {
        const link = event.target.closest('a[href]');
        if (!link) return;
        const url = new URL(link.href, window.location.origin);
        const page = url.searchParams.get('page');
        if (!page) return;
        event.preventDefault();
        this.filters.page = page;
        this.reload();
        const container = this.$refs.results;
        if (container) container.scrollIntoView({ behavior: 'smooth', block: 'start' });
    },
}));

Alpine.start();
