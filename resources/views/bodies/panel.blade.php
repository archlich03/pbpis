<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('List of all bodies') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                @if (in_array(Auth::user()->role, ['IT administratorius']))
                    <div class="px-6 py-4">
                        <a href="{{ route('bodies.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-gray-300 active:bg-gray-900 dark:active:bg-gray-600 focus:outline-none focus:border-gray-900 dark:focus:border-gray-300 focus:ring focus:ring-gray-300 dark:focus:ring-gray-800 disabled:opacity-25 transition">
                            Create new body
                        </a>
                    </div>
                @endif
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <table class="table-fixed w-full">
                        <thead>
                            <tr class="bg-gray-100 dark:bg-gray-700">
                                <th class="px-4 py-2 w-1/6">Name</th>
                                <th class="px-4 py-2 w-1/6">Chairman</th>
                                <th class="px-4 py-2 w-1/6">Type</th>
                                <th class="px-4 py-2 w-1/6">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($bodies as $body)
                                <tr>
                                    <td class="border px-4 py-2">{{ $body->name }}</td>
                                    <td class="border px-4 py-2">
                                        {{ optional($body->chairman)->pedagogical_name ?? '' }} {{ optional($body->chairman)->name ?? '' }}
                                    </td>
                                    <td class="border px-4 py-2">{{ $body->is_ba_sp ? 'BA' : 'MA' }}</td>
                                    <td class="border px-4 py-2">
                                        <a href="{{ route('bodies.edit', $body) }}">Edit</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
