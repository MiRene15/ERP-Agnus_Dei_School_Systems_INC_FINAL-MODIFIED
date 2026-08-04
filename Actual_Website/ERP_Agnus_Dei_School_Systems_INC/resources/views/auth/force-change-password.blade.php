<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        {{ __('Welcome! This is your first time logging in. Please set a new password to secure your account.') }}
    </div>

    <form method="POST" action="{{ route('password.force.update') }}">
        @csrf
        @method('PUT')

        <!-- New Password -->
        <div>
            <x-input-label for="password" :value="__('New Password')" />
            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('forcePassword.password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm New Password')" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation"
                            required autocomplete="new-password" />
        </div>

        <div class="flex justify-end mt-4">
            <x-primary-button>
                {{ __('Set Password & Continue') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
