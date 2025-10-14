
@if (!Auth::user()->isPrivileged())
    <ul class="list-disc pl-4">
        <li><strong>{{ __('Meeting date') }}:</strong> {{ $meeting->meeting_date->format('Y-m-d') }}</li>
        <li><strong>{{ __('Meeting type') }}:</strong> {{ $meeting->is_evote ? __('Electronic') : __('Physical') }}</li>
        <li><strong>{{ __('Status') }}:</strong> {{ __($meeting->status) }}</li>
        <li><strong>{{ __('Associated secretary') }}:</strong> {{ optional($meeting->secretary)->pedagogical_name ?? '' }} {{ optional($meeting->secretary)->name ?? '' }}</li>
        <li><strong>{{ __('Vote start') }}:</strong> {{ $meeting->vote_start->format('Y-m-d H:i') }}</li>
        <li><strong>{{ __('Vote end') }}:</strong> {{ $meeting->vote_end->format('Y-m-d H:i') }}</li>
    </ul>
@else
    <form method="post" action="{{ route('meetings.update', $meeting) }}" class="dark:text-gray-800">
        @csrf
        @method('PATCH')
        <div class="grid grid-cols-2 gap-4">
            <div>
                <p class="text-gray-700 dark:text-gray-300"><strong>{{ __('Status') }}:</strong> {{ __($meeting->status) }}</p>
            </div>
            <div>
                <x-input-label for="meeting_date" value="{{ __('Meeting date') }}:" />
                <x-text-input id="meeting_date" name="meeting_date" type="date" class="block w-full" value="{{ $meeting->meeting_date->format('Y-m-d') }}" />
            </div>
            <div>
                <x-input-label for="is_evote" value="{{ __('Meeting type') }}:" />
                <select id="is_evote" name="is_evote" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm block w-full">
                    <option value="0" {{ $meeting->is_evote === 0 ? 'selected' : '' }}>{{ __('Physical') }}</option>
                    <option value="1" {{ $meeting->is_evote === 1 ? 'selected' : '' }}>{{ __('Electronic') }}</option>
                </select>
            </div>
            <div>
                <x-input-label for="secretary_id" value="{{ __('Associated secretary') }}:" />
                <select id="secretary_id" name="secretary_id" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm block w-full">
                    <option value="">{{ __('Select secretary') }}</option>
                    @foreach ($users as $user)
                        @if ($user->isSecretary())
                            <option value="{{ $user->user_id }}"
                                    @if ($user == $meeting->secretary) selected @endif>
                                {{ $user->pedagogical_name }} {{ $user->name }}
                            </option>
                        @endif
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="vote_start" value="{{ __('Vote start') }}:" />
                <x-text-input id="vote_start" name="vote_start" type="datetime-local" class="block w-full" value="{{ $meeting->vote_start->format('Y-m-d\TH:i') }}" />
            </div>
            <div>
                <x-input-label for="vote_end" value="{{ __('Vote end') }}:" />
                <x-text-input id="vote_end" name="vote_end" type="datetime-local" class="block w-full" value="{{ $meeting->vote_end->format('Y-m-d\TH:i') }}" />
            </div>
        </div>
        <div class="flex items-center justify-end mt-4">
            <x-primary-button class="ml-4">
                {{ __('Update Meeting') }}
            </x-primary-button>
        </div>
    </form>
    @if (Auth::user()->isPrivileged())
        <div class="flex space-x-2 mt-4">
            <x-primary-button>
                <a href="{{ route('meetings.protocol', $meeting) }}" class="w-full" target="_blank">
                    {{ __('View HTML Protocol') }}
                </a>
            </x-primary-button>
            <x-primary-button>
                <a href="{{ route('meetings.docx', $meeting) }}" class="w-full">
                    {{ __('Download DOCX Protocol') }}
                </a>
            </x-primary-button>
            <x-primary-button>
                <a href="{{ route('meetings.pdf', $meeting) }}" class="w-full" target="_blank">
                    {{ __('Download PDF Protocol') }}
                </a>
            </x-primary-button>
            <div x-data="{ confirmingMeetingDeletion: false }" class="relative">
                <x-danger-button x-on:click.prevent="confirmingMeetingDeletion = true">
                    {{ __('Delete Meeting') }}
                </x-danger-button>

                <div x-show="confirmingMeetingDeletion"
                        @click.outside="confirmingMeetingDeletion = false"
                        class="fixed z-50 inset-0 bg-gray-900 bg-opacity-50 dark:bg-gray-800 dark:bg-opacity-50 flex items-center justify-center"
                        style="backdrop-filter: blur(2px);">
                    <div class="bg-gray-800 dark:bg-gray-700 p-6 rounded shadow-md max-w-md mx-auto">
                        <h2 class="text-lg font-medium text-gray-300 dark:text-gray-100">
                            {{ __('Are you sure you want to delete this meeting?') }}
                        </h2>

                        <p class="mt-4 text-sm text-gray-600 dark:text-gray-400">
                            {{ __('This action is irreversible. Please confirm that you want to delete this meeting') }}
                        </p>

                        <form method="post" action="{{ route('meetings.destroy', $meeting) }}">
                            @csrf
                            @method('delete')

                            <div class="mt-6 flex justify-end">
                                <x-secondary-button x-on:click="confirmingMeetingDeletion = false">
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
        </div>
    @endif
@endif