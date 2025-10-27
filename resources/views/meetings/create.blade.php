@section('title', __('Create Meeting') . ' - ' . config('app.name', 'POBIS'))

<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <form method="post" action="{{ route('meetings.store', $body->body_id) }}" class="p-6 space-y-6">
                    @csrf

                    <div class="mt-6 text-center">
                        <h2 class="text-3xl font-bold mt-6 mb-2 text-gray-800 dark:text-gray-200">
                            {{ __('New meeting for') }} {{ $body->title }} ({{ $body->is_ba_sp ? 'BA' : 'MA' }})
                        </h2>
                    </div>

                    <x-user-search-select 
                        name="secretary_id"
                        :label="__('Associated secretary') . ':'"
                        :users="$users"
                        :selected="Auth::user()->isSecretary() ? Auth::user() : null"
                        :filter="fn($user) => $user->isSecretary()"
                        required
                    />

                    <div class="mt-6">
                        <x-input-label for="is_evote" value="{{ __('Meeting type') }}:" class="flex items-center" />
                        <div class="mt-1 flex items-center space-x-4">
                            <div class="flex items-center">
                                <input id="is_evote_0" type="radio" name="is_evote" value="0" class="focus:ring-indigo-500 dark:focus:ring-indigo-600 h-4 w-4 text-indigo-600 border-gray-300 dark:border-gray-700 dark:bg-gray-900 rounded" required />
                                <label for="is_evote_0" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                                    {{ __('Physical') }}
                                </label>
                            </div>
                            <div class="flex items-center">
                                <input id="is_evote_1" type="radio" name="is_evote" value="1" class="focus:ring-indigo-500 dark:focus:ring-indigo-600 h-4 w-4 text-indigo-600 border-gray-300 dark:border-gray-700 dark:bg-gray-900 rounded" required />
                                <label for="is_evote_1" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                                    {{ __('Electronic') }}
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6">
                        <x-input-label for="meeting_date" value="{{ __('Meeting date') }}:" />
                        <x-text-input id="meeting_date" name="meeting_date" type="date" class="block w-full" />
                    </div>


                    <div class="mt-6">
                        <x-input-label for="vote_start" value="{{ __('Vote start') }}:" />
                        <x-text-input id="vote_start" name="vote_start" type="datetime-local" class="block w-full" />
                    </div>

                    <div class="mt-6">
                        <x-input-label for="vote_end" value="{{ __('Vote end') }}:" />
                        <x-text-input id="vote_end" name="vote_end" type="datetime-local" class="block w-full" />
                    </div>

                    <div class="flex items-center gap-4 mt-6">
                        <x-primary-button>{{ __('Create') }}</x-primary-button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</x-app-layout>

