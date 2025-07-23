@section('title', __('Setup Two-Factor Authentication') . ' - ' . config('app.name', 'PBPIS'))

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Setup Two-Factor Authentication') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="max-w-2xl">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                            {{ __('Setup Two-Factor Authentication') }}
                        </h3>

                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
                            {{ __('Two-factor authentication adds an additional layer of security to your account by requiring you to provide a six-digit code from your phone in addition to your password when signing in.') }}
                        </p>

                        <div class="space-y-6">
                            <div>
                                <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-2">
                                    {{ __('Step 1: Scan QR Code') }}
                                </h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                                    {{ __('Scan this QR code with your authenticator app (Google Authenticator, Authy, etc.):') }}
                                </p>
                                
                                <div class="bg-white p-4 rounded-lg inline-block">
                                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={{ urlencode($qrCodeUrl) }}" alt="QR Code" class="w-48 h-48">
                                </div>
                            </div>

                            <div>
                                <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-2">
                                    {{ __('Step 2: Manual Entry (Alternative)') }}
                                </h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                    {{ __('If you cannot scan the QR code, enter this secret key manually:') }}
                                </p>
                                <div class="bg-gray-100 dark:bg-gray-700 p-3 rounded font-mono text-sm">
                                    {{ $secret }}
                                </div>
                            </div>

                            <div>
                                <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-2">
                                    {{ __('Step 3: Verify Setup') }}
                                </h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                                    {{ __('Enter the 6-digit code from your authenticator app to complete setup:') }}
                                </p>

                                <form method="POST" action="{{ route('two-factor.confirm') }}">
                                    @csrf
                                    
                                    <div class="mb-4">
                                        <x-input-label for="code" :value="__('Verification Code')" />
                                        <x-text-input id="code" name="code" type="text" class="mt-1 block w-full max-w-xs" 
                                                      placeholder="123456" maxlength="6" pattern="[0-9]{6}" required />
                                        <x-input-error :messages="$errors->get('code')" class="mt-2" />
                                    </div>

                                    <div class="flex items-center gap-4">
                                        <x-primary-button>
                                            {{ __('Enable Two-Factor Authentication') }}
                                        </x-primary-button>
                                        
                                        <a href="{{ route('profile.edit') }}" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">
                                            {{ __('Cancel') }}
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
