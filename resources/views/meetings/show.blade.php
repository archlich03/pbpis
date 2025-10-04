@section('title', __('Meeting Details') . ' - ' . config('app.name', 'PBPIS'))

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
                    {{-- Meeting Information Section --}}
                    @include('meetings.partials.meeting-info')
                    
                    <hr class="border-t-2 border-gray-300 dark:border-gray-600 mt-4 mb-4">
                    
                    {{-- Attendance Management Section --}}
                    @include('meetings.partials.attendance-management')
                    
                    @if ($meeting->body->members->contains(Auth::user()) || Auth::User()->isPrivileged())
                        <hr class="border-t-2 border-gray-300 dark:border-gray-600 mt-4 mb-4">
                    @endif
                    
                    {{-- Questions Section --}}
                    @include('meetings.partials.questions-section')
                    
                    @if ($meeting->body->members->contains(Auth::user()) || Auth::User()->isPrivileged())
                        <hr class="border-t-2 border-gray-300 dark:border-gray-600 mt-4 mb-4">
                    @endif
                    
                    {{-- Voting Process Section --}}
                    @include('meetings.partials.voting-process')
                    
                    @if (Auth::user()->isPrivileged() || Auth::user()->isSecretary())
                        <hr class="border-t-2 border-gray-300 dark:border-gray-600 mt-4 mb-4">
                    @endif
                    
                    {{-- Proxy Voting Section --}}
                    @include('meetings.partials.proxy-voting')
                </div>
            </div>
        </div>
    </div>

    <script>
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
