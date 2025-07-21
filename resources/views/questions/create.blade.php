@section('title', __('Create Question') . ' - ' . config('app.name', 'PBPIS'))

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
                            <select id="type" name="type" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm block mt-1 w-full">
                                @foreach (\App\Models\Question::STATUSES as $status)
                                    <option value="{{ $status }}" {{ old('type') == $status ? 'selected' : '' }}>{{ $status }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mt-4">
                            <x-input-label for="presenter_id" value="{{ __('Presenter') }}:" />
                            <select id="presenter_id" name="presenter_id" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm block mt-1 w-full">
                                @foreach ($users as $user)
                                    @if (!$user->isSecretary()) continue; @endif
                                    <option value="{{ $user->user_id }}" {{ old('presenter_id') == $user->user_id ? 'selected' : '' }}>{{ $user->pedagogical_name }} {{ $user->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mt-4">
                            <x-input-label for="decision" value="{{ __('Question decision') }}:" />
                            <x-text-input id="decision" name="decision" type="text" class="block mt-1 w-full" value="{{ old('decision') }}" />
                        </div>

                        <div class="mt-4">
                            <x-input-label for="summary" value="{{ __('Question summary') }}:" />
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ __('You can use HTML formatting (e.g., <strong>, <em>, <p>, <br>, <ul>, <li>)') }}</p>
                            <textarea id="summary" name="summary" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm block mt-1 w-full" rows="6" placeholder="{{ __('Enter detailed summary with HTML formatting if needed...') }}">{{ old('summary') }}</textarea>
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

