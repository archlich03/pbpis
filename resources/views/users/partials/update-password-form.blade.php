<section>
    @if(!empty($user->ms_id))
        <header>
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ __('Set new password') }}
            </h2>

            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                {{ __('Password changes are not available for Microsoft-linked accounts. This user\'s password is managed through Microsoft.') }}
            </p>
        </header>

        <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-md">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">
                        {{ __('Microsoft Account Linked') }}
                    </h3>
                    <div class="mt-2 text-sm text-blue-700 dark:text-blue-300">
                        <p>{{ __('This user\'s account is linked to Microsoft. Password changes must be done through Microsoft\'s password reset functionality.') }}</p>
                    </div>
                </div>
            </div>
        </div>
    @else
        <header>
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ __('Set new password') }}
            </h2>

            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                {{ __('Ensure the account is using a long, random password to stay secure.') }}
            </p>
        </header>

        <form method="post" action="{{ route('users.updatePassword', $user) }}" class="mt-6 space-y-6">
            @csrf
            @method('patch')

            @if ($errors->updatePassword->any())
                <div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-red-900/20 dark:text-red-400" role="alert">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->updatePassword->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div>
                <x-input-label for="update_password_password" :value="__('New Password')" />
                <x-text-input id="update_password_password" name="password" type="password" class="mt-1 block w-full" autocomplete="new-password" />
            </div>

            <div>
                <x-input-label for="update_password_password_confirmation" :value="__('Confirm Password')" />
                <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" autocomplete="new-password" />
            </div>

            <div class="flex items-center gap-4">
                <x-primary-button>{{ __('Save') }}</x-primary-button>

                @if (session('status') === 'password-updated')
                    <p
                        x-data="{ show: true }"
                        x-show="show"
                        x-transition
                        x-init="setTimeout(() => show = false, 2000)"
                        class="text-sm text-gray-600 dark:text-gray-400"
                    >{{ __('Saved.') }}</p>
                @endif
            </div>

            <!-- Password Requirements -->
            <div class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-md">
                <p class="text-xs font-medium text-blue-900 dark:text-blue-100 mb-1">{{ __('Password Requirements') }}:</p>
                <ul class="text-xs text-blue-800 dark:text-blue-200 space-y-0.5">
                    <li>• {{ __('At least 12 characters long') }}</li>
                    <li>• {{ __('Contains uppercase letters (A-Z)') }}</li>
                    <li>• {{ __('Contains lowercase letters (a-z)') }}</li>
                    <li>• {{ __('Contains numbers (0-9)') }}</li>
                    <li>• {{ __('Contains symbols (!@#$%^&*)') }}</li>
                </ul>
            </div>
        </form>
    @endif
</section>
