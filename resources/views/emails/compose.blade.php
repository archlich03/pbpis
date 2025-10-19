@section('title', __('Send Email') . ' - ' . config('app.name', 'POBIS'))

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Send Email') }} - {{ $meeting->body->title }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    
                    <!-- Back button -->
                    <div class="mb-6">
                        <a href="{{ route('meetings.show', $meeting) }}" 
                           class="inline-flex items-center text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                            {{ __('Back to Meeting') }}
                        </a>
                    </div>

                    <!-- Error/Success Messages -->
                    @if (session('error'))
                        <div class="mb-6 p-4 bg-red-100 border border-red-400 text-red-700 dark:bg-red-900 dark:border-red-700 dark:text-red-300 rounded">
                            {{ session('error') }}
                        </div>
                    @endif
                    @if (session('success'))
                        <div class="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 dark:bg-green-900 dark:border-green-700 dark:text-green-300 rounded">
                            {{ session('success') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('emails.send', $meeting) }}" class="space-y-6">
                        @csrf

                        <!-- Recipients -->
                        <div x-data="{ selectAll: true }">
                            <div class="flex items-center justify-between mb-2">
                                <x-input-label for="recipients" :value="__('Recipients')" />
                                <button type="button" 
                                        @click="selectAll = !selectAll; document.querySelectorAll('input[name=\'recipients[]\']').forEach(cb => cb.checked = selectAll)"
                                        class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300">
                                    <span x-text="selectAll ? '{{ __('Deselect All') }}' : '{{ __('Select All') }}'"></span>
                                </button>
                            </div>
                            <div class="mt-2 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg space-y-2">
                                @foreach($meeting->body->members as $member)
                                    <label class="flex items-center space-x-2 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600 p-2 rounded">
                                        <input type="checkbox" 
                                               name="recipients[]" 
                                               value="{{ $member->email }}" 
                                               checked
                                               class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                        <span class="text-sm text-gray-700 dark:text-gray-300">
                                            {{ $member->pedagogical_name }} {{ $member->name }} ({{ $member->email }})
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                            <x-input-error :messages="$errors->get('recipients')" class="mt-2" />
                        </div>

                        <!-- Subject -->
                        <div>
                            <x-input-label for="subject" :value="__('Subject')" />
                            <x-text-input 
                                id="subject" 
                                name="subject" 
                                type="text" 
                                class="mt-1 block w-full" 
                                :value="old('subject', $template['subject'])" 
                                required 
                                maxlength="255" />
                            <x-input-error :messages="$errors->get('subject')" class="mt-2" />
                        </div>

                        <!-- Body -->
                        <div>
                            <x-input-label for="body" :value="__('Message')" />
                            <x-tiptap-editor name="body" :value="old('body', $template['body'])" id="body" />
                            <x-input-error :messages="$errors->get('body')" class="mt-2" />
                        </div>

                        <!-- Preview -->
                        <div>
                            <button type="button" 
                                    onclick="togglePreview()"
                                    class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                {{ __('Toggle Preview') }}
                            </button>
                        </div>

                        <div id="preview" class="hidden p-4 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                            <h3 class="font-semibold mb-2">{{ __('Preview') }}:</h3>
                            <div class="bg-white dark:bg-gray-800 p-4 rounded">
                                <div class="border-b border-gray-200 dark:border-gray-600 pb-2 mb-4">
                                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Subject') }}:</p>
                                    <p class="font-semibold" id="previewSubject">{{ $template['subject'] }}</p>
                                </div>
                                <div id="previewBody" class="prose dark:prose-invert max-w-none">
                                    {!! $template['body'] !!}
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-end gap-4">
                            <a href="{{ route('meetings.show', $meeting) }}" 
                               class="inline-flex items-center px-4 py-2 bg-gray-300 dark:bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest hover:bg-gray-400 dark:hover:bg-gray-500 focus:bg-gray-400 dark:focus:bg-gray-500 active:bg-gray-500 dark:active:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                                {{ __('Cancel') }}
                            </a>
                            
                            <x-primary-button type="submit">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                                {{ __('Send Email') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Preview toggle
        function togglePreview() {
            const preview = document.getElementById('preview');
            const subject = document.getElementById('subject').value;
            
            // Get TipTap editor content
            const bodyEditor = document.querySelector('[name="body"]');
            const body = bodyEditor ? bodyEditor.value : '';
            
            document.getElementById('previewSubject').textContent = subject;
            document.getElementById('previewBody').innerHTML = body;
            
            preview.classList.toggle('hidden');
        }

        // Update preview when subject changes
        document.getElementById('subject').addEventListener('input', function() {
            document.getElementById('previewSubject').textContent = this.value;
        });
    </script>
</x-app-layout>
