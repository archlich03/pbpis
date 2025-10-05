@section('title', __('Audit Logs') . ' - ' . config('app.name', 'PBPIS'))

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Audit Logs') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    
                    <!-- Search and Filter Form -->
                    <div class="mb-6 bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                        <form method="GET" action="{{ route('audit.logs') }}" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                <!-- Search -->
                                <div>
                                    <x-input-label for="search" :value="__('Search')" />
                                    <x-text-input id="search" name="search" type="text" 
                                                  value="{{ request('search') }}" 
                                                  placeholder="{{ __('Search by action, IP, user agent, user...') }}" 
                                                  class="mt-1 block w-full" />
                                </div>

                                <!-- User Filter -->
                                <div>
                                    <x-input-label for="user_search" :value="__('User')" />
                                    <div class="relative">
                                        <x-text-input id="user_search" name="user_search" type="text"
                                                      value="{{ request('user_search') }}"
                                                      placeholder="{{ __('Search by name or email...') }}"
                                                      class="mt-1 block w-full"
                                                      autocomplete="off" />
                                        <div id="user_suggestions" class="absolute z-10 w-full bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg mt-1 hidden max-h-60 overflow-y-auto">
                                            <!-- Suggestions will be populated by JavaScript -->
                                        </div>
                                        <input type="hidden" id="user_id" name="user_id" value="{{ request('user_id') }}" />
                                    </div>
                                </div>

                                <!-- Action Filter -->
                                <div>
                                    <x-input-label for="action" :value="__('Action Type')" />
                                    <select id="action" name="action" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                                        <option value="">{{ __('All Actions') }}</option>
                                        @foreach($availableActions as $action)
                                            <option value="{{ $action }}" {{ request('action') == $action ? 'selected' : '' }}>
                                                {{ \App\Services\AuditLogService::getActionName($action) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Sort -->
                                <div>
                                    <x-input-label for="sort" :value="__('Sort By')" />
                                    <select id="sort" name="sort" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                                        <option value="created_at" {{ request('sort') == 'created_at' ? 'selected' : '' }}>{{ __('Date') }}</option>
                                        <option value="action" {{ request('sort') == 'action' ? 'selected' : '' }}>{{ __('Action') }}</option>
                                        <option value="user_id" {{ request('sort') == 'user_id' ? 'selected' : '' }}>{{ __('User') }}</option>
                                        <option value="ip_address" {{ request('sort') == 'ip_address' ? 'selected' : '' }}>{{ __('IP Address') }}</option>
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <!-- Date From -->
                                <div>
                                    <x-input-label for="date_from" :value="__('Date From')" />
                                    <x-text-input id="date_from" name="date_from" type="date" 
                                                  value="{{ request('date_from') }}" 
                                                  class="mt-1 block w-full" />
                                </div>

                                <!-- Date To -->
                                <div>
                                    <x-input-label for="date_to" :value="__('Date To')" />
                                    <x-text-input id="date_to" name="date_to" type="date" 
                                                  value="{{ request('date_to') }}" 
                                                  class="mt-1 block w-full" />
                                </div>
                                
                                <!-- Per Page -->
                                <div>
                                    <x-input-label for="per_page" :value="__('Items per page')" />
                                    <select id="per_page" name="per_page" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                                        <option value="10" {{ request('per_page', 20) == 10 ? 'selected' : '' }}>10</option>
                                        <option value="20" {{ request('per_page', 20) == 20 ? 'selected' : '' }}>20</option>
                                        <option value="50" {{ request('per_page', 20) == 50 ? 'selected' : '' }}>50</option>
                                        <option value="100" {{ request('per_page', 20) == 100 ? 'selected' : '' }}>100</option>
                                    </select>
                                </div>
                            </div>

                            <div class="flex items-center space-x-4">
                                <x-primary-button type="submit">
                                    {{ __('Filter') }}
                                </x-primary-button>
                                
                                <a href="{{ route('audit.logs') }}" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">
                                    {{ __('Clear Filters') }}
                                </a>
                                
                                <!-- Sort Direction -->
                                <input type="hidden" name="direction" value="{{ request('direction', 'desc') }}">
                            </div>
                        </form>
                    </div>

                    <!-- Results Count -->
                    <div class="mb-4">
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{ __('Showing :from to :to of :total results', [
                                'from' => $auditLogs->firstItem() ?? 0,
                                'to' => $auditLogs->lastItem() ?? 0,
                                'total' => $auditLogs->total()
                            ]) }}
                        </p>
                    </div>

                    <!-- Audit Logs Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'created_at', 'direction' => request('direction') == 'asc' ? 'desc' : 'asc']) }}" class="hover:text-gray-700 dark:hover:text-gray-100">
                                            {{ __('Date') }}
                                            @if(request('sort') == 'created_at')
                                                @if(request('direction') == 'asc') ↑ @else ↓ @endif
                                            @endif
                                        </a>
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'user_id', 'direction' => request('direction') == 'asc' ? 'desc' : 'asc']) }}" class="hover:text-gray-700 dark:hover:text-gray-100">
                                            {{ __('User') }}
                                            @if(request('sort') == 'user_id')
                                                @if(request('direction') == 'asc') ↑ @else ↓ @endif
                                            @endif
                                        </a>
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'action', 'direction' => request('direction') == 'asc' ? 'desc' : 'asc']) }}" class="hover:text-gray-700 dark:hover:text-gray-100">
                                            {{ __('Action') }}
                                            @if(request('sort') == 'action')
                                                @if(request('direction') == 'asc') ↑ @else ↓ @endif
                                            @endif
                                        </a>
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'ip_address', 'direction' => request('direction') == 'asc' ? 'desc' : 'asc']) }}" class="hover:text-gray-700 dark:hover:text-gray-100">
                                            {{ __('IP Address') }}
                                            @if(request('sort') == 'ip_address')
                                                @if(request('direction') == 'asc') ↑ @else ↓ @endif
                                            @endif
                                        </a>
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        {{ __('Details') }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse($auditLogs as $log)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ $log->created_at->format('Y-m-d H:i:s') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                            @if($log->user)
                                                <div>
                                                    <div class="font-medium">{{ $log->user->name }}</div>
                                                    <div class="text-gray-500 dark:text-gray-400">{{ $log->user->email }}</div>
                                                </div>
                                            @elseif($log->deleted_user_name)
                                                <div>
                                                    <div class="font-medium text-red-600 dark:text-red-400">{{ $log->deleted_user_name }}</div>
                                                    <div class="text-gray-500 dark:text-gray-400">{{ $log->deleted_user_email }}</div>
                                                    <div class="text-xs text-red-500 dark:text-red-400 italic">{{ __('(Deleted User)') }}</div>
                                                </div>
                                            @else
                                                <span class="text-gray-400 dark:text-gray-500">{{ __('Unknown User') }}</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $log->action_badge_classes }}">
                                                {{ $log->action_name }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                            <div class="flex flex-col">
                                                <span class="font-medium">{{ $log->ip_address }}</span>
                                                @if($log->user_agent)
                                                    <span class="text-xs text-gray-500 dark:text-gray-400 mt-1 truncate max-w-xs cursor-pointer hover:text-gray-700 dark:hover:text-gray-300 transition-colors" 
                                                          title="{{ $log->user_agent }} (Double-click to copy)"
                                                          ondblclick="copyToClipboard(this, '{{ addslashes($log->user_agent) }}')">
                                                        {{ Str::limit($log->user_agent, 50) }}
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                            @if($log->details)
                                                <div class="max-w-md">
                                                    @if(is_array($log->details))
                                                        @foreach($log->details as $key => $value)
                                                            <div class="text-xs mb-1">
                                                                <span class="font-medium">{{ $key }}:</span> {{ $value }}
                                                            </div>
                                                        @endforeach
                                                    @else
                                                        <div class="text-xs">{{ $log->details }}</div>
                                                    @endif
                                                </div>
                                            @else
                                                <span class="text-gray-400 dark:text-gray-500">{{ __('No details') }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                            {{ __('No audit logs found.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="mt-6">
                        {{ $auditLogs->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const userSearch = document.getElementById('user_search');
            const userSuggestions = document.getElementById('user_suggestions');
            const userIdInput = document.getElementById('user_id');
            let debounceTimer;
            
            // Set initial display value if user is selected
            if (userIdInput.value) {
                const selectedUser = @json($availableUsers->firstWhere('user_id', request('user_id')));
                if (selectedUser) {
                    userSearch.value = selectedUser.name + ' (' + selectedUser.email + ')';
                }
            }
            
            userSearch.addEventListener('input', function() {
                clearTimeout(debounceTimer);
                const query = this.value.toLowerCase();
                
                if (query.length < 2) {
                    userSuggestions.classList.add('hidden');
                    return;
                }
                
                debounceTimer = setTimeout(() => {
                    const users = @json($availableUsers);
                    const filteredUsers = users.filter(user => 
                        user.name.toLowerCase().includes(query) || 
                        user.email.toLowerCase().includes(query)
                    ).slice(0, 10); // Limit to 10 results
                    
                    if (filteredUsers.length > 0) {
                        userSuggestions.innerHTML = filteredUsers.map(user => 
                            `<div class="px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer user-suggestion" data-user-id="${user.user_id}" data-user-name="${user.name}" data-user-email="${user.email}">
                                ${user.name} (${user.email})
                            </div>`
                        ).join('');
                        
                        // Add click handlers to suggestions
                        userSuggestions.querySelectorAll('.user-suggestion').forEach(suggestion => {
                            suggestion.addEventListener('click', function() {
                                const userId = this.dataset.userId;
                                const userName = this.dataset.userName;
                                const userEmail = this.dataset.userEmail;
                                
                                userSearch.value = userName + ' (' + userEmail + ')';
                                userIdInput.value = userId;
                                userSuggestions.classList.add('hidden');
                            });
                        });
                        
                        userSuggestions.classList.remove('hidden');
                    } else {
                        userSuggestions.innerHTML = '<div class="px-4 py-2 text-gray-500 dark:text-gray-400">{{ __('No users found') }}</div>';
                        userSuggestions.classList.remove('hidden');
                    }
                }, 300);
            });
            
            // Clear selection when input is cleared
            userSearch.addEventListener('input', function() {
                if (this.value === '') {
                    userIdInput.value = '';
                }
            });
            
            // Hide suggestions when clicking outside
            document.addEventListener('click', function(e) {
                if (!userSearch.contains(e.target) && !userSuggestions.contains(e.target)) {
                    userSuggestions.classList.add('hidden');
                }
            });
        });
        
        // Copy to clipboard function
        function copyToClipboard(element, text) {
            navigator.clipboard.writeText(text).then(function() {
                // Show visual feedback
                const originalText = element.textContent;
                const originalTitle = element.title;
                element.textContent = '✓ Copied!';
                element.title = 'Copied to clipboard!';
                element.classList.add('text-green-600', 'dark:text-green-400');
                
                // Reset after 1.5 seconds
                setTimeout(function() {
                    element.textContent = originalText;
                    element.title = originalTitle;
                    element.classList.remove('text-green-600', 'dark:text-green-400');
                }, 1500);
            }).catch(function(err) {
                console.error('Failed to copy:', err);
                alert('Failed to copy to clipboard');
            });
        }
    </script>
</x-app-layout>
