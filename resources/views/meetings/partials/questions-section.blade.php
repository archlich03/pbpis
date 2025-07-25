@if ($meeting->body->members->contains(Auth::user()) || Auth::User()->isPrivileged())
    <details class="mb-4">
        <summary class="text-xl font-semibold"><span class="cursor-pointer">{{ __('Questions') }}</span></summary>
        <div class="ml-4" x-data="questionReorder()">
            @if (Auth::User()->isPrivileged())
                <div class="mb-4">
                    <div class="flex space-x-2 mb-2">
                        <x-primary-button>
                            <a href="{{ route('questions.create', $meeting) }}" class="w-full">
                                {{ __('Create Question') }}
                            </a>
                        </x-primary-button>
                        @if($meeting->questions->count() > 1)
                            <x-secondary-button @click="reorderMode = !reorderMode" x-text="reorderMode ? '{{ __('Done Reordering') }}' : '{{ __('Reorder Questions') }}'">
                            </x-secondary-button>
                        @endif
                    </div>
                    @if($meeting->questions->count() > 1)
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4" x-show="reorderMode">
                            {{ __('Use the up and down buttons to reorder questions. Changes will be saved automatically.') }}
                        </p>
                    @endif
                </div>
            @endif
            @foreach ($meeting->questions as $question)
                <details class="mb-4" data-question-id="{{ $question->question_id }}">
                    <summary class="font-semibold cursor-pointer flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <span>{{ $loop->iteration }}. {{ $question->title }}</span>
                            @if ($question->type != 'Nebalsuoti')
                                @php
                                    $voteCounts = $meeting->getVoteCounts($question);
                                    $questionPassed = $meeting->calculateQuestionResult($question);
                                @endphp
                                @if ($voteCounts['Už'] > 0)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $questionPassed ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' }}">
                                        {{ $questionPassed ? __('Passed') : __('Not Passed') }}
                                    </span>
                                @endif
                            @endif
                        </div>
                        @if (Auth::User()->isPrivileged() && $meeting->questions->count() > 1)
                            <div class="flex space-x-1 ml-4" x-show="reorderMode">
                                @if (!$loop->first)
                                    <button onclick="moveQuestion({{ $question->question_id }}, 'up')" class="px-2 py-1 text-xs bg-blue-100 text-blue-700 rounded hover:bg-blue-200 dark:bg-blue-900 dark:text-blue-300 dark:hover:bg-blue-800">
                                        {{ __('Move Up') }}
                                    </button>
                                @endif
                                @if (!$loop->last)
                                    <button onclick="moveQuestion({{ $question->question_id }}, 'down')" class="px-2 py-1 text-xs bg-blue-100 text-blue-700 rounded hover:bg-blue-200 dark:bg-blue-900 dark:text-blue-300 dark:hover:bg-blue-800">
                                        {{ __('Move Down') }}
                                    </button>
                                @endif
                            </div>
                        @endif
                    </summary>
                    <div class="ml-4">
                        @php
                            $voteCounts = $meeting->getVoteCounts($question);
                            $hasChairmanVoted = $meeting->hasChairmanVoted($question);
                            $requiredVotes = $meeting->getRequiredVotesForQuestion($question, $hasChairmanVoted);
                            $requiredWithoutChairman = $meeting->getRequiredVotesForQuestion($question, false);
                            $requiredWithChairman = $meeting->getRequiredVotesForQuestion($question, true);
                            $questionPassed = $meeting->calculateQuestionResult($question);
                        @endphp

                        @if ($question->type != 'Nebalsuoti')
                            <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                <strong>{{ __('Vote counts') }}:</strong> 
                                {{ __('Už') }}: {{ $voteCounts['Už'] }}, 
                                {{ __('Prieš') }}: {{ $voteCounts['Prieš'] }}, 
                                {{ __('Susilaikė') }}: {{ $voteCounts['Susilaikė'] }}
                            </div>
                            
                            <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                <strong>{{ __('Required to pass') }}:</strong> 
                                @if ($question->type === '2/3 dauguma')
                                    @if ($hasChairmanVoted)
                                        {{ $requiredWithChairman }} {{ __('votes') }}
                                    @else
                                        {{ $requiredWithoutChairman }} {{ __('votes') }}
                                    @endif
                                @else
                                    @php
                                        $chairman = $meeting->body->chairman;
                                        $chairmanVotedFor = false;
                                        if ($chairman && $hasChairmanVoted) {
                                            $chairmanVote = $question->votes()->where('user_id', $chairman->user_id)->first();
                                            $chairmanVotedFor = $chairmanVote && $chairmanVote->choice === 'Už';
                                        }
                                    @endphp
                                    {{ $requiredWithoutChairman }} {{ __('votes') }}
                                    @if ($hasChairmanVoted && $chairmanVotedFor)
                                        {{ __('(or tie with chairman voting for)') }}
                                    @elseif (!$hasChairmanVoted && $chairman)
                                        {{ __('(or tie if chairman votes for)') }}
                                    @endif
                                @endif
                            </div>

                        @endif

                        @if (!Auth::User()->isPrivileged())
                            <p><strong>{{ __('Presenter') }}:</strong> {{ optional($question->presenter)->pedagogical_name ?? '' }} {{ optional($question->presenter)->name ?? '' }}</p>
                            @if (!empty($question->decision))
                                <p><strong>{{ __('Decision') }}:</strong> {{ $question->decision }}</p>
                            @endif
                            @if (!empty($question->summary))
                                <p><strong>{{ __('Summary') }}:</strong> {!! $question->summary !!}</p>
                            @endif
                        @else
                            <form method="POST" action="{{ route('questions.update', [$meeting, $question]) }}" class="dark:text-gray-800">
                                @csrf
                                @method('PATCH')

                                <div class="mt-4">
                                    <x-input-label for="title" value="{{ __('Question title') }}:" />
                                    <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" value="{{ $question->title }}" />
                                </div>

                                <div class="mt-4">
                                    <x-input-label for="type" value="{{ __('Voting type') }}:" />
                                    <select id="type" name="type" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm block mt-1 w-full">
                                        @foreach (\App\Models\Question::STATUSES as $status)
                                            <option value="{{ $status }}" {{ $question->type == $status ? 'selected' : '' }}>{{ __($status) }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="mt-4">
                                    <x-input-label for="presenter_id" value="{{ __('Presenter') }}:" />
                                    <select id="presenter_id" name="presenter_id" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm block mt-1 w-full">
                                        <option value="">{{ __('Select presenter') }}</option>
                                        @foreach ($users as $user)
                                            <option value="{{ $user->user_id }}" {{ $question->presenter_id == $user->user_id ? 'selected' : '' }}>{{ $user->pedagogical_name }} {{ $user->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="mt-4">
                                    <x-input-label for="decision" value="{{ __('Decision') }}:" />
                                    <x-text-input id="decision" name="decision" type="text" class="mt-1 block w-full" value="{{ $question->decision }}" />
                                </div>

                                <div class="mt-4">
                                    <x-input-label for="summary-{{ $question->question_id }}" value="{{ __('Question summary') }}:" />
                                    <textarea 
                                        id="summary-{{ $question->question_id }}" 
                                        name="summary" 
                                        rows="4" 
                                        class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm block mt-1 w-full"
                                        placeholder="{{ __('Enter detailed summary...') }}">{{ $question->summary }}</textarea>
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
@endif
