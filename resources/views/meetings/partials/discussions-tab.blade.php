{{-- Discussions Tab - Only for E-Vote Meetings --}}
@if ($meeting->is_evote)
    <div x-data="{ activeQuestion: {{ session('active_question_id', $meeting->questions->first()->question_id ?? 0) }} }">
        {{-- Question Tabs --}}
        @if ($meeting->questions->count() > 1)
            <div class="border-b border-gray-200 dark:border-gray-700 mb-4 -mx-4 sm:mx-0">
                <nav class="flex space-x-1 sm:space-x-2 overflow-x-auto px-4 sm:px-0 scrollbar-hide" aria-label="Question tabs" style="scrollbar-width: none; -ms-overflow-style: none;">
                    @foreach ($meeting->questions as $question)
                        <button @click="activeQuestion = {{ $question->question_id }}" 
                                :class="activeQuestion === {{ $question->question_id }} ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'"
                                class="whitespace-nowrap py-3 px-3 sm:px-4 border-b-2 font-medium text-xs sm:text-sm transition-colors flex-shrink-0"
                                role="tab"
                                :aria-selected="activeQuestion === {{ $question->question_id }}"
                                title="{{ $question->title }}">
                            {{ $loop->iteration }} klausimas
                        </button>
                    @endforeach
                </nav>
            </div>
        @endif

        {{-- Question Content Panels --}}
        @foreach ($meeting->questions as $question)
            <div x-show="activeQuestion === {{ $question->question_id }}"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 role="tabpanel"
                 class="space-y-4">
                    {{-- Question Header --}}
                    @if ($meeting->questions->count() > 1)
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 border border-gray-200 dark:border-gray-600">
                            <h3 class="font-semibold text-lg text-gray-900 dark:text-gray-100">
                                {{ $loop->iteration }}. {{ $question->title }}
                            </h3>
                            @if ($question->decision)
                                <div class="mt-2 text-sm text-gray-700 dark:text-gray-300">
                                    <span class="font-medium text-gray-900 dark:text-gray-100">{{ __('Proposal') }}:</span>
                                    {{ $question->decision }}
                                </div>
                            @endif
                        </div>
                    @endif

                    {{-- New Comment Form (only during voting) --}}
                    @if ($meeting->status === 'Vyksta' && 
                         ($meeting->body->members->contains(Auth::user()) || 
                          Auth::user()->role === 'Sekretorius' || 
                          Auth::user()->role === 'IT administratorius'))
                        <form method="POST" action="{{ route('discussions.store', [$meeting, $question]) }}" 
                              class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4"
                              x-data="{ content: '', replyTo: null, replyToName: '', replyToContent: '' }">
                            @csrf
                            
                            {{-- Reply indicator with quoted message --}}
                            <div x-show="replyTo" class="mb-2 bg-blue-50 dark:bg-blue-900/30 px-3 py-2 rounded">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-sm font-semibold text-blue-700 dark:text-blue-300">
                                        {{ __('Replying to') }}: <span x-text="replyToName"></span>
                                    </span>
                                    <button type="button" @click="replyTo = null; replyToName = ''; replyToContent = ''" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-200">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </div>
                                <div class="text-xs text-gray-600 dark:text-gray-400 italic border-l-2 border-blue-400 pl-2" x-text="replyToContent"></div>
                            </div>

                            <input type="hidden" name="parent_id" x-model="replyTo">
                            
                            <textarea 
                                name="content" 
                                x-model="content"
                                rows="3" 
                                class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm resize-none"
                                placeholder="{{ __('Write your comment...') }}"
                                maxlength="5000"
                                required></textarea>
                            
                            <div class="mt-2 flex items-center justify-between">
                                <span class="text-xs text-gray-500 dark:text-gray-400" x-text="content.length + '/5000'">0/5000</span>
                                <x-primary-button type="submit" class="text-sm">
                                    {{ __('Post Comment') }}
                                </x-primary-button>
                            </div>
                        </form>
                    @endif

                    {{-- Discussion Comments --}}
                    @php
                        // Get all discussions (top-level and replies) in reverse chronological order (newest first)
                        $allDiscussions = \App\Models\Discussion::where('question_id', $question->question_id)
                            ->with(['user', 'parent.user'])
                            ->orderBy('created_at', 'desc')
                            ->get();
                    @endphp

                    @if ($allDiscussions->isEmpty())
                        <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                            <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                            </svg>
                            <p class="text-sm">{{ __('No comments yet. Be the first to start the discussion!') }}</p>
                        </div>
                    @else
                        <div class="space-y-3">
                            @foreach ($allDiscussions as $discussion)
                                {{-- Each comment card (top-level or reply) --}}
                                <div class="border-l-4 {{ $discussion->parent_id ? 'border-gray-200 dark:border-gray-500' : 'border-gray-300 dark:border-gray-600' }} pl-4 py-1" 
                                     x-data="{ editing: false, editContent: @js($discussion->content) }">
                                    <div class="flex items-start space-x-3">
                                        {{-- Avatar --}}
                                        <div class="flex-shrink-0">
                                            <div class="w-8 h-8 rounded-full bg-indigo-500 flex items-center justify-center text-white font-semibold text-sm">
                                                {{ strtoupper(substr($discussion->user->name, 0, 1)) }}
                                            </div>
                                        </div>

                                        {{-- Comment content --}}
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center justify-between flex-wrap gap-2">
                                                <div class="flex items-center space-x-2">
                                                    <span class="font-semibold text-sm text-gray-900 dark:text-gray-100">
                                                        {{ $discussion->user->name }}
                                                    </span>
                                                    <span class="text-xs text-gray-500 dark:text-gray-400" 
                                                          data-timestamp="{{ $discussion->created_at->timestamp }}"
                                                          data-created="{{ $discussion->created_at->format('Y-m-d H:i') }}">
                                                        {{ $discussion->created_at->diffForHumans() }}
                                                    </span>
                                                    @if ($discussion->created_at != $discussion->updated_at)
                                                        <span class="text-xs text-gray-400 dark:text-gray-500 italic">
                                                            ({{ __('edited') }})
                                                        </span>
                                                    @endif
                                                </div>

                                                {{-- Actions --}}
                                                @if ($meeting->status === 'Vyksta')
                                                    <div class="flex items-center space-x-2">
                                                        {{-- Reply button --}}
                                                        @if ($meeting->body->members->contains(Auth::user()) || 
                                                             Auth::user()->role === 'Sekretorius' || 
                                                             Auth::user()->role === 'IT administratorius')
                                                            <button type="button" 
                                                                    @click="
                                                                        let form = $el.closest('.p-4').querySelector('form');
                                                                        form.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                                                        let alpineData = Alpine.$data(form);
                                                                        alpineData.replyTo = {{ $discussion->discussion_id }};
                                                                        alpineData.replyToName = '{{ $discussion->user->name }}';
                                                                        alpineData.replyToContent = {{ Js::from(Str::limit($discussion->content, 100)) }};
                                                                        form.querySelector('textarea').focus();"
                                                                    class="text-xs text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-200 font-medium">
                                                                {{ __('Reply') }}
                                                            </button>
                                                        @endif

                                                        {{-- Edit button (own comments only) --}}
                                                        @if ($discussion->user_id === Auth::id())
                                                            <button type="button" @click="editing = !editing" 
                                                                    class="text-xs text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 font-medium">
                                                                {{ __('Edit') }}
                                                            </button>
                                                        @endif

                                                        {{-- Delete button (own comments, secretaries, IT admins) --}}
                                                        @if ($discussion->user_id === Auth::id() || 
                                                             Auth::user()->role === 'Sekretorius' || 
                                                             Auth::user()->role === 'IT administratorius')
                                                            <form method="POST" action="{{ route('discussions.destroy', [$meeting, $question, $discussion]) }}" 
                                                                  class="inline-flex items-center"
                                                                  onsubmit="return confirm('{{ __('Are you sure you want to delete this comment?') }}');">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="text-xs text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-200 font-medium">
                                                                    {{ __('Delete') }}
                                                                </button>
                                                            </form>
                                                        @endif
                                                    </div>
                                                @endif
                                            </div>

                                            {{-- Quoted parent message (if this is a reply) --}}
                                            @if ($discussion->parent_id && $discussion->parent)
                                                <div class="mb-2 text-xs text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-700/50 border-l-2 border-gray-300 dark:border-gray-600 pl-2 py-1 rounded">
                                                    <div class="font-semibold">{{ $discussion->parent->user->name }}</div>
                                                    <div class="italic">{{ Str::limit($discussion->parent->content, 100) }}</div>
                                                </div>
                                            @endif

                                            {{-- Comment text (view mode) --}}
                                            <div x-show="!editing" class="text-sm text-gray-700 dark:text-gray-300 break-words">
                                                {{ $discussion->content }}
                                            </div>

                                            {{-- Edit form --}}
                                            <div x-show="editing" class="mt-2">
                                                <form method="POST" action="{{ route('discussions.update', [$meeting, $question, $discussion]) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <textarea name="content" x-model="editContent" rows="3" 
                                                              class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm resize-none text-sm"
                                                              maxlength="5000" required></textarea>
                                                    <div class="mt-2 flex items-center space-x-2">
                                                        <button type="submit" class="text-xs px-3 py-1 bg-indigo-600 text-white rounded hover:bg-indigo-700">
                                                            {{ __('Save') }}
                                                        </button>
                                                        <button type="button" @click="editing = false; editContent = @js($discussion->content)" 
                                                                class="text-xs px-3 py-1 bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-400 dark:hover:bg-gray-500">
                                                            {{ __('Cancel') }}
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
            </div>
        @endforeach
    </div>
@else
    <div class="text-center py-12">
        <svg class="w-16 h-16 mx-auto mb-4 text-gray-400 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
        </svg>
        <p class="text-gray-500 dark:text-gray-400">
            {{ __('Discussions are only available for e-vote meetings.') }}
        </p>
    </div>
@endif

<script>
// Real-time timestamp updates for discussions
function updateTimestamps() {
    const now = Math.floor(Date.now() / 1000);
    const timestamps = document.querySelectorAll('[data-timestamp]');
    
    timestamps.forEach(element => {
        const timestamp = parseInt(element.getAttribute('data-timestamp'));
        const created = element.getAttribute('data-created');
        const diffSeconds = now - timestamp;
        const diffMinutes = Math.floor(diffSeconds / 60);
        
        // If older than 5 minutes, show the actual date/time
        if (diffMinutes >= 5) {
            element.textContent = created;
        } else {
            // Show relative time
            if (diffSeconds < 60) {
                element.textContent = 'prieš ' + diffSeconds + ' sek.';
            } else if (diffMinutes === 1) {
                element.textContent = 'prieš 1 min.';
            } else {
                element.textContent = 'prieš ' + diffMinutes + ' min.';
            }
        }
    });
}

// Update timestamps immediately and then every 10 seconds
if (document.querySelectorAll('[data-timestamp]').length > 0) {
    updateTimestamps();
    setInterval(updateTimestamps, 10000);
}
</script>
