@section('title', __('Two-Factor Authentication') . ' - ' . config('app.name', 'PBPIS'))

<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
        {{ __('Please confirm access to your account by entering the authentication code provided by your authenticator application or one of your emergency recovery codes.') }}
    </div>

    <form method="POST" action="{{ route('two-factor.verify.post') }}" id="twoFactorForm">
        @csrf

        <!-- Authentication Code -->
        <div>
            <x-input-label for="code" :value="__('Code')" />
            <x-text-input id="code" 
                          name="code" 
                          type="text" 
                          class="block mt-1 w-full text-center text-lg tracking-widest" 
                          required 
                          autofocus 
                          autocomplete="one-time-code"
                          maxlength="6"
                          pattern="[0-9]{6}"
                          placeholder="000000" />
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                {{ __('Verify') }}
            </x-primary-button>
        </div>
    </form>

    <div class="mt-6 text-center">
        <p class="text-sm text-gray-600 dark:text-gray-400">
            {{ __('Enter a 6-digit code from your authenticator app or an 8-character recovery code.') }}
        </p>
        
        <div class="mt-4">
            <a href="{{ route('login') }}" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">
                {{ __('Back to Login') }}
            </a>
        </div>
    </div>


</x-guest-layout>
