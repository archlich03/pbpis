<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Two-Factor Authentication') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('Add additional security to your account using two-factor authentication.') }}
        </p>
    </header>

    @if($user->hasTwoFactorEnabled())
        <div class="mt-6">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
                <span class="text-sm font-medium text-green-600 dark:text-green-400">
                    {{ __('Two-factor authentication is enabled') }}
                </span>
            </div>

            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                {{ __('Two-factor authentication is currently enabled for your account. You can view your recovery codes or disable it below.') }}
            </p>

            <div class="mt-4 flex items-center gap-4">
                <a href="{{ route('two-factor.recovery-codes') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                    {{ __('View Recovery Codes') }}
                </a>

                <x-danger-button
                    x-data=""
                    x-on:click.prevent="$dispatch('open-modal', 'disable-two-factor')"
                >{{ __('Disable') }}</x-danger-button>
            </div>
        </div>

        <x-modal name="disable-two-factor" :show="$errors->isNotEmpty()" focusable>
            <form method="post" action="{{ route('two-factor.disable') }}" class="p-6">
                @csrf
                @method('delete')

                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    {{ __('Are you sure you want to disable two-factor authentication?') }}
                </h2>

                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    {{ __('This will make your account less secure. You can re-enable it at any time.') }}
                </p>

                <div class="mt-6">
                    <x-input-label for="password" value="{{ __('Password') }}" class="sr-only" />
                    <x-text-input
                        id="password"
                        name="password"
                        type="password"
                        class="mt-1 block w-3/4"
                        placeholder="{{ __('Password') }}"
                    />
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <div class="mt-6 flex justify-end">
                    <x-secondary-button type="button" x-on:click="$dispatch('close')">
                        {{ __('Cancel') }}
                    </x-secondary-button>

                    <x-danger-button type="submit" class="ms-3">
                        {{ __('Disable Two-Factor Authentication') }}
                    </x-danger-button>
                </div>
            </form>
        </x-modal>
    @else
        <div class="mt-6">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-gray-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                </svg>
                <span class="text-sm font-medium text-gray-600 dark:text-gray-400">
                    {{ __('Two-factor authentication is not enabled') }}
                </span>
            </div>

            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                {{ __('Two-factor authentication adds an additional layer of security to your account by requiring you to provide a six-digit code from your phone when signing in.') }}
            </p>

            <div class="mt-4">
                <a href="{{ route('two-factor.setup') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                    {{ __('Enable Two-Factor Authentication') }}
                </a>
            </div>
        </div>
    @endif

    @if (session('status') === '2fa-disabled')
        <p
            x-data="{ show: true }"
            x-show="show"
            x-transition
            x-init="setTimeout(() => show = false, 3000)"
            class="text-sm text-green-600 dark:text-green-400 mt-2"
        >{{ __('Two-factor authentication has been disabled.') }}</p>
    @endif
</section>
