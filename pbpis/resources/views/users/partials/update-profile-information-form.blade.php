<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>


    <form method="post" action="{{ route('users.updateProfile', $user) }}">
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
        </div>

        <div>
            <x-input-label for="pedagogical_name" :value="__('Pedagogical Name')" />
            <x-text-input id="pedagogical_name" class="block mt-1 w-full" type="text" name="pedagogical_name" :value="strtolower(old('pedagogical_name', $user->pedagogical_name))" autocomplete="pedagogical_name" style="text-transform: lowercase;" />
            <x-input-error class="mt-2" :messages="$errors->get('pedagogical_name')" />
        </div>

        <div>
            <x-input-label for="role" :value="__('Role')" />
            <select id="role" name="role" class="block mt-1 w-full">
                @if (Auth::user()->role === 'Sekretorius')
                    <option value="Balsuojantysis" {{ old('role') == 'Balsuojantysis' || $user->role == 'Balsuojantysis' ? 'selected' : '' }}>{{ __('Balsuojantysis') }}</option>
                @else
                    <option value="IT administratorius" {{ old('role') == 'IT administratorius' || $user->role == 'IT administratorius' ? 'selected' : '' }}>{{ __('IT administratorius') }}</option>
                    <option value="Sekretorius" {{ old('role') == 'Sekretorius' || $user->role == 'Sekretorius' ? 'selected' : '' }}>{{ __('Sekretorius') }}</option>
                    <option value="Balsuojantysis" {{ old('role') == 'Balsuojantysis' || $user->role == 'Balsuojantysis' ? 'selected' : '' }}>{{ __('Balsuojantysis') }}</option>
                @endif
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
</section>

