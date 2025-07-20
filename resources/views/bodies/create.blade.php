<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Create new body') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <form method="post" action="{{ route('bodies.store') }}" class="p-6 space-y-6">
                    @csrf
                    <x-input-label for="title" value="{{ __('Body name') }}" />
                    <x-text-input id="title" name="title" type="text" class="block w-full" />

                    <div class="mt-6">
                        <x-input-label for="classification" value="{{ __('Classification') }}" />
                        <select id="classification" name="classification" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm mt-1 block w-full">
                            <option value="SPK" selected>SPK</option>
                        </select>
                    </div>

                    <div class="mt-6">
                        <x-input-label for="is_ba_sp" value="{{ __('Type') }}" class="flex items-center" />
                        <div class="mt-1 flex items-center space-x-4">
                            <div class="flex items-center">
                                <input id="is_ba_sp_0" type="radio" name="is_ba_sp" value="0" class="focus:ring-indigo-500 dark:focus:ring-indigo-600 h-4 w-4 text-indigo-600 border-gray-300 dark:border-gray-700 dark:bg-gray-900 rounded" />
                                <label for="is_ba_sp_0" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                                    MA
                                </label>
                            </div>
                            <div class="flex items-center">
                                <input id="is_ba_sp_1" type="radio" name="is_ba_sp" value="1" class="focus:ring-indigo-500 dark:focus:ring-indigo-600 h-4 w-4 text-indigo-600 border-gray-300 dark:border-gray-700 dark:bg-gray-900 rounded" />
                                <label for="is_ba_sp_1" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                                    BA
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6">
                        <x-input-label for="chairman_id" value="{{ __('Chairperson') }}" />
                        <select id="chairman_id" name="chairman_id" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm block mt-1 w-full" required>
                            <option value="">{{ __('Select chairperson') }}</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->user_id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>

                    </div>

                    <div class="mt-6 space-y-2">
                        <x-input-label for="members" value="{{ __('Members') }}" />
                        @foreach ($users as $user)
                            <div class="flex items-center">
                                <input id="members_{{ $user->user_id }}" type="checkbox" name="members[]" value="{{ $user->user_id }}" class="focus:ring-indigo-500 dark:focus:ring-indigo-600 h-4 w-4 text-indigo-600 border-gray-300 dark:border-gray-700 dark:bg-gray-900 rounded" />
                                <label for="members_{{ $user->user_id }}" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                                    {{ $user->name }}{{ $user->pedagogical_name ? ' (' . $user->pedagogical_name . ')' : '' }}
                                </label>
                            </div>
                        @endforeach
                    </div>

                    <div class="flex items-center gap-4 mt-6">
                        <x-primary-button>{{ __('Create') }}</x-primary-button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</x-app-layout>
