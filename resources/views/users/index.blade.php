<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('List of all users') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="flex flex-wrap items-center gap-4 mb-4">
                        <x-secondary-button>
                            <a href="{{ route('register') }}" class="w-full">
                                {{ __('Register New User') }}
                            </a>
                        </x-secondary-button>

                        <form method="GET" class="flex flex-1 items-center gap-2">
                            @foreach(request()->except('search', 'page') as $key => $value)
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endforeach

                            <input
                                type="text"
                                name="search"
                                value="{{ request('search') }}"
                                placeholder="{{ __('Search by name or email') }}"
                                class="w-full px-3 py-1 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 dark:bg-gray-800 dark:text-white"
                            />

                            <x-secondary-button type="submit">
                                {{ __('Search') }}
                            </x-secondary-button>

                            @if(request('search'))
                                <a href="{{ route('users.index', request()->except('search', 'page')) }}" class="text-sm text-gray-200 hover:underline ml-2">
                                    {{ __('Clear') }}
                                </a>
                            @endif
                        </form>
                    </div>
                    @if (Auth::user()->isPrivileged())
                        @php
                        if (!function_exists('sortLink')) {
                            function sortLink($column, $label) {
                                $currentSort = request('sort', 'name');
                                $currentDir = request('direction', 'asc');

                                $isCurrent = $currentSort === $column;
                                $newDir = ($isCurrent && $currentDir === 'asc') ? 'desc' : 'asc';

                                $query = request()->except('page');
                                $query['sort'] = $column;
                                $query['direction'] = $newDir;

                                $url = url()->current() . '?' . http_build_query($query);
                                $icon = $isCurrent ? ($currentDir === 'asc' ? '↑' : '↓') : '';

                                return '<a href="' . $url . '" class="hover:underline">' . $label . ' ' . $icon . '</a>';
                            }
                        }
                        @endphp
                        <table class="table-auto w-full mt-4">
                            <thead>
                                <tr class="bg-gray-100 dark:bg-gray-700">
                                    <th class="px-4 py-2">{!! sortLink('name', __('Name')) !!}</th>
                                    <th class="px-4 py-2">{!! sortLink('email', __('Email')) !!}</th>
                                    @if (Auth::user()->isAdmin())
                                        <th class="px-2 py-2 whitespace-nowrap w-[1%]">{{ __('Role') }}</th>
                                    @endif
                                    <th class="px-2 py-2 whitespace-nowrap w-[1%]">{{ __('Gender') }}</th>
                                    <th class="px-2 py-2 whitespace-nowrap w-[1%]">{{ __('Microsoft') }}</th>
                                    <th class="px-2 py-2 whitespace-nowrap w-[1%]">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($users as $user)
                                    @if (Auth::user()->isPrivileged())
                                        <tr>
                                            <td class="border px-4 py-2">{{ $user->pedagogical_name }} {{ $user->name }}</td>
                                            <td class="border px-4 py-2 break-words">{{ $user->email }}</td>
                                            @if (Auth::user()->isAdmin())
                                                <td class="border px-2 py-2 whitespace-nowrap text-sm">{{ __($user->role) }}</td>
                                            @endif
                                            <td class="border px-2 py-2 whitespace-nowrap text-sm">
                                                {{ $user->gender == '0' ? __('Female') : __('Male') }}
                                            </td>
                                            <td class="border px-2 py-2 whitespace-nowrap text-sm text-center">
                                                @if(!empty($user->ms_id))
                                                    <span class="text-blue-600 dark:text-blue-400" title="{{ __('Microsoft Account Linked') }}">✅</span>
                                                @else
                                                    <span class="text-gray-400" title="{{ __('No Microsoft Account') }}">❌</span>
                                                @endif
                                            </td>
                                            <td class="border px-2 py-2 whitespace-nowrap text-sm">
                                                <a href="{{ route('users.edit', $user) }}" class="hover:underline font-semibold">{{ __('Edit') }}</a>
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                        <div class="mt-4">
                            <form method="GET" class="mb-4 flex items-center space-x-2">
                                <!-- Preserve other query params -->
                                @foreach(request()->except('perPage', 'page') as $key => $value)
                                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                @endforeach

                                <x-select-dropdown
                                    label="{{ __('Records per page') }}:"
                                    name="perPage"
                                    :options="[10 => '10', 20 => '20', 50 => '50', 100 => '100']"
                                    :selected="request('perPage', 20)"
                                />
                            </form>
                            {{ $users->links() }}
                        </div>
                    @else
                        <p class="text-center text-lg">{{ __('You are not authorized to view this page.') }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

