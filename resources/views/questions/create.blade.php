<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Create Question') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-500">
                    <form method="POST" action="{{ route('questions.store', $meeting) }}">
                        @csrf

                        <div>
                            <x-input-label for="title" value="{{ __('Title') }}:" />
                            <x-text-input id="title" name="title" type="text" class="block mt-1 w-full" value="{{ old('title') }}" />
                        </div>

                        <div class="mt-4">
                            <x-input-label for="type" value="{{ __('Type') }}:" />
                            <select id="type" name="type" class="block mt-1 w-full">
                                @foreach (\App\Models\Question::STATUSES as $status)
                                    <option value="{{ $status }}" {{ old('type') == $status ? 'selected' : '' }}>{{ $status }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mt-4">
                            <x-input-label for="presenter_id" value="{{ __('Presenter') }}:" />
                            <select id="presenter_id" name="presenter_id" class="block mt-1 w-full">
                                @foreach ($users as $user)
                                    @if (!$user->isSecretary()) continue; @endif
                                    <option value="{{ $user->user_id }}" {{ old('presenter_id') == $user->user_id ? 'selected' : '' }}>{{ $user->pedagogical_name }} {{ $user->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mt-4">
                            <x-input-label for="decision" value="{{ ('Question decision') }}:" />
                            <x-text-input id="decision" name="decision" type="text" class="block mt-1 w-full" value="{{ old('decision') }}" />
                        </div>

                        <div class="mt-4">
                            <x-input-label for="summary" value="{{ __('Question summary') }}:" />
                            <textarea id="summary" name="summary" class="block mt-1 w-full rounded-md shadow-sm border-gray-300 dark:border-gray-700 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" rows="3">{{ old('summary') }}</textarea>
                        </div>

                        <div class="flex items-center justify-end mt-4">
                            <x-primary-button class="ml-4">
                                {{ __('Create Question') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

