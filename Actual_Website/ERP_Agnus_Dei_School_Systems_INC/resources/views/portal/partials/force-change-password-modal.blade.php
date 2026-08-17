@if(!auth()->user()->first_login_at)
<div x-data="{ show: true }" x-show="show" x-cloak
     class="fixed inset-0 z-[999] flex items-center justify-center"
     style="background: rgba(0,0,0,0.4); backdrop-filter: blur(4px);">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 p-8 relative" @click.stop>
        <div class="text-center mb-6">
            <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4" style="background: var(--navy);">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
            <h3 class="text-xl font-bold text-gray-900">Set Your New Password</h3>
            <p class="text-sm text-gray-500 mt-1">This is your first login. Please set a new password to secure your account.</p>
        </div>

        <form method="POST" action="{{ route('password.force.update') }}">
            @csrf
            @method('PUT')

            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">New Password</label>
                <input type="password" name="password" required autocomplete="new-password"
                       class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 text-sm outline-none transition">
                @error('forcePassword.password')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Confirm New Password</label>
                <input type="password" name="password_confirmation" required autocomplete="new-password"
                       class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 text-sm outline-none transition">
            </div>

            <button type="submit"
                    class="w-full py-3 rounded-lg text-white font-bold text-sm tracking-wide transition hover:opacity-90"
                    style="background: var(--navy);">
                SET PASSWORD & CONTINUE
            </button>
        </form>
    </div>
</div>
@endif
