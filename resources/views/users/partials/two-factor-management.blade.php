<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Two-Factor Authentication Management') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('Manage two-factor authentication settings for this user.') }}
        </p>
    </header>

    <div class="mt-6">
        @if($user->hasTwoFactorEnabled())
            <div class="flex items-center mb-4">
                <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
                <span class="text-sm font-medium text-green-600 dark:text-green-400">
                    {{ __('Two-factor authentication is enabled') }}
                </span>
            </div>

            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                {{ __('This user has two-factor authentication enabled. You can remove it if needed (e.g., if they lost their device).') }}
            </p>

            <x-danger-button
                x-data=""
                x-on:click.prevent="$dispatch('open-modal', 'remove-user-two-factor')"
            >{{ __('Remove Two-Factor Authentication') }}</x-danger-button>

            <x-modal name="remove-user-two-factor" focusable>
                <form method="post" action="{{ route('users.remove-two-factor', $user) }}" class="p-6">
                    @csrf
                    @method('delete')

                    <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                        {{ __('Remove Two-Factor Authentication') }}
                    </h2>

                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        {{ __('Are you sure you want to remove two-factor authentication for this user? This action will make their account less secure.') }}
                    </p>

                    <p class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">
                        {{ __('User: :name (:email)', ['name' => $user->name, 'email' => $user->email]) }}
                    </p>

                    <div class="mt-6 flex justify-end">
                        <x-secondary-button type="button" x-on:click="$dispatch('close')">
                            {{ __('Cancel') }}
                        </x-secondary-button>

                        <x-danger-button type="submit" class="ms-3">
                            {{ __('Remove Two-Factor Authentication') }}
                        </x-danger-button>
                    </div>
                </form>
            </x-modal>
        @else
            <div class="flex items-center mb-4">
                <svg class="w-5 h-5 text-gray-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                </svg>
                <span class="text-sm font-medium text-gray-600 dark:text-gray-400">
                    {{ __('Two-factor authentication is not enabled') }}
                </span>
            </div>

            <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ __('This user has not enabled two-factor authentication. They can enable it themselves from their profile settings.') }}
            </p>
        @endif
    </div>

    @if (session('status') === '2fa-removed')
        <p
            x-data="{ show: true }"
            x-show="show"
            x-transition
            x-init="setTimeout(() => show = false, 3000)"
            class="text-sm text-green-600 dark:text-green-400 mt-2"
        >{{ __('Two-factor authentication has been removed for this user.') }}</p>
    @endif
</section>
