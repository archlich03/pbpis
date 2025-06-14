<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Meeting') }} {{ $meeting->meeting_date->format('Y-m-d') }} - <a href="{{ route('bodies.show', $meeting->body) }}" class="text-blue-500 hover:underline">{{ $meeting->body->title }}</a> ({{ $meeting->body->is_ba_sp ? 'BA' : 'MA' }})
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <details class="mb-4">
                        <summary class="text-xl font-semibold"><span class="cursor-pointer">Meeting information</span></summary>
                        @if (!Auth::user()->isPrivileged())
                            <ul class="list-disc pl-4">
                                <li><strong>Meeting date:</strong> {{ $meeting->meeting_date->format('Y-m-d') }}</li>
                                <li><strong>Meeting type:</strong> {{ $meeting->is_evote ? 'Electronic' : 'Physical' }}</li>
                                <li><strong>Status:</strong> {{ $meeting->status }}</li>
                                <li><strong>Secretary:</strong> {{ optional($meeting->secretary)->pedagogical_name ?? '' }} {{ optional($meeting->secretary)->name ?? '' }}</li>
                                <li><strong>Vote start:</strong> {{ $meeting->vote_start->format('Y-m-d H:i') }}</li>
                                <li><strong>Vote end:</strong> {{ $meeting->vote_end->format('Y-m-d H:i') }}</li>
                                <li><strong>Created:</strong> {{ $meeting->created_at->format('Y-m-d H:i') }}</li>
                                <li><strong>Last edited:</strong> {{ $meeting->updated_at->format('Y-m-d H:i') }}</li>
                            </ul>
                        @else
                            <form method="post" action="{{ route('meetings.update', $meeting) }}" class="dark:text-gray-800">
                                @csrf
                                @method('PATCH')
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <x-input-label for="meeting_date" value="Meeting date" />
                                        <x-text-input id="meeting_date" name="meeting_date" type="date" class="block w-full" value="{{ $meeting->meeting_date->format('Y-m-d') }}" />
                                    </div>
                                    <div>
                                        <x-input-label for="is_evote" value="Meeting type" />
                                        <select id="is_evote" name="is_evote" class="block w-full">
                                            <option value="0" {{ $meeting->is_evote === 0 ? 'selected' : '' }}>Physical</option>
                                            <option value="1" {{ $meeting->is_evote === 1 ? 'selected' : '' }}>Electronic</option>
                                        </select>
                                    </div>
                                    <div>
                                        <x-input-label for="status" value="Status" />
                                        <select id="status" name="status" class="block w-full">
                                            @foreach ($meeting::STATUSES as $status)
                                                <option value="{{ $status }}" {{ $meeting->status === $status ? 'selected' : '' }}>{{ $status }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <x-input-label for="secretary_id" value="Secretary" />
                                        <select id="secretary_id" name="secretary_id" class="block w-full">
                                            <option value="">Select secretary</option>
                                            @foreach ($users as $user)
                                                @if ($user->isSecretary())
                                                    <option value="{{ $user->user_id }}"
                                                        @if ($user == $meeting->secretary) selected @endif
                                                        required>{{ $user->pedagogical_name }} {{ $user->name }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <x-input-label for="vote_start" value="Vote start" />
                                        <x-text-input id="vote_start" name="vote_start" type="datetime-local" class="block w-full" value="{{ $meeting->vote_start->format('Y-m-d\TH:i') }}" />
                                    </div>
                                    <div>
                                        <x-input-label for="vote_end" value="Vote end" />
                                        <x-text-input id="vote_end" name="vote_end" type="datetime-local" class="block w-full" value="{{ $meeting->vote_end->format('Y-m-d\TH:i') }}" />
                                    </div>
                                    <div>
                                        <x-input-label for="created_at" value="Created at: {{ $meeting->created_at->format('Y-m-d H:i') }}" />
                                        <x-input-label for="updated_at" value="Updated at: {{ $meeting->updated_at->format('Y-m-d H:i') }}" />
                                    </div>
                                </div>
                                <x-primary-button type="submit">
                                    Update
                                </x-primary-button>
                            </form>
                            @if (Auth::user()->isPrivileged())
                                <div x-data="{ confirmingMeetingDeletion: false }"
                                    class="relative">
                                    <x-danger-button
                                        x-on:click.prevent="confirmingMeetingDeletion = true">
                                        {{ __('Delete Meeting') }}
                                    </x-danger-button>

                                    <div
                                        x-show="confirmingMeetingDeletion"
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
                            @endif
                        @endif
                    </details>

                    <hr class="border-t-2 border-gray-300 dark:border-gray-600 mt-4 mb-4">

                    <details class="mb-4">
                        <summary class="text-xl font-semibold"><span class="cursor-pointer">Questions</span></summary>
                        @if (Auth::User()->isPrivileged())
                            <x-primary-button>
                                <a href="{{ route('questions.create', $meeting) }}" class="w-full">
                                    {{ __('Create New Question') }}
                                </a>
                            </x-primary-button>
                        @endif
                        <div class="ml-4">
                            @foreach ($meeting->questions as $question)
                                <details class="mb-4">
                                    <summary class="font-semibold cursor-pointer">{{ $loop->iteration }}. {{ $question->title }}</summary>
                                    <div class="ml-4">
                                        @if (!Auth::User()->isPrivileged())
                                            <p><strong>Presenter:</strong> {{ optional($question->presenter)->pedagogical_name ?? '' }} {{ optional($question->presenter)->name ?? '' }}</p>
                                            @if (!empty($question->decision))
                                                <p><strong>Decision:</strong> {{ $question->decision }}</p>
                                            @endif
                                            @if (!empty($question->summary))
                                                <p><strong>Summary:</strong> {{ $question->summary }}</p>
                                            @endif
                                        @else
                                            <form method="POST" action="{{ route('questions.update', [$meeting, $question]) }}" class="dark:text-gray-800">
                                                @csrf
                                                @method('PATCH')

                                                <div class="mt-4">
                                                    <x-input-label for="title" value="Title" />
                                                    <x-text-input id="title" name="title" type="text" class="block mt-1 w-full" value="{{ $question->title }}" required />
                                                </div>

                                                <div class="mt-4">
                                                    <x-input-label for="type" value="Type" />
                                                    <select id="type" name="type" class="block mt-1 w-full">
                                                        @foreach (\App\Models\Question::STATUSES as $status)
                                                            <option value="{{ $status }}" {{ $question->type == $status ? 'selected' : '' }}>{{ $status }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <div class="mt-4">
                                                    <x-input-label for="presenter_id" value="Presenter" />
                                                    <select id="presenter_id" name="presenter_id" class="block mt-1 w-full">
                                                        @foreach ($users as $user)
                                                            @if ($user->isSecretary())
                                                                <option value="{{ $user->user_id }}" {{ $question->presenter_id == $user->user_id ? 'selected' : '' }}>{{ $user->pedagogical_name }} {{ $user->name }}</option>
                                                            @endif
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <div class="mt-4">
                                                    <x-input-label for="decision" value="Decision" />
                                                    <x-text-input id="decision" name="decision" type="text" class="block mt-1 w-full" value="{{ $question->decision }}" />
                                                </div>

                                                <div class="mt-4">
                                                    <x-input-label for="summary" value="Summary" />
                                                    <textarea id="summary" name="summary" class="block mt-1 w-full rounded-md shadow-sm border-gray-300 dark:border-gray-700 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" rows="3">{{ $question->summary }}</textarea>
                                                </div>

                                                <div class="flex items-center justify-end mt-4">
                                                    <x-primary-button class="ml-4">
                                                        {{ __('Update Question') }}
                                                    </x-primary-button>
                                                </div>
                                            </form>
                                            <form method="POST" action="{{ route('questions.destroy', [$meeting, $question]) }}" class="dark:text-gray-800">
                                                @csrf
                                                @method('DELETE')
                                                <x-danger-button class="mt-4">
                                                    {{ __('Delete Question') }}
                                                </x-danger-button>
                                            </form>
                                        @endif
                                    </div>
                                </details>
                            @endforeach
                        </div>
                    </details>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>


