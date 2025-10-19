@section('title', __('Create Body') . ' - ' . config('app.name', 'POBIS'))

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Create new body') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <form method="post" action="{{ route('bodies.store') }}" class="p-6 space-y-6">
                    @csrf
                    
                    <div>
                        <x-input-label for="title" value="{{ __('Body name') }}" />
                        <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" />
                    </div>

                    <div class="mt-6">
                        <x-input-label for="classification" value="{{ __('Classification') }}" />
                        <select id="classification" name="classification" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm mt-1 block w-full">
                            <option value="SPK" selected>SPK</option>
                        </select>
                    </div>

                    <div class="mt-6">
                        <x-input-label for="is_ba_sp" value="{{ __('Type') }}" class="flex items-center" />
                        <div class="mt-1 flex items-center space-x-4">
                            <div class="flex items-center">
                                <input id="is_ba_sp_0" type="radio" name="is_ba_sp" value="0" class="focus:ring-indigo-500 dark:focus:ring-indigo-600 h-4 w-4 text-indigo-600 border-gray-300 dark:border-gray-700 dark:bg-gray-900 rounded" />
                                <label for="is_ba_sp_0" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                                    MA
                                </label>
                            </div>
                            <div class="flex items-center">
                                <input id="is_ba_sp_1" type="radio" name="is_ba_sp" value="1" class="focus:ring-indigo-500 dark:focus:ring-indigo-600 h-4 w-4 text-indigo-600 border-gray-300 dark:border-gray-700 dark:bg-gray-900 rounded" />
                                <label for="is_ba_sp_1" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                                    BA
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6">
                        <x-input-label for="chairman_id" value="{{ __('Chairperson') }}" />
                        <select id="chairman_id" name="chairman_id" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm block mt-1 w-full" required>
                            <option value="">{{ __('Select chairperson') }}</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->user_id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mt-6" x-data="memberSelector()">
                        <x-input-label for="members" value="{{ __('Members') }}" />
                        
                        <!-- Search Input -->
                        <div class="mt-2 relative">
                            <input 
                                type="text" 
                                x-model="searchQuery" 
                                @input="filterUsers()" 
                                placeholder="{{ __('Search members by name or email...') }}"
                                class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm block w-full"
                            />
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>
                        </div>
                        
                        <!-- Selected Members Display -->
                        <div class="mt-4" x-show="selectedMembers.length > 0">
                            <div class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ __('Selected Members') }} (<span x-text="selectedMembers.length"></span>):
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <template x-for="member in selectedMembers" :key="member.id">
                                    <div class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300">
                                        <span x-text="member.name + (member.pedagogical_name ? ' (' + member.pedagogical_name + ')' : '')"></span>
                                        <button type="button" @click="removeMember(member.id)" class="ml-2 text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-200">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>
                        
                        <!-- Search Results -->
                        <div class="mt-4 max-h-60 overflow-y-auto border border-gray-300 dark:border-gray-600 rounded-md" x-show="searchQuery.length > 0 && filteredUsers.length > 0">
                            <template x-for="user in filteredUsers" :key="user.id">
                                <div 
                                    class="p-3 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer border-b border-gray-200 dark:border-gray-600 last:border-b-0"
                                    @click="toggleMember(user)"
                                >
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <div class="font-medium text-gray-900 dark:text-gray-100" x-text="user.name"></div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                <span x-text="user.email"></span>
                                                <span x-show="user.pedagogical_name" x-text="' (' + user.pedagogical_name + ')'"></span>
                                            </div>
                                        </div>
                                        <div class="flex items-center">
                                            <div 
                                                class="w-5 h-5 rounded border-2 flex items-center justify-center"
                                                :class="isSelected(user.id) ? 'bg-indigo-600 border-indigo-600' : 'border-gray-300 dark:border-gray-600'"
                                            >
                                                <svg x-show="isSelected(user.id)" class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                        
                        <!-- No Results Message -->
                        <div x-show="searchQuery.length > 0 && filteredUsers.length === 0" class="mt-4 p-4 text-center text-gray-500 dark:text-gray-400 border border-gray-300 dark:border-gray-600 rounded-md">
                            {{ __('No members found matching your search.') }}
                        </div>
                        
                        <!-- Hidden inputs for form submission -->
                        <template x-for="member in selectedMembers" :key="member.id">
                            <input type="hidden" name="members[]" :value="member.id">
                        </template>
                    </div>
                    
                    <script>
                        function memberSelector() {
                            return {
                                searchQuery: '',
                                allUsers: {!! json_encode($users->map(function($user) {
                                    return [
                                        'id' => $user->user_id,
                                        'name' => $user->name,
                                        'email' => $user->email,
                                        'pedagogical_name' => $user->pedagogical_name
                                    ];
                                })->values()) !!},
                                filteredUsers: [],
                                selectedMembers: [],
                                
                                filterUsers() {
                                    if (this.searchQuery.length === 0) {
                                        this.filteredUsers = [];
                                        return;
                                    }
                                    
                                    const query = this.searchQuery.toLowerCase();
                                    this.filteredUsers = this.allUsers.filter(user => {
                                        return user.name.toLowerCase().includes(query) ||
                                               user.email.toLowerCase().includes(query) ||
                                               (user.pedagogical_name && user.pedagogical_name.toLowerCase().includes(query));
                                    }).slice(0, 10); // Limit to 10 results
                                },
                                
                                toggleMember(user) {
                                    if (this.isSelected(user.id)) {
                                        this.removeMember(user.id);
                                    } else {
                                        this.selectedMembers.push(user);
                                    }
                                },
                                
                                removeMember(userId) {
                                    this.selectedMembers = this.selectedMembers.filter(member => member.id !== userId);
                                },
                                
                                isSelected(userId) {
                                    return this.selectedMembers.some(member => member.id === userId);
                                }
                            }
                        }
                    </script>

                    <div class="flex items-center gap-4 mt-6">
                        <x-primary-button>{{ __('Create') }}</x-primary-button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</x-app-layout>
