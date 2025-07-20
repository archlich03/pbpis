<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <details class="gap-4" open>
                    <summary class="p-6 text-gray-900 dark:text-gray-100">
                        <strong>{{ __('Upcoming Meetings') }}</strong>
                    </summary>
                    <div class="bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-gray-100 p-4 rounded shadow">
                        @foreach ($meetings as $meeting)
                                <div class="p-3 mb-2 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors duration-200">
                                    <a href="{{ route('meetings.show', $meeting) }}" class="block text-gray-800 dark:text-gray-200 hover:text-blue-600 dark:hover:text-blue-400 transition-colors duration-200">
                                        <h2 class="text-xl font-semibold mb-1">{{ $meeting->body->title }} ({{ $meeting->body->is_ba_sp ? 'BA' : 'MA' }}) - {{ $meeting->meeting_date->format('Y-m-d') }}</h2>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">
                                            {{ __($meeting->status) }}
                                        </p>
                                    </a>
                                </div>
                        @endforeach
                    </div>
                </details>
            </div>

            <div class="mt-6 bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <details>
                    <summary class="p-6 text-gray-900 dark:text-gray-100">
                        <strong>{{ __('Related Bodies') }}</strong>
                    </summary>
                    <div class="bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-gray-100 p-4 rounded shadow">
                        @foreach ($bodies as $body)
                            <div class="hover:bg-gray-300 dark:hover:bg-gray-500">
                                <a href="{{ route('bodies.show', $body) }}" class="text-gray-300">
                                    <h2 class="text-xl font-semibold">{{ $body->title }} ({{ $body->is_ba_sp ? 'BA' : 'MA' }})</h2>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ __('View') }}
                                    </p>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </details>
            </div>
        </div>
    </div>
</x-app-layout>
