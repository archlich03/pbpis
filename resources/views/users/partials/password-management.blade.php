<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Password Management') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('Force this user to change their password on next login.') }}
        </p>
    </header>

    <div class="mt-6">
        @if($user->password_change_required)
            <div class="flex items-center mb-4">
                <svg class="w-5 h-5 text-yellow-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                </svg>
                <span class="text-sm font-medium text-yellow-600 dark:text-yellow-400">
                    {{ __('Password change is required') }}
                </span>
            </div>

            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                {{ __('This user must change their password before they can access the system.') }}
            </p>
            
            <x-primary-button
                x-data=""
                x-on:click.prevent="$dispatch('open-modal', 'cancel-password-change')"
            >{{ __('Cancel Password Change') }}</x-primary-button>
            
            <x-modal name="cancel-password-change" focusable>
                <form method="post" action="{{ route('users.cancel-password-change', $user) }}" class="p-6">
                    @csrf
                    @method('patch')

                    <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                        {{ __('Cancel Password Change') }}
                    </h2>

                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        {{ __('Are you sure you want to cancel the forced password change for this user? They will be able to access the system with their current password.') }}
                    </p>

                    <p class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">
                        {{ __('User: :name (:email)', ['name' => $user->name, 'email' => $user->email]) }}
                    </p>

                    <div class="mt-6 flex justify-end">
                        <x-secondary-button x-on:click="$dispatch('close')">
                            {{ __('Cancel') }}
                        </x-secondary-button>

                        <x-primary-button class="ms-3">
                            {{ __('Cancel Password Change') }}
                        </x-primary-button>
                    </div>
                </form>
            </x-modal>
        @else
            <div class="flex items-center mb-4">
                <svg class="w-5 h-5 text-gray-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
                <span class="text-sm font-medium text-gray-600 dark:text-gray-400">
                    {{ __('No password change required') }}
                </span>
            </div>

            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                {{ __('You can force this user to change their password on their next login for security reasons.') }}
            </p>

            <x-danger-button
                x-data=""
                x-on:click.prevent="$dispatch('open-modal', 'force-password-change')"
            >{{ __('Force Password Change') }}</x-danger-button>

            <x-modal name="force-password-change" focusable>
                <form method="post" action="{{ route('users.force-password-change', $user) }}" class="p-6">
                    @csrf

                    <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                        {{ __('Force Password Change') }}
                    </h2>

                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        {{ __('Are you sure you want to force this user to change their password? They will be required to change it on their next login.') }}
                    </p>

                    <p class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">
                        {{ __(':name (:email)', ['name' => $user->name, 'email' => $user->email]) }}
                    </p>

                    <div class="mt-6 flex justify-end">
                        <x-primary-button x-on:click="$dispatch('close')">
                            {{ __('Cancel') }}
                        </x-primary-button>

                        <x-danger-button class="ms-3">
                            {{ __('Force Password Change') }}
                        </x-danger-button>
                    </div>
                </form>
            </x-modal>
        @endif
    </div>

    @if (session('status') === 'password-change-forced')
        <p
            x-data="{ show: true }"
            x-show="show"
            x-transition
            x-init="setTimeout(() => show = false, 3000)"
            class="text-sm text-green-600 dark:text-green-400 mt-2"
        >{{ __('Password change has been required for this user.') }}</p>
    @endif
</section>
