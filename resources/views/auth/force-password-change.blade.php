@section('title', __('Password Change Required') . ' - ' . config('app.name', 'PBPIS'))

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Password Change Required') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="max-w-xl">
                        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg p-4 mb-6">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                                        {{ __('Password Change Required') }}
                                    </h3>
                                    <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
                                        <p>{{ __('An administrator has required you to change your password before you can continue using the system.') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                            {{ __('Update Password') }}
                        </h3>

                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400 mb-6">
                            {{ __('Please choose a strong password that you haven\'t used before.') }}
                        </p>

                        <form method="post" action="{{ route('password.update') }}" class="space-y-6">
                            @csrf
                            @method('put')



                            <div>
                                <x-input-label for="update_password_password" :value="__('New Password')" />
                                <x-text-input id="update_password_password" name="password" type="password" class="mt-1 block w-full" autocomplete="new-password" />
                                <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    {{ __('Password must be at least 12 characters with uppercase, lowercase, numbers, and symbols.') }}
                                </p>
                            </div>

                            <div>
                                <x-input-label for="update_password_password_confirmation" :value="__('Confirm Password')" />
                                <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" autocomplete="new-password" />
                                <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
                            </div>

                            <div class="flex items-center gap-4">
                                <x-primary-button>{{ __('Update Password') }}</x-primary-button>

                                @if (session('status') === 'password-updated')
                                    <p
                                        x-data="{ show: true }"
                                        x-show="show"
                                        x-transition
                                        x-init="setTimeout(() => show = false, 2000)"
                                        class="text-sm text-gray-600 dark:text-gray-400"
                                    >{{ __('Password updated successfully.') }}</p>
                                @endif
                            </div>
                        </form>

                        <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg">
                            <h4 class="font-medium text-blue-900 dark:text-blue-100 mb-2">
                                {{ __('Password Requirements') }}
                            </h4>
                            <ul class="text-sm text-blue-800 dark:text-blue-200 space-y-1">
                                <li>• {{ __('At least 12 characters long') }}</li>
                                <li>• {{ __('Contains uppercase letters (A-Z)') }}</li>
                                <li>• {{ __('Contains lowercase letters (a-z)') }}</li>
                                <li>• {{ __('Contains numbers (0-9)') }}</li>
                                <li>• {{ __('Contains symbols (!@#$%^&*)') }}</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
