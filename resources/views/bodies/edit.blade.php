<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <form method="post" action="{{ route('bodies.update', $body) }}" class="p-6 space-y-6">
                    @csrf
                    @method('patch')

                    <x-input-label for="title" value="Body name:" />
                    <x-text-input id="title" name="title" type="text" class="block w-full" value="{{ $body->title }}" />

                    <div class="mt-6">
                        <x-input-label for="classification" value="Classification" />
                        <select id="classification" name="classification" class="mt-1 block w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            <option value="SPK" {{ $body->classification === 'SPK' ? 'selected' : '' }}>SPK</option>
                        </select>
                    </div>

                    <div class="mt-6">
                        <x-input-label for="is_ba_sp" value="Type" class="flex items-center" />
                        <div class="mt-1 flex items-center space-x-4">
                            <div class="flex items-center">
                                <input id="is_ba_sp_0" type="radio" name="is_ba_sp" value="0" class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded" {{ $body->is_ba_sp === 0 ? 'checked' : '' }} />
                                <label for="is_ba_sp_0" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                                    MA
                                </label>
                            </div>
                            <div class="flex items-center">
                                <input id="is_ba_sp_1" type="radio" name="is_ba_sp" value="1" class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded" {{ $body->is_ba_sp === 1 ? 'checked' : '' }} />
                                <label for="is_ba_sp_1" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                                    BA
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6">
                        <x-input-label for="chairman_id" value="Chairman" />
                        <select id="chairman_id" name="chairman_id" required>
                            <option value="">Select chairperson</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->user_id }}" {{ $body->chairman_id === $user->user_id ? 'selected' : '' }}>{{ $user->name }}</option>
                            @endforeach
                        </select>

                    </div>

                    <div class="mt-6 space-y-2">
                        <x-input-label for="members" value="Members" />
                        @foreach ($users as $user)
                            <div class="flex items-center">
                                <input id="members_{{ $user->user_id }}" type="checkbox" name="members[]" value="{{ $user->user_id }}" class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded" {{ $body->members->contains($user) ? 'checked' : '' }} />
                                <label for="members_{{ $user->user_id }}" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                                    {{ $user->name }} ({{ $user->pedagogical_name }})
                                </label>
                            </div>
                        @endforeach
                    </div>

                    <div class="flex items-center gap-4 mt-6">
                        <x-primary-button>{{ __('Update') }}</x-primary-button>
                    </div>

                </form>

                @if (Auth::user()->isAdmin())
                    <div x-data="{ confirmingBodyDeletion: false }"
                        class="relative">
                        <x-danger-button
                            x-on:click.prevent="confirmingBodyDeletion = true">
                            {{ __('Delete Body') }}
                        </x-danger-button>

                        <div
                            x-show="confirmingBodyDeletion"
                            @click.outside="confirmingBodyDeletion = false"
                            class="fixed z-50 inset-0 bg-gray-900 bg-opacity-50 dark:bg-gray-800 dark:bg-opacity-50 flex items-center justify-center"
                            style="backdrop-filter: blur(2px);">
                            
                            <div class="bg-gray-800 dark:bg-gray-700 p-6 rounded shadow-md max-w-md mx-auto">
                                <h2 class="text-lg font-medium text-gray-300 dark:text-gray-100">
                                    {{ __('Are you sure you want to delete this body?') }}
                                </h2>

                                <p class="mt-4 text-sm text-gray-600 dark:text-gray-400">
                                    {{ __('This action is irreversible. Please confirm that you want to delete this body') }}
                                </p>

                                <form method="post" action="{{ route('bodies.destroy', $body) }}">
                                    @csrf
                                    @method('delete')

                                    <div class="mt-6 flex justify-end">
                                        <x-secondary-button x-on:click="confirmingBodyDeletion = false">
                                            {{ __('Cancel') }}
                                        </x-secondary-button>

                                        <x-danger-button type="submit">
                                            {{ __('Delete') }}
                                        </x-danger-button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>


