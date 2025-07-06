<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('List of all bodies') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                @if (Auth::user()->isAdmin())
                    <div class="px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <x-secondary-button>
                            <a href="{{ route('bodies.create') }}">
                                {{ __('Create new body') }}
                            </a>
                        </x-secondary-button>

                        <form method="GET" class="flex-1 flex items-center gap-2">
                            @foreach(request()->except('search', 'page') as $key => $value)
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endforeach

                            <input
                                type="text"
                                name="search"
                                value="{{ request('search') }}"
                                placeholder="{{ __('Search by title') }}"
                                class="flex-1 px-3 py-1 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 dark:bg-gray-800 dark:text-white"
                            />

                            <x-secondary-button type="submit">
                                {{ __('Search') }}
                            </x-secondary-button>

                            @if(request('search'))
                                <a href="{{ route('bodies.panel', request()->except('search', 'page')) }}" class="text-sm text-gray-200 hover:underline ml-2">
                                    {{ __('Clear') }}
                                </a>
                            @endif
                        </form>
                    </div>
                @endif

                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <table class="table-auto w-full">
                        <thead>
                            <tr class="bg-gray-100 dark:bg-gray-700">
                                <th class="px-4 py-2">{{ __('Name') }}</th>
                                <th class="px-4 py-2">{{ __('Chairperson') }}</th>
                                <th class="px-2 py-2 whitespace-nowrap w-[1%]">{{ __('Type') }}</th>
                                <th class="px-2 py-2 whitespace-nowrap w-[1%]" colspan="{{ Auth::user()->isPrivileged() ? 2 : 1 }}">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($bodies as $body)
                                @if (Auth::user()->isVoter() && !$body->members->contains(Auth::user()))
                                    @continue
                                @endif
                                <tr>
                                    <td class="border px-4 py-2">{{ $body->title }}</td>
                                    <td class="border px-4 py-2">
                                        {{ optional($body->chairman)->pedagogical_name ?? '' }} {{ optional($body->chairman)->name ?? '' }}
                                    </td>
                                    <td class="border px-2 py-2 whitespace-nowrap">{{ $body->is_ba_sp ? 'BA' : 'MA' }}</td>
                                    <td class="border px-2 py-2 whitespace-nowrap text-sm">
                                        <a href="{{ route('bodies.show', $body) }}"><b>{{ __('View') }}</b></a>
                                    </td>
                                    <td class="border px-2 py-2 whitespace-nowrap text-sm">
                                        @if (Auth::user()->isPrivileged())
                                            <a href="{{ route('bodies.edit', $body) }}"><b>{{ __('Edit') }}</b></a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <div class="px-6 py-4">
                    {{ $bodies->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
