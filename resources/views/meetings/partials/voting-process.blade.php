@if ($meeting->body->members->contains(Auth::user()))
    <details class="mb-4" open>
        <summary class="text-xl font-semibold"><span class="cursor-pointer">{{ __('Voting process') }}</span></summary>
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
                        @if (!$meeting->hasQuorum())
                            <div class="text-orange-600 dark:text-orange-400">
                                <i>{{ __('Warning: Quorum has not been reached. Votes cast will not be counted towards question approval.') }}</i>
                            </div>
                        @endif
                        @foreach ($meeting->questions as $question)
                            <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                <td class="px-6 py-4">{{ $loop->iteration }}. {{ $question->title }}</td>
                                @if ($question->type == "Nebalsuoti")
                                    <td class="px-6 py-4" colspan="{{ count(\App\Models\Vote::STATUSES) }}">
                                        <i>{{ __('Casting vote is not needed.') }}</i>
                                    </td>
                                    @continue
                                @endif
                                
                                @if (!$meeting->isUserAttending(Auth::user()))
                                    <td class="px-6 py-4" colspan="{{ count(\App\Models\Vote::STATUSES) }}">
                                        <div class="text-orange-600 dark:text-orange-400">
                                            <i>{{ __('Only attending members can vote.') }}</i>
                                        </div>
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
                                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-500 text-white rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-green-400 focus:bg-green-400 active:bg-green-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                                                    {{ __($status) }}
                                                </button>
                                            @else
                                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-300 text-gray-700 rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-gray-400 focus:bg-gray-400 active:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
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
                                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-500 text-white rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-red-400 focus:bg-red-400 active:bg-red-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                                                {{ __('Remove vote') }}
                                            </button>
                                        </td>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('votes.destroy', [$meeting, $question]) }}" class="inline-block">
                                        @csrf
                                        @method('DELETE')
                                        <td class="px-6 py-4">
                                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-300 text-gray-700 rounded-md font-semibold text-xs uppercase tracking-widest">
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
            <div class="w-full">
                <p class="text-gray-500 dark:text-gray-400">{{ __('Voting process is not available.') }}</p>
            </div>
            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-6 py-3">{{ __('Question') }}</th>
                        @foreach (\App\Models\Vote::STATUSES as $status)
                            <th scope="col" class="px-6 py-3">{{ __($status) }}</th>
                        @endforeach
                        <th scope="col" class="px-6 py-3">{{ __('Nebalsuota') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($meeting->questions as $question)
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                            <td class="px-6 py-4">{{ $loop->iteration }}. {{ $question->title }}</td>
                            @foreach (\App\Models\Vote::STATUSES as $status)
                                <td class="px-6 py-4">{{ $question->votes()->where('choice', $status)->count() }}</td>
                            @endforeach
                            <td class="px-6 py-4">
                                @if ($question->voteByUser(auth()->user()))
                                    <span class="inline-flex items-center px-4 py-2 bg-gray-300 text-gray-700 rounded-md font-semibold text-xs uppercase tracking-widest">
                                        {{ __('Nebalsuota') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-4 py-2 bg-red-500 text-white rounded-md font-semibold text-xs uppercase tracking-widest">
                                        {{ __('Nebalsuota') }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </details>
@endif
