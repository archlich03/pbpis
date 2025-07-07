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
                    @php
                    if (!function_exists('sortLink')) {
                        function sortLink($column, $label) {
                            $currentSort = request('sort', 'meeting_date');
                            $currentDir = request('direction', 'desc');

                            $isCurrent = $currentSort === $column;
                            $newDir = ($isCurrent && $currentDir === 'asc') ? 'desc' : 'asc';

                            $query = request()->except('page');
                            $query['sort'] = $column;
                            $query['direction'] = $newDir;

                            $url = url()->current() . '?' . http_build_query($query);
                            $icon = $isCurrent ? ($currentDir === 'asc' ? '↑' : '↓') : '';

                            return '<a href="' . $url . '" class="hover:underline whitespace-nowrap">' . $label . ' ' . $icon . '</a>';
                        }
                    }
                    @endphp

                    <table class="table-auto w-full">
                        <thead>
                            <tr class="bg-gray-100 dark:bg-gray-700">
                                <th class="px-4 py-2 break-words">{!! sortLink('meeting_date', __('Date')) !!}</th>
                                <th class="px-4 py-2 break-words">{!! sortLink('body_id', __('Body')) !!}</th>
                                <th class="px-4 py-2 break-words">{!! sortLink('status', __('Status')) !!}</th>
                                <th class="px-4 py-2 break-words">{!! sortLink('vote_start', __('Vote start')) !!}</th>
                                <th class="px-4 py-2 break-words">{!! sortLink('vote_end', __('Vote end')) !!}</th>
                                <th class="px-4 py-2">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($meetings as $meeting)
                            <tr class="text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                <td class="px-4 py-2">{{ $meeting->meeting_date->format('Y-m-d') }}</td>
                                <td class="px-4 py-2"><a href="{{ route('bodies.show', $meeting->body) }}">{{ $meeting->body->title }} ({{ $meeting->body->is_ba_sp? 'BA' : 'MA' }})</a></td>
                                <td class="px-4 py-2">{{ __($meeting->status) }}</td>
                                <td class="px-4 py-2">{{ $meeting->vote_start->format('Y-m-d H:i') }}</td>
                                <td class="px-4 py-2">{{ $meeting->vote_end->format('Y-m-d H:i') }}</td>
                                <td class="px-4 py-2">
                                    <a href="{{ route('meetings.show', $meeting) }}" class="hover:underline"><b>{{ __('View') }}</b></a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="mt-4">
                        <form method="GET" class="mb-4 flex items-center space-x-2">
                            @foreach(request()->except('perPage', 'page') as $key => $value)
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endforeach

                            <x-select-dropdown
                                label="{{ __('Records per page') }}:"
                                name="perPage"
                                :options="[10 => '10', 20 => '20', 50 => '50', 100 => '100']"
                                :selected="request('perPage', 20)"
                                onchange="this.form.submit()"
                            />
                        </form>
                        {{ $meetings->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>