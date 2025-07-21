@section('title', __('Body Details') . ' - ' . config('app.name', 'PBPIS'))

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ $body->title }} ({{ $body->is_ba_sp ? 'BA' : 'MA' }})
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                @if (Auth::user()->isPrivileged())
                    <div class="flex space-x-2 px-6 py-4">
                        <x-primary-button>
                            <a href="{{ route('bodies.edit', $body) }}" class="w-full">
                                {{ __('Edit body') }}
                            </a>
                        </x-primary-button>

                        @if (Auth::user()->isPrivileged())
                            <x-primary-button>
                                <a href="{{ route('meetings.create', $body) }}" class="w-full">
                                    {{ __('Create Meeting') }}
                                </a>
                            </x-primary-button>
                        @endif

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
                                        {{ __('This action is irreversible and will delete all related meeting, question and vote information. Please confirm that you want to delete this body.') }}
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
                @endif
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-xl font-semibold mb-4">{{ __('Body Information') }}</h3>
                    <ul class="list-disc pl-4">
                        <li><strong>{{ __('Title') }}:</strong> {{ $body->title }} ({{ $body->is_ba_sp ? 'BA' : 'MA' }})</li>
                        <li><strong>{{ $body->chairman->gender ? __('Chairman') : __('Chairwoman') }}:</strong> {{ optional($body->chairman)->pedagogical_name ?? '' }} {{ optional($body->chairman)->name ?? '' }}</li>
                        <li><strong>{{ __('Members') }}:</strong>
                            <ul class="list-disc pl-8">
                                @foreach ($body->members as $member)
                                    <li>{{ $member->pedagogical_name ?? '' }} {{ $member->name ?? '' }}</li>
                                @endforeach
                            </ul>
                        </li>
                    </ul>

                    <hr class="border-t-2 border-gray-300 dark:border-gray-600 mt-4 mb-4">

                    <h3 class="text-xl font-semibold mt-8 mb-4">{{ __('Meetings') }}</h3>
                    <table class="w-full mt-4">
                        <thead class="text-gray-800 dark:text-gray-200">
                            <tr>
                                <th class="px-4 py-2">{{ __('Meeting date') }}</th>
                                <th class="px-4 py-2">{{ __('Meeting type') }}</th>
                                <th class="px-4 py-2">{{ __('Status') }}</th>
                                <th class="px-4 py-2">{{ __('Associated secretary') }}</th>
                                <th class="px-4 py-2">{{ __('Vote start') }}</th>
                                <th class="px-4 py-2">{{ __('Vote end') }}</th>
                                <th class="px-4 py-2">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($body->meetings as $meeting)
                            <tr class="text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                <td class="px-4 py-2">{{ $meeting->meeting_date->format('Y-m-d') }}</td>
                                <td class="px-4 py-2">{{ $meeting->is_evote ? __('Electronic') : __('Physical') }}</td>
                                <td class="px-4 py-2">{{ __($meeting->status) }}</td>
                                <td class="px-4 py-2">{{ optional($meeting->secretary)->name ?? '' }}</td>                                
                                <td class="px-4 py-2">{{ $meeting->vote_start->format('Y-m-d H:i') }}</td>
                                <td class="px-4 py-2">{{ $meeting->vote_end->format('Y-m-d H:i') }}</td>
                                <td class="px-4 py-2">
                                    <a href="{{ route('meetings.show', $meeting) }}" class="text-blue-500 hover:underline"><b>{{ __('View') }}</b></a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
            </div>
        </div>
    </div>
</x-app-layout>

