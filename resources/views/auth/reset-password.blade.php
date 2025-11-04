@section('title', __('Reset Password') . ' - ' . config('app.name', 'POBIS'))

<x-guest-layout>
    <form method="POST" action="{{ route('password.store') }}">
        @csrf

        <!-- Password Reset Token -->
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />

            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                                type="password"
                                name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                {{ __('Reset Password') }}
            </x-primary-button>
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
</x-guest-layout>
