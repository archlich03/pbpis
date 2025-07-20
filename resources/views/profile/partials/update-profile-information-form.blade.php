<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800 dark:text-gray-200">
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification" class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600 dark:text-green-400">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div>
            <x-input-label for="pedagogical_name" :value="__('Pedagogical Name')" />
            <x-text-input id="pedagogical_name" class="block mt-1 w-full" type="text" name="pedagogical_name" :value="old('pedagogical_name', $user->pedagogical_name)" autocomplete="pedagogical_name" />
            <x-input-error class="mt-2" :messages="$errors->get('pedagogical_name')" />
        </div>

        <div>
            <x-input-label for="role" :value="__('Role')" />
            <select id="role" name="role" class="block mt-1 w-full">
                <option value="IT administratorius" {{ old('role') == 'IT administratorius' || $user->role == 'IT administratorius' ? 'selected' : '' }}>{{ __('IT administratorius') }}</option>
                <option value="Sekretorius" {{ old('role') == 'Sekretorius' || $user->role == 'Sekretorius' ? 'selected' : '' }}>{{ __('Sekretorius') }}</option>
                <option value="Balsuojantysis" {{ old('role') == 'Balsuojantysis' || $user->role == 'Balsuojantysis' ? 'selected' : '' }}>{{ __('Balsuojantysis') }}</option>
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('role')" />
        </div>

        <div>
            <x-input-label for="gender" :value="__('Gender')" />
            <select id="gender" name="gender" class="block mt-1 w-full">
                <option value="0" {{ old('gender') == '0' || $user->gender == '0' ? 'selected' : '' }}>{{ __('Female') }}</option>
                <option value="1" {{ old('gender') == '1' || $user->gender == '1' ? 'selected' : '' }}>{{ __('Male') }}</option>
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('gender')" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600 dark:text-gray-400"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>

    <div class="mt-10 pt-6 border-t border-gray-200">
        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
            {{ __('Microsoft Account') }}
        </h3>

        @if(empty($user->ms_id))
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                {{ __('Connect your Microsoft account for easier login.') }}
            </p>
            <a href="{{ route('login.microsoft') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                {{ __('Connect Microsoft Account') }}
            </a>
        @else
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                {{ __('Your Microsoft account is connected.') }}
            </p>
            <form method="POST" action="{{ route('disconnect.microsoft') }}" class="inline">
                @csrf
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:bg-red-700 active:bg-red-800 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    {{ __('Disconnect Microsoft Account') }}
                </button>
            </form>
        @endif
    </div>
</section>
