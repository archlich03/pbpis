@if ($meeting->body->members->contains(Auth::user()))
    @if ($meeting->status == "Vyksta")
        <div class="w-full">
            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-6 py-3">{{ __('Question') }}</th>
                        <th class="px-6 py-4" colspan="{{ count(\App\Models\Vote::STATUSES) + 1 }}">
                            {{ __('Voting choice') }}
                        </th>
                    </tr>
                </thead>
                <tbody>

                    @foreach ($meeting->questions as $question)
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                            <td class="px-6 py-4">
                                <div>
                                    <div class="font-medium">{{ $loop->iteration }}. {{ $question->title }}</div>
                                    @if ($question->type != "Nebalsuoti" && !empty($question->decision))
                                        <div class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                            <span class="font-semibold">{{ __('Proposal') }}:</span> {{ $question->decision }}
                                        </div>
                                    @endif
                                    @if (!empty($question->summary))
                                        <div class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                            <span class="font-semibold">{{ __('Summary') }}:</span>
                                            <div class="prose prose-sm dark:prose-invert max-w-none mt-1">
                                                {!! $question->summary !!}
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </td>
                            @if ($question->type == "Nebalsuoti")
                                <td class="px-6 py-4" colspan="{{ count(\App\Models\Vote::STATUSES) }}">
                                    <i>{{ __('Casting vote is not needed.') }}</i>
                                </td>
                                @continue
                            @endif
                            

                            @foreach (\App\Models\Vote::STATUSES as $status)
                                <form method="POST" action="{{ route('votes.store', [$meeting, $question]) }}" class="inline-block">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="choice" value="{{ $status }}">
                                    <td class="px-6 py-4">
                                        @if ($question->voteByUser(auth::user()) && $question->voteByUser(auth::user())->choice == $status)
                                            @php
                                                $buttonClass = match($status) {
                                                    'Už' => 'bg-green-500 hover:bg-green-400 focus:bg-green-400 active:bg-green-600 dark:bg-green-600 dark:hover:bg-green-500',
                                                    'Prieš' => 'bg-red-500 hover:bg-red-400 focus:bg-red-400 active:bg-red-600 dark:bg-red-600 dark:hover:bg-red-500',
                                                    'Susilaiko' => 'bg-yellow-500 hover:bg-yellow-400 focus:bg-yellow-400 active:bg-yellow-600 dark:bg-yellow-600 dark:hover:bg-yellow-500',
                                                    default => 'bg-gray-300 hover:bg-gray-400 focus:bg-gray-400 active:bg-gray-600'
                                                };
                                                $textClass = 'text-white';
                                            @endphp
                                            <button type="submit" class="inline-flex items-center px-4 py-2 {{ $buttonClass }} {{ $textClass }} rounded-md font-semibold text-xs uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                                                {{ __($status) }}
                                            </button>
                                        @else
                                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-300 text-gray-700 dark:bg-gray-600 dark:text-gray-300 rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-gray-400 dark:hover:bg-gray-500 focus:bg-gray-400 active:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                                                {{ __($status) }}
                                            </button>
                                        @endif
                                    </td>
                                </form>
                            @endforeach
                            @if ($question->voteByUser(auth::user()))
                                <form method="POST" action="{{ route('votes.destroy', [$meeting, $question]) }}" class="inline-block">
                                    @csrf
                                    @method('DELETE')
                                    <td class="px-6 py-4">
                                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-300 text-gray-700 dark:bg-gray-600 dark:text-gray-300 rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-gray-400 dark:hover:bg-gray-500 focus:bg-gray-400 active:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                                            {{ __('Remove vote') }}
                                        </button>
                                    </td>
                                </form>
                            @else
                                <form method="POST" action="{{ route('votes.destroy', [$meeting, $question]) }}" class="inline-block">
                                    @csrf
                                    @method('DELETE')
                                    <td class="px-6 py-4">
                                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-300 text-gray-700 dark:bg-gray-600 dark:text-gray-300 rounded-md font-semibold text-xs uppercase tracking-widest">
                                            {{ __('Remove vote') }}
                                        </button>
                                    </td>
                                </form>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="w-full space-y-6">
            <p class="text-gray-500 dark:text-gray-400 mb-4">{{ __('Voting results') }}</p>
            
            @foreach ($meeting->questions as $question)
                <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 border-b border-gray-200 dark:border-gray-600">
                        <h3 class="font-semibold text-gray-900 dark:text-gray-100">
                            {{ $loop->iteration }}. {{ $question->title }}
                        </h3>
                        @if ($question->type != "Nebalsuoti" && !empty($question->decision))
                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                <span class="font-semibold">{{ __('Proposal') }}:</span> {{ $question->decision }}
                            </p>
                        @endif
                        @if (!empty($question->summary))
                            <div class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                <span class="font-semibold">{{ __('Summary') }}:</span>
                                <div class="prose prose-sm dark:prose-invert max-w-none mt-1">
                                    {!! $question->summary !!}
                                </div>
                            </div>
                        @endif
                    </div>
                    
                    @if ($question->type == "Nebalsuoti")
                        <div class="px-4 py-3">
                            <p class="text-sm text-gray-500 dark:text-gray-400 italic">{{ __('Casting vote is not needed.') }}</p>
                        </div>
                    @else
                        <div class="px-4 py-4">
                            @php
                                $voteCounts = $meeting->getVoteCounts($question);
                                $questionPassed = $meeting->calculateQuestionResult($question);
                            @endphp
                            
                            {{-- Pass/Fail Status --}}
                            <div class="mb-4 p-3 rounded-lg {{ $questionPassed ? 'bg-green-50 dark:bg-green-900/20' : 'bg-red-50 dark:bg-red-900/20' }}">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-semibold {{ $questionPassed ? 'text-green-800 dark:text-green-300' : 'text-red-800 dark:text-red-300' }}">
                                        {{ __('Decision') }}:
                                    </span>
                                    <span class="text-sm font-bold {{ $questionPassed ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                        {{ $questionPassed ? __('Passed') : __('Not Passed') }}
                                    </span>
                                </div>
                            </div>
                            
                            {{-- Vote Summary --}}
                            <div class="flex flex-wrap gap-3 mb-4">
                                @foreach (\App\Models\Vote::STATUSES as $status)
                                    @php
                                        $count = $question->votes()->where('choice', $status)->count();
                                        $badgeClass = match($status) {
                                            'Už' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                                            'Prieš' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                                            'Susilaiko' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
                                            default => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300'
                                        };
                                    @endphp
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $badgeClass }}">
                                        {{ __($status) }}: {{ $count }}
                                    </span>
                                @endforeach
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300">
                                    {{ __('Nebalsuota') }}: {{ $meeting->body->members->count() - $question->votes()->count() }}
                                </span>
                            </div>
                            
                            {{-- Individual Votes in 4 Columns --}}
                            <div class="space-y-2">
                                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">{{ __('Individual votes') }}:</h4>
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                                    @php
                                        $votedFor = $question->votes()->where('choice', 'Už')->get()->pluck('user');
                                        $votedAgainst = $question->votes()->where('choice', 'Prieš')->get()->pluck('user');
                                        $votedAbstain = $question->votes()->where('choice', 'Susilaiko')->get()->pluck('user');
                                        $votedIds = $question->votes()->pluck('user_id');
                                        $didNotVote = $meeting->body->members->whereNotIn('user_id', $votedIds);
                                    @endphp
                                    
                                    {{-- Už (For) Column --}}
                                    <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-3">
                                        <h5 class="font-semibold text-green-800 dark:text-green-300 mb-2 text-sm">
                                            {{ __('Už') }} ({{ $votedFor->count() }})
                                        </h5>
                                        <div class="space-y-1">
                                            @forelse ($votedFor->sortBy('name') as $member)
                                                <div class="text-xs text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 rounded px-2 py-1">
                                                    {{ $member->pedagogical_name }} {{ $member->name }}
                                                </div>
                                            @empty
                                                <div class="text-xs text-gray-500 dark:text-gray-400 italic">{{ __('None') }}</div>
                                            @endforelse
                                        </div>
                                    </div>
                                    
                                    {{-- Prieš (Against) Column --}}
                                    <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-3">
                                        <h5 class="font-semibold text-red-800 dark:text-red-300 mb-2 text-sm">
                                            {{ __('Prieš') }} ({{ $votedAgainst->count() }})
                                        </h5>
                                        <div class="space-y-1">
                                            @forelse ($votedAgainst->sortBy('name') as $member)
                                                <div class="text-xs text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 rounded px-2 py-1">
                                                    {{ $member->pedagogical_name }} {{ $member->name }}
                                                </div>
                                            @empty
                                                <div class="text-xs text-gray-500 dark:text-gray-400 italic">{{ __('None') }}</div>
                                            @endforelse
                                        </div>
                                    </div>
                                    
                                    {{-- Susilaiko (Abstain) Column --}}
                                    <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-3">
                                        <h5 class="font-semibold text-yellow-800 dark:text-yellow-300 mb-2 text-sm">
                                            {{ __('Susilaiko') }} ({{ $votedAbstain->count() }})
                                        </h5>
                                        <div class="space-y-1">
                                            @forelse ($votedAbstain->sortBy('name') as $member)
                                                <div class="text-xs text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 rounded px-2 py-1">
                                                    {{ $member->pedagogical_name }} {{ $member->name }}
                                                </div>
                                            @empty
                                                <div class="text-xs text-gray-500 dark:text-gray-400 italic">{{ __('None') }}</div>
                                            @endforelse
                                        </div>
                                    </div>
                                    
                                    {{-- Nebalsuota (Did Not Vote) Column --}}
                                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3">
                                        <h5 class="font-semibold text-gray-800 dark:text-gray-300 mb-2 text-sm">
                                            {{ __('Nebalsuota') }} ({{ $didNotVote->count() }})
                                        </h5>
                                        <div class="space-y-1">
                                            @forelse ($didNotVote->sortBy('name') as $member)
                                                <div class="text-xs text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 rounded px-2 py-1">
                                                    {{ $member->pedagogical_name }} {{ $member->name }}
                                                </div>
                                            @empty
                                                <div class="text-xs text-gray-500 dark:text-gray-400 italic">{{ __('None') }}</div>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
@endif
