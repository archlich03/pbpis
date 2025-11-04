<section class="space-y-6 relative z-10">
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Delete Account') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('This account will be soft-deleted and can be restored within :days days. After this period, all account data will be permanently deleted.', ['days' => config('app.data_retention_days', 455)]) }}
        </p>
    </header>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
        class="relative z-10"
    >{{ __('Delete Account') }}</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('users.destroy', $user) }}" class="p-6">
            @csrf
            @method('delete')

            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ __('Are you sure you want to delete this account?') }}
            </h2>

            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                {{ __('This account will be soft-deleted and can be restored within :days days. After this period, all account data will be permanently deleted.', ['days' => config('app.data_retention_days', 455)]) }}
            </p>

            <p class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">
                {{ __('User: :name (:email)', ['name' => $user->name, 'email' => $user->email]) }}
            </p>
            
            @if($errors->userDeletion->has('delete'))
                <div class="mt-4 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-md">
                    <p class="text-sm text-red-800 dark:text-red-200">
                        {{ $errors->userDeletion->first('delete') }}
                    </p>
                </div>
            @endif

            <div class="mt-6 flex justify-end">
                <x-secondary-button type="button" x-on:click="$dispatch('close')">
                    {{ __('Cancel') }}
                </x-secondary-button>

                <x-danger-button type="submit" class="ms-3" x-on:click.stop>
                    {{ __('Delete Account') }}
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
