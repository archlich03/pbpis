@section('title', __('Register') . ' - ' . config('app.name', 'POBIS'))

<x-guest-layout>
    <div class="mb-4 flex justify-between">
        <a class="text-base font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-100" href="{{ route('users.index') }}">
            {{ __('Back') }}
        </a>
    </div>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        @if ($errors->any())
            <div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-red-900/20 dark:text-red-400" role="alert">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />

            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />
        </div>

        <!-- Gender -->
        <div class="mt-4">
            <x-input-label for="gender" :value="__('Gender')" />
            <select id="gender" name="gender" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm block mt-1 w-full">
                <option value="0" {{ old('gender') == '0' ? 'selected' : '' }}>{{ __('Female') }}</option>
                <option value="1" {{ old('gender') == '1' ? 'selected' : '' }}>{{ __('Male') }}</option>
            </select>
        </div>

        <!-- Role -->
        <div class="mt-4">
            <x-input-label for="role" :value="__('Role')" />
            <select id="role" name="role" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm block mt-1 w-full">
                @if (Auth::user()->role === 'Sekretorius')
                    <option value="Balsuojantysis" {{ old('role') == 'Balsuojantysis' || Auth::user()->role == 'Balsuojantysis' ? 'selected' : '' }}>
                        {{ __('Balsuojantysis') }}
                    </option>
                @else
                    <option value="IT administratorius" {{ old('role') == 'IT administratorius' || Auth::user()->role == 'IT administratorius' ? 'selected' : '' }}>
                        {{ __('IT administratorius') }}
                    </option>
                    <option value="Sekretorius" {{ old('role') == 'Sekretorius' || Auth::user()->role == 'Sekretorius' ? 'selected' : '' }}>
                        {{ __('Sekretorius') }}
                    </option>
                    <option value="Balsuojantysis" {{ old('role') == 'Balsuojantysis' || Auth::user()->role == 'Balsuojantysis' ? 'selected' : '' }}>
                        {{ __('Balsuojantysis') }}
                    </option>
                @endif
            </select>
        </div>

        <!-- Pedagogical Name -->
        <div class="mt-4">
            <x-input-label for="pedagogical_name" :value="__('Pedagogical Name')" />
            <x-text-input id="pedagogical_name" class="block mt-1 w-full" type="text" name="pedagogical_name" :value="old('pedagogical_name')" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button class="ms-4">
                {{ __('Register') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>

