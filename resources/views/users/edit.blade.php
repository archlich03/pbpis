<x-app-layout>
    @if (Auth::user()->isPrivileged())
        <x-slot name="header">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Edit user'). ": " . $user->name }}
            </h2>
        </x-slot>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                    <div class="max-w-xl">
                        @include('users.partials.update-profile-information-form')
                    </div>
                </div>
                    <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                        <div class="max-w-xl">
                            @include('users.partials.update-password-form')
                        </div>
                    </div>

                @if (Auth::user()->role === 'IT administratorius')

                    <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                        <div class="max-w-xl">
                            @include('users.partials.delete-user-form')
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif
</x-app-layout>
