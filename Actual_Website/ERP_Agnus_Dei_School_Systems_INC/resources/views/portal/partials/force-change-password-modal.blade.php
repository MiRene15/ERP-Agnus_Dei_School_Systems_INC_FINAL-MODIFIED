@if(!auth()->user()->first_login_at)
<div x-data="forcePasswordModal()" x-show="show" x-cloak
     x-transition.opacity
     class="fixed inset-0 z-[999] flex items-center justify-center p-4"
     style="background: rgba(14,17,36,0.55); backdrop-filter: blur(6px);">
    <div class="bg-white dark:bg-[#1A1E3B] rounded-2xl shadow-2xl w-full max-w-md p-8 relative border border-gray-100 dark:border-[#2A2F58]" @click.stop>
        <div class="text-center mb-6">
            <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4" style="background: var(--navy);">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-[#E8EAF6]">Set Your New Password</h3>
            <p class="text-sm text-gray-500 dark:text-[#8A90B0] mt-1">This is your first login. Please set a new password to secure your account.</p>
        </div>

        <div x-show="errors.length" class="mb-4 p-3 rounded-lg bg-red-50 dark:bg-[rgba(248,113,113,0.12)] border border-red-200 dark:border-[rgba(248,113,113,0.25)] text-sm text-red-700 dark:text-[#FCA5A5]">
            <p class="font-semibold mb-1">Please fix the following:</p>
            <ul class="list-disc ml-4 space-y-0.5">
                <template x-for="err in errors" :key="err"><li x-text="err"></li></template>
            </ul>
        </div>
        <div x-show="successMsg" class="mb-4 p-3 rounded-lg bg-green-50 dark:bg-[rgba(74,222,128,0.12)] border border-green-200 dark:border-[rgba(74,222,128,0.25)] text-sm text-green-700 dark:text-[#86EFAC]" x-text="successMsg"></div>

        @if($errors->getBag('forcePassword')->any())
            <div class="mb-4 p-3 rounded-lg bg-red-50 dark:bg-[rgba(248,113,113,0.12)] border border-red-200 dark:border-[rgba(248,113,113,0.25)] text-sm text-red-700 dark:text-[#FCA5A5]">
                <ul class="list-disc ml-4 space-y-0.5">
                    @foreach($errors->getBag('forcePassword')->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form @submit.prevent="submit()" novalidate>
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 dark:text-[#C1C4DC] mb-1">New Password</label>
                <input type="password" x-model="password" required autocomplete="new-password"
                       class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-[#3B4172] bg-white dark:bg-[#23274C] text-gray-900 dark:text-[#E8EAF6] placeholder-gray-400 dark:placeholder-[#6A7094] focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 text-sm outline-none transition">
                <p class="text-xs text-gray-400 dark:text-[#6A7094] mt-1">Min 8 chars, include uppercase, lowercase, number, and symbol.</p>
            </div>
            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 dark:text-[#C1C4DC] mb-1">Confirm New Password</label>
                <input type="password" x-model="password_confirmation" required autocomplete="new-password"
                       class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-[#3B4172] bg-white dark:bg-[#23274C] text-gray-900 dark:text-[#E8EAF6] placeholder-gray-400 dark:placeholder-[#6A7094] focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 text-sm outline-none transition">
            </div>
            <button type="submit" :disabled="loading"
                    class="w-full py-3 rounded-lg text-white font-bold text-sm tracking-wide transition hover:opacity-90 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                    style="background: var(--navy);">
                <svg x-show="loading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                <span x-text="loading ? 'Saving...' : 'SET PASSWORD & CONTINUE'"></span>
            </button>
        </form>
    </div>
</div>
<script>
function forcePasswordModal() {
    return {
        show: true,
        loading: false,
        errors: [],
        successMsg: '',
        password: '',
        password_confirmation: '',
        async submit() {
            this.loading = true;
            this.errors = [];
            this.successMsg = '';
            const token = document.querySelector('meta[name=\"csrf-token\"]')?.content || '';
            const formData = new FormData();
            formData.append('_method', 'PUT');
            formData.append('_token', token);
            formData.append('password', this.password);
            formData.append('password_confirmation', this.password_confirmation);
            try {
                const res = await fetch('{{ route('password.force.update') }}', {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    body: formData
                });
                const data = await res.json();
                if (!res.ok) {
                    if (data.errors) {
                        const bag = data.errors.password || Object.values(data.errors).flat();
                        this.errors = bag;
                    } else if (data.message) {
                        this.errors = [data.message];
                    } else {
                        this.errors = ['Failed to set password. Please try again.'];
                    }
                    return;
                }
                this.successMsg = data.message || 'Password set! Welcome.';
                setTimeout(() => { this.show = false; }, 800);
            } catch (e) {
                this.errors = ['Network error. Please try again.'];
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>
@endif
