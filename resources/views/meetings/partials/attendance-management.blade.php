@if ($meeting->body->members->contains(Auth::user()) || Auth::User()->isPrivileged())
    @php
        $now = \Carbon\Carbon::now();
        $canEditAttendance = $now >= $meeting->vote_start && $now <= $meeting->vote_end;
    @endphp
    
    @if ($canEditAttendance)
            <div class="ml-4">
                @if (Auth::user()->isPrivileged())
                    <div class="mb-4 space-x-2">
                        <form method="POST" action="{{ route('attendance.mark-all', $meeting) }}" class="inline">
                            @csrf
                            <x-secondary-button type="submit">
                                {{ __('Mark All Present') }}
                            </x-secondary-button>
                        </form>
                        
                        <form method="POST" action="{{ route('attendance.mark-non-voters-absent', $meeting) }}" class="inline">
                            @csrf
                            <x-secondary-button type="submit" class="bg-orange-100 text-orange-800 hover:bg-orange-200 dark:bg-orange-900 dark:text-orange-200 dark:hover:bg-orange-800">
                                {{ __('Mark Non-Voters Absent') }}
                            </x-secondary-button>
                        </form>
                    </div>
                @endif
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach ($meeting->body->members->sortBy('name') as $member)
                        @php
                            $isAttending = $meeting->isUserAttending($member);
                            $canToggle = Auth::user()->isPrivileged() || Auth::user()->user_id == $member->user_id;
                        @endphp
                        
                        @if ($canToggle)
                            <form method="POST" action="{{ route('attendance.toggle', $meeting) }}" class="w-full">
                                @csrf
                                <input type="hidden" name="user_id" value="{{ $member->user_id }}">
                                <button type="submit" class="w-full flex items-center justify-between p-3 border rounded-lg cursor-pointer transition-colors {{ $isAttending ? 'bg-green-50 border-green-200 hover:bg-green-100 dark:bg-green-900 dark:border-green-700 dark:hover:bg-green-800' : 'bg-gray-50 border-gray-200 hover:bg-gray-100 dark:bg-gray-800 dark:border-gray-600 dark:hover:bg-gray-700' }}">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-3 h-3 rounded-full {{ $isAttending ? 'bg-green-500' : 'bg-gray-400' }}"></div>
                                        <div class="text-left">
                                            <div class="font-medium text-gray-900 dark:text-gray-100">
                                                {{ $member->pedagogical_name }} {{ $member->name }}
                                            </div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ $isAttending ? __('Present') : __('Absent') }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-xs px-2 py-1 rounded-full {{ $isAttending ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300' : 'bg-gray-100 text-gray-700 dark:bg-gray-900 dark:text-gray-300' }}">
                                        {{ $isAttending ? '✓' : '○' }}
                                    </div>
                                </button>
                            </form>
                        @else
                            <div class="flex items-center justify-between p-3 border rounded-lg {{ $isAttending ? 'bg-green-50 border-green-200 dark:bg-green-900 dark:border-green-700' : 'bg-gray-50 border-gray-200 dark:bg-gray-800 dark:border-gray-600' }}">
                                <div class="flex items-center space-x-3">
                                    <div class="w-3 h-3 rounded-full {{ $isAttending ? 'bg-green-500' : 'bg-gray-400' }}"></div>
                                    <div>
                                        <div class="font-medium text-gray-900 dark:text-gray-100">
                                            {{ $member->pedagogical_name }} {{ $member->name }}
                                        </div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ $isAttending ? __('Present') : __('Absent') }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
                
                <div class="mt-4 space-y-3">
                    <div class="p-3 bg-blue-50 border border-blue-200 rounded-lg dark:bg-blue-900 dark:border-blue-700">
                        <div class="flex items-center space-x-2">
                            <svg class="w-5 h-5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                            </svg>
                            <div class="text-sm text-blue-700 dark:text-blue-300">
                                <strong>{{ __('Attendees') }}:</strong> {{ $meeting->getAttendeesCount() }} / {{ $meeting->body->members->count() }}
                                @if ($meeting->body->members->count() > 0)
                                    ({{ round(($meeting->getAttendeesCount() / $meeting->body->members->count()) * 100, 1) }}%)
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    <div class="p-3 border rounded-lg {{ $meeting->hasQuorum() ? 'bg-green-50 border-green-200 dark:bg-green-900 dark:border-green-700' : 'bg-red-50 border-red-200 dark:bg-red-900 dark:border-red-700' }}">
                        <div class="flex items-center space-x-2">
                            <svg class="w-5 h-5 {{ $meeting->hasQuorum() ? 'text-green-500' : 'text-red-500' }}" fill="currentColor" viewBox="0 0 20 20">
                                @if ($meeting->hasQuorum())
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                @else
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                @endif
                            </svg>
                            <div class="text-sm {{ $meeting->hasQuorum() ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300' }}">
                                <strong>{{ __('Quorum') }}:</strong> 
                                @if ($meeting->hasQuorum())
                                    {{ __('Reached') }} - {{ __('Meeting is valid') }}
                                @else
                                    {{ __('Not reached') }} - {{ __('Meeting is not valid') }}
                                @endif
                                <br>
                                <small>{{ __('Required') }}: {{ ceil($meeting->body->members->count() / 2) }} {{ __('attendees') }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
    @endif
@endif
