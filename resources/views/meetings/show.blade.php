@section('title', __('Meeting Details') . ' - ' . config('app.name', 'POBIS'))

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Meeting') }} {{ $meeting->meeting_date->format('Y-m-d') }} - <a href="{{ route('bodies.show', $meeting->body) }}" class="text-blue-500 hover:underline">{{ $meeting->body->title }}</a> ({{ $meeting->body->is_ba_sp ? 'BA' : 'MA' }})
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg" style="overflow: visible;">
                <div x-data="{ activeTab: localStorage.getItem('meeting-{{ $meeting->meeting_id }}-tab') || 'voting' }" 
                     x-init="$watch('activeTab', value => localStorage.setItem('meeting-{{ $meeting->meeting_id }}-tab', value))"
                     class="text-gray-900 dark:text-gray-100">
                    
                    {{-- Tab Navigation --}}
                    <div class="border-b border-gray-200 dark:border-gray-700 overflow-x-auto scrollbar-hide" style="overflow-y: hidden;">
                        <nav class="flex space-x-2 border-b border-gray-200 dark:border-gray-700 overflow-x-auto" role="tablist">
                            {{-- Meeting Information Tab - Visible to all --}}
                            <button @click="activeTab = 'info'" 
                                    :class="activeTab === 'info' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'"
                                    class="whitespace-nowrap py-4 px-4 sm:px-6 border-b-2 font-medium text-sm transition-colors"
                                    role="tab"
                                    :aria-selected="activeTab === 'info'"
                                    :tabindex="activeTab === 'info' ? 0 : -1">
                                {{ __('Meeting Information') }}
                            </button>
                            
                            {{-- Attendance Tab - Only show to privileged users when meeting is in progress --}}
                            @if (Auth::User()->isPrivileged() && $meeting->status == 'Vyksta')
                                <button @click="activeTab = 'attendance'" 
                                        :class="activeTab === 'attendance' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'"
                                        class="whitespace-nowrap py-4 px-4 sm:px-6 border-b-2 font-medium text-sm transition-colors"
                                        role="tab"
                                        :aria-selected="activeTab === 'attendance'"
                                        :tabindex="activeTab === 'attendance' ? 0 : -1">
                                    {{ __('Attendance') }}
                                </button>
                            @endif
                            
                            {{-- Questions Tab - Hidden from regular voters --}}
                            @if (Auth::User()->isPrivileged())
                                <button @click="activeTab = 'questions'" 
                                        :class="activeTab === 'questions' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'"
                                        class="whitespace-nowrap py-4 px-4 sm:px-6 border-b-2 font-medium text-sm transition-colors"
                                        role="tab"
                                        :aria-selected="activeTab === 'questions'"
                                        :tabindex="activeTab === 'questions' ? 0 : -1">
                                    {{ __('Questions') }}
                                </button>
                            @endif
                            
                            {{-- Voting Tab - Hide when meeting is planned --}}
                            @if (($meeting->body->members->contains(Auth::user()) || Auth::User()->isPrivileged()) && $meeting->status !== 'Suplanuotas')
                                <button @click="activeTab = 'voting'" 
                                        :class="activeTab === 'voting' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'"
                                        class="whitespace-nowrap py-4 px-4 sm:px-6 border-b-2 font-medium text-sm transition-colors"
                                        role="tab"
                                        :aria-selected="activeTab === 'voting'"
                                        :tabindex="activeTab === 'voting' ? 0 : -1">
                                    {{ __('Voting') }}
                                </button>
                            @endif
                            
                            {{-- Proxy Voting Tab - Only show when meeting is in progress --}}
                            @if ((Auth::user()->isPrivileged() || Auth::user()->isSecretary()) && $meeting->status == 'Vyksta')
                                <button @click="activeTab = 'proxy'" 
                                        :class="activeTab === 'proxy' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'"
                                        class="whitespace-nowrap py-4 px-4 sm:px-6 border-b-2 font-medium text-sm transition-colors"
                                        role="tab"
                                        :aria-selected="activeTab === 'proxy'"
                                        :tabindex="activeTab === 'proxy' ? 0 : -1">
                                    {{ __('Proxy Voting') }}
                                </button>
                            @endif
                            
                            {{-- Discussions Tab - Only for e-vote meetings --}}
                            @if ($meeting->is_evote && ($meeting->body->members->contains(Auth::user()) || Auth::User()->isPrivileged()))
                                <button @click="activeTab = 'discussions'" 
                                        :class="activeTab === 'discussions' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'"
                                        class="whitespace-nowrap py-4 px-4 sm:px-6 border-b-2 font-medium text-sm transition-colors"
                                        role="tab"
                                        :aria-selected="activeTab === 'discussions'"
                                        :tabindex="activeTab === 'discussions' ? 0 : -1">
                                    {{ __('Discussions') }}
                                </button>
                            @endif
                        </nav>
                    </div>
                    
                    {{-- Tab Panels --}}
                    <div class="p-6">
                        {{-- Meeting Information Panel - Visible to all (read-only for voters) --}}
                        <div x-show="activeTab === 'info'" 
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0"
                             x-transition:enter-end="opacity-100"
                             role="tabpanel"
                             :aria-hidden="activeTab !== 'info'">
                            @include('meetings.partials.meeting-info')
                        </div>
                        
                        {{-- Attendance Management Panel - Hidden from regular voters --}}
                        @if (Auth::User()->isPrivileged())
                            <div x-show="activeTab === 'attendance'" 
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0"
                                 x-transition:enter-end="opacity-100"
                                 role="tabpanel"
                                 :aria-hidden="activeTab !== 'attendance'">
                                @include('meetings.partials.attendance-management')
                            </div>
                        @endif
                        
                        {{-- Questions Panel - Hidden from regular voters --}}
                        @if (Auth::User()->isPrivileged())
                            <div x-show="activeTab === 'questions'" 
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0"
                                 x-transition:enter-end="opacity-100"
                                 role="tabpanel"
                                 :aria-hidden="activeTab !== 'questions'">
                                @include('meetings.partials.questions-section')
                            </div>
                        @endif
                        
                        {{-- Voting Process Panel --}}
                        @if ($meeting->body->members->contains(Auth::user()) || Auth::User()->isPrivileged())
                            <div x-show="activeTab === 'voting'" 
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0"
                                 x-transition:enter-end="opacity-100"
                                 role="tabpanel"
                                 :aria-hidden="activeTab !== 'voting'">
                                @include('meetings.partials.voting-process')
                            </div>
                        @endif
                        
                        {{-- Proxy Voting Panel --}}
                        @if (Auth::user()->isPrivileged() || Auth::user()->isSecretary())
                            <div x-show="activeTab === 'proxy'" 
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0"
                                 x-transition:enter-end="opacity-100"
                                 role="tabpanel"
                                 :aria-hidden="activeTab !== 'proxy'">
                                @include('meetings.partials.proxy-voting')
                            </div>
                        @endif
                        
                        {{-- Discussions Panel - Only for e-vote meetings --}}
                        @if ($meeting->is_evote && ($meeting->body->members->contains(Auth::user()) || Auth::User()->isPrivileged()))
                            <div x-show="activeTab === 'discussions'" 
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0"
                                 x-transition:enter-end="opacity-100"
                                 role="tabpanel"
                                 :aria-hidden="activeTab !== 'discussions'">
                                @include('meetings.partials.discussions-tab')
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Keyboard navigation for tabs (accessibility)
        document.addEventListener('DOMContentLoaded', function() {
            const tabButtons = document.querySelectorAll('[role="tab"]');
            
            tabButtons.forEach((tab, index) => {
                tab.addEventListener('keydown', function(e) {
                    let newIndex;
                    
                    // Arrow key navigation
                    if (e.key === 'ArrowRight' || e.key === 'ArrowDown') {
                        e.preventDefault();
                        newIndex = (index + 1) % tabButtons.length;
                    } else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') {
                        e.preventDefault();
                        newIndex = (index - 1 + tabButtons.length) % tabButtons.length;
                    } else if (e.key === 'Home') {
                        e.preventDefault();
                        newIndex = 0;
                    } else if (e.key === 'End') {
                        e.preventDefault();
                        newIndex = tabButtons.length - 1;
                    }
                    
                    if (newIndex !== undefined) {
                        tabButtons[newIndex].click();
                        tabButtons[newIndex].focus();
                    }
                });
            });
        });
        
        // Persist details element state
        document.addEventListener('DOMContentLoaded', function() {
            const details = document.querySelectorAll('details');
            
            details.forEach(function(detail) {
                // Use ID if available (for questions), otherwise use a generic key
                let key;
                if (detail.id) {
                    key = `meeting-{{ $meeting->meeting_id }}-${detail.id}`;
                } else {
                    // For sections without IDs, use the summary text as identifier
                    const summaryText = detail.querySelector('summary')?.textContent.trim().substring(0, 30);
                    key = `meeting-{{ $meeting->meeting_id }}-${summaryText}`;
                }
                
                const isOpen = localStorage.getItem(key) === 'true';
                
                if (isOpen) {
                    detail.open = true;
                }
                
                detail.addEventListener('toggle', function() {
                    localStorage.setItem(key, detail.open);
                });
            });
        });
        
        function questionReorder() {
            return {
                reorderMode: false
            }
        }
        
        // Global function to handle question movement
        function moveQuestion(questionId, direction) {
            const questionsContainer = document.querySelector('[x-data="questionReorder()"]');
            const questionElements = Array.from(questionsContainer.querySelectorAll('details[data-question-id]'));
            
            const currentElement = questionsContainer.querySelector(`details[data-question-id="${questionId}"]`);
            const currentIndex = questionElements.indexOf(currentElement);
            
            if (direction === 'up' && currentIndex > 0) {
                const previousElement = questionElements[currentIndex - 1];
                questionsContainer.insertBefore(currentElement, previousElement);
            } else if (direction === 'down' && currentIndex < questionElements.length - 1) {
                const nextElement = questionElements[currentIndex + 1];
                questionsContainer.insertBefore(nextElement, currentElement);
            }
            
            // Send AJAX request to update order
            const newOrder = Array.from(questionsContainer.querySelectorAll('details[data-question-id]'))
                .map(el => el.getAttribute('data-question-id'));
            
            fetch('{{ route("questions.reorder", $meeting) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ questions: newOrder })
            }).catch(error => {
                console.error('Error updating question order:', error);
                // Optionally revert the UI change on error
            });
        }
    </script>
</x-app-layout>
