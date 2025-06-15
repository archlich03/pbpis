<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('List of all meetings') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <table class="table-fixed w-full">
                        <thead>
                            <tr class="bg-gray-100 dark:bg-gray-700">
                                <th class="px-4 py-2 w-1/6">Date</th>
                                <th class="px-4 py-2 w-1/6">Body</th>
                                <th class="px-4 py-2 w-1/6">Type</th>
                                <th class="px-4 py-2 w-1/6">Status</th>
                                <th class="px-4 py-2 w-1/6">Secretary</th>
                                <th class="px-4 py-2 w-1/6">Vote start</th>
                                <th class="px-4 py-2 w-1/6">Vote end</th>
                                <th class="px-4 py-2 w-1/6">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($meetings as $meeting)
                            <tr class="text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                <td class="px-4 py-2">{{ $meeting->meeting_date->format('Y-m-d') }}</td>
                                <td class="px-4 py-2"><a href="{{ route('bodies.show', $meeting->body) }}" class="text-blue-500 hover:underline">{{ $meeting->body->title }} ({{ $meeting->body->is_ba_sp? 'BA' : 'MA' }})</a></td>
                                <td class="px-4 py-2">{{ $meeting->is_evote ? 'Electronic' : 'Physical' }}</td>
                                <td class="px-4 py-2">{{ $meeting->status }}</td>
                                <td class="px-4 py-2">{{ optional($meeting->secretary)->pedagogical_name ?? '' }} {{ optional($meeting->secretary)->name ?? '' }}</td>
                                <td class="px-4 py-2">{{ $meeting->vote_start->format('Y-m-d H:i') }}</td>
                                <td class="px-4 py-2">{{ $meeting->vote_end->format('Y-m-d H:i') }}</td>
                                <td class="px-4 py-2">
                                    <a href="{{ route('meetings.show', $meeting) }}" class="text-blue-500 hover:underline"><b>View</b></a>
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