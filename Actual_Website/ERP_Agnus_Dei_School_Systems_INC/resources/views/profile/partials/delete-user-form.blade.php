<section class="space-y-6">
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Deactivate Account') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Your account will be deactivated (archived) instead of deleted. Your records will be preserved for school purposes, but you will no longer be able to log in.') }}
        </p>
    </header>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >{{ __('Deactivate Account') }}</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <h2 class="text-lg font-medium text-gray-900">
                {{ __('Are you sure you want to deactivate your account?') }}
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                {{ __('Your account will be deactivated and archived. Please provide the reason for deactivation and confirm with your password.') }}
            </p>

            @if(auth()->user()->student)
            <div class="mt-6">
                <x-input-label for="action" value="{{ __('Action') }}" />

                <select id="action" name="action"
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                    <option value="">Select action...</option>
                    <option value="transfer">Transfer</option>
                    <option value="graduated">Graduated</option>
                </select>

                <x-input-error :messages="$errors->userDeletion->get('action')" class="mt-2" />
            </div>
            @endif

            <div class="mt-6">
                <x-input-label for="reason" value="{{ __('Reason for deactivation') }}" />

                <textarea id="reason" name="reason" rows="3" required
                          placeholder="{{ __('Please state the reason...') }}"
                          class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"></textarea>

                <x-input-error :messages="$errors->userDeletion->get('reason')" class="mt-2" />
            </div>

            <div class="mt-6">
                <x-input-label for="password" value="{{ __('Password') }}" class="sr-only" />

                <x-text-input
                    id="password"
                    name="password"
                    type="password"
                    class="mt-1 block w-3/4"
                    placeholder="{{ __('Password') }}"
                />

                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    {{ __('Cancel') }}
                </x-secondary-button>

                <x-danger-button class="ms-3">
                    {{ __('Deactivate Account') }}
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
