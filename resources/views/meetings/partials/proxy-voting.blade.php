@if (Auth::user()->isPrivileged() || Auth::user()->isSecretary())
    <div class="ml-4">
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
            {{ __('Cast votes on behalf of body members. Only secretaries and IT administrators can use this feature.') }}
        </p>
        
        @if ($meeting->status == "Vyksta" && $meeting->questions->count() > 0)
            <div class="flex gap-6" x-data="{
                selectedQuestion: null,
                init() {
                    // Try to get saved question from localStorage
                    const saved = localStorage.getItem('proxyVoting_selectedQuestion_{{ $meeting->meeting_id }}');
                    if (saved && saved !== 'null') {
                        this.selectedQuestion = parseInt(saved);
                    } else {
                        // Default to first votable question
                        this.selectedQuestion = {{ $meeting->questions->where('type', '!=', 'Nebalsuoti')->first()?->question_id ?? 'null' }};
                    }
                    
                    // Watch for changes and save to localStorage
                    this.$watch('selectedQuestion', (value) => {
                        localStorage.setItem('proxyVoting_selectedQuestion_{{ $meeting->meeting_id }}', value);
                    });
                }
            }">
                <!-- Left side: Question list -->
                <div class="w-1/3">
                    <h4 class="font-semibold mb-3">{{ __('Select Question') }}</h4>
                    <div class="space-y-2">
                        @foreach ($meeting->questions as $question)
                            @if ($question->type != 'Nebalsuoti')
                                <button 
                                    @click="selectedQuestion = {{ $question->question_id }}"
                                    :class="selectedQuestion === {{ $question->question_id }} ? 'bg-blue-100 border-blue-500 text-blue-900 dark:bg-blue-900 dark:border-blue-400 dark:text-blue-100' : 'bg-white border-gray-300 text-gray-900 hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-100 dark:hover:bg-gray-700'"
                                    class="w-full text-left p-3 border rounded-lg transition-colors">
                                    <div class="font-medium">{{ $loop->iteration }}. {{ $question->title }}</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                        {{ __('Votes') }}: {{ $question->votes()->count() }}
                                    </div>
                                </button>
                            @endif
                        @endforeach
                    </div>
                </div>
                
                <!-- Right side: User voting table -->
                <div class="w-2/3">
                    @foreach ($meeting->questions as $question)
                        @if ($question->type != 'Nebalsuoti')
                            <div x-show="selectedQuestion === {{ $question->question_id }}" class="space-y-4">
                                <h4 class="font-semibold mb-3">{{ __('Cast Votes for') }}: {{ $question->title }}</h4>
                                
                                <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                                    <table class="w-full">
                                        <thead class="bg-gray-50 dark:bg-gray-700">
                                            <tr>
                                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                    {{ __('Member') }}
                                                </th>
                                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                    {{ __('Attendance Status') }}
                                                </th>
                                                <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                    {{ __('Actions') }}
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                            @foreach ($meeting->body->members->sortBy('name') as $member)
                                                @php
                                                    $existingVote = $question->voteByUser($member);
                                                @endphp
                                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                                    <td class="px-3 py-2">
                                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                            {{ $member->pedagogical_name }} {{ $member->name }}
                                                        </div>
                                                        @if ($existingVote)
                                                            <div class="text-xs text-blue-600 dark:text-blue-400 mt-1">
                                                                {{ __('Current') }}: {{ __($existingVote->choice) }}
                                                            </div>
                                                        @endif
                                                    </td>
                                                    <td class="px-3 py-2">
                                                        @if ($meeting->isUserAttending($member))
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">
                                                                {{ __('Present') }}
                                                            </span>
                                                        @else
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300">
                                                                {{ __('Absent') }}
                                                            </span>
                                                        @endif
                                                    </td>
                                                    <td class="px-3 py-2">
                                                        <div class="flex justify-center space-x-1">
                                                            @foreach (\App\Models\Vote::STATUSES as $status)
                                                                <form method="POST" action="{{ route('votes.proxy', [$meeting, $question]) }}" class="inline">
                                                                    @csrf
                                                                    @method('PUT')
                                                                    <input type="hidden" name="user_id" value="{{ $member->user_id }}">
                                                                    <input type="hidden" name="choice" value="{{ $status }}">
                                                                    @php
                                                                        if ($existingVote && $existingVote->choice == $status) {
                                                                            $btnClass = match($status) {
                                                                                'Už' => 'bg-green-500 text-white hover:bg-green-400 dark:bg-green-600 dark:hover:bg-green-500',
                                                                                'Prieš' => 'bg-red-500 text-white hover:bg-red-400 dark:bg-red-600 dark:hover:bg-red-500',
                                                                                'Susilaiko' => 'bg-yellow-500 text-white hover:bg-yellow-400 dark:bg-yellow-600 dark:hover:bg-yellow-500',
                                                                                default => 'bg-gray-200 text-gray-700 hover:bg-gray-300'
                                                                            };
                                                                        } else {
                                                                            $btnClass = 'bg-gray-200 text-gray-700 hover:bg-gray-300 dark:bg-gray-600 dark:text-gray-300 dark:hover:bg-gray-500';
                                                                        }
                                                                    @endphp
                                                                    <button type="submit" 
                                                                            class="px-2 py-1 text-xs rounded {{ $btnClass }}"
                                                                            title="{{ __($status) }}">
                                                                        {{ __($status) }}
                                                                    </button>
                                                                </form>
                                                            @endforeach
                                                            
                                                            @if ($existingVote)
                                                                <form method="POST" action="{{ route('votes.proxy-destroy', [$meeting, $question]) }}" class="inline">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <input type="hidden" name="user_id" value="{{ $member->user_id }}">
                                                                    <button type="submit" 
                                                                            class="px-2 py-1 text-xs bg-gray-300 text-gray-700 dark:bg-gray-600 dark:text-gray-300 rounded hover:bg-gray-400 dark:hover:bg-gray-500"
                                                                            title="{{ __('Remove vote') }}">
                                                                        ✕
                                                                    </button>
                                                                </form>
                                                            @endif
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                
                                <!-- Current votes summary -->
                                <div class="mt-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                    <div class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('Current votes') }}:</div>
                                    <div class="flex flex-wrap gap-3">
                                        @foreach (\App\Models\Vote::STATUSES as $status)
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                                                {{ __($status) }}: {{ $question->votes()->where('choice', $status)->count() }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        @else
            <div class="text-gray-500 dark:text-gray-400">
                @if ($meeting->status != "Vyksta")
                    {{ __('Proxy voting is only available during active meetings.') }}
                @else
                    {{ __('No questions available for voting.') }}
                @endif
            </div>
        @endif
    </div>
@endif
