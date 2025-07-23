@section('title', __('Two-Factor Recovery Codes') . ' - ' . config('app.name', 'PBPIS'))

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Two-Factor Recovery Codes') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    @if (session('status') == 'recovery-codes-regenerated')
                        <div class="mb-6 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-lg p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-green-700 dark:text-green-300">
                                        {{ __('Recovery codes have been regenerated successfully.') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif
                    
                    <div class="max-w-2xl">
                        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg p-4 mb-6">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                                        {{ __('Important: Save Your Recovery Codes') }}
                                    </h3>
                                    <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
                                        <p>{{ __('Store these recovery codes in a secure location. They can be used to access your account if you lose your two-factor authentication device.') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                            {{ __('Recovery Codes') }}
                        </h3>

                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                            {{ __('Each recovery code can only be used once. If you run out of recovery codes, you can generate new ones.') }}
                        </p>

                        <div class="bg-gray-100 dark:bg-gray-700 p-4 rounded-lg mb-6">
                            <div class="grid grid-cols-2 gap-2 font-mono text-sm">
                                @foreach($recoveryCodes as $code)
                                    <div class="bg-white dark:bg-gray-800 p-2 rounded text-center">
                                        {{ $code }}
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            <a href="{{ route('profile.edit') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                                {{ __('Continue to Profile') }}
                            </a>
                            
                            @if(auth()->user()->hasTwoFactorEnabled())
                                <form method="POST" action="{{ route('two-factor.recovery-codes.regenerate') }}" class="inline">
                                    @csrf
                                    <button type="submit" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">
                                        {{ __('Regenerate Recovery Codes') }}
                                    </button>
                                </form>
                            @endif
                        </div>

                        <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg">
                            <h4 class="font-medium text-blue-900 dark:text-blue-100 mb-2">
                                {{ __('How to Use Recovery Codes') }}
                            </h4>
                            <ul class="text-sm text-blue-800 dark:text-blue-200 space-y-1">
                                <li>• {{ __('Enter a recovery code instead of the 6-digit code from your authenticator app') }}</li>
                                <li>• {{ __('Each code can only be used once') }}</li>
                                <li>• {{ __('Keep these codes in a safe place, separate from your device') }}</li>
                                <li>• {{ __('If you lose access to your authenticator app, contact an administrator') }}</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
