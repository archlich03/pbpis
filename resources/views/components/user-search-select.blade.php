@props(['name', 'label', 'users', 'selected' => null, 'filter' => null, 'required' => false])

<div x-data="userSearchSelect{{ Str::camel($name) }}()" class="mt-4 relative">
    <x-input-label :for="$name" :value="$label" />
    
    <!-- Search Input - Hidden when user is selected -->
    <div x-show="!selectedUser">
        <input 
            type="text" 
            x-model="searchQuery"
            @input="filterUsers()"
            @focus="showDropdown = true"
            placeholder="{{ __('Search by name or email') }}..."
            class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm block mt-1 w-full"
        />
    </div>
    
    <!-- Selected User Display -->
    <div x-show="selectedUser" class="mt-1">
        <div class="flex items-center justify-between px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-gray-50 dark:bg-gray-700">
            <span class="text-gray-900 dark:text-gray-100" x-text="selectedUser ? (selectedUser.pedagogical_name ? selectedUser.pedagogical_name + ' ' + selectedUser.name : selectedUser.name) : ''"></span>
            <button type="button" @click="clearSelection()" class="ml-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
    </div>
    
    <!-- Search Results Dropdown -->
    <div 
        x-show="showDropdown && filteredUsers.length > 0" 
        @click.away="showDropdown = false"
        class="mt-2 max-h-60 overflow-y-auto border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 shadow-lg z-10 absolute left-0 right-0"
    >
        <template x-for="user in filteredUsers" :key="user.id">
            <div 
                class="p-3 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer border-b border-gray-200 dark:border-gray-600 last:border-b-0"
                @click="selectUser(user)"
            >
                <div class="font-medium text-gray-900 dark:text-gray-100" x-text="user.pedagogical_name ? user.pedagogical_name + ' ' + user.name : user.name"></div>
                <div class="text-sm text-gray-500 dark:text-gray-400" x-text="user.email"></div>
            </div>
        </template>
    </div>
    
    <!-- No Results Message -->
    <div x-show="showDropdown && searchQuery.length > 0 && filteredUsers.length === 0" class="mt-2 p-4 text-center text-gray-500 dark:text-gray-400 border border-gray-300 dark:border-gray-600 rounded-md">
        {{ __('No users found matching your search.') }}
    </div>
    
    <!-- Hidden input for form submission -->
    <input type="hidden" name="{{ $name }}" x-model="selectedUserId" {{ $required ? 'required' : '' }}>
</div>

@php
    $filteredUsers = $users;
    if ($filter) {
        $filteredUsers = $users->filter($filter);
    }
    $usersData = $filteredUsers->map(function($user) {
        return [
            'id' => $user->user_id,
            'name' => $user->name,
            'email' => $user->email,
            'pedagogical_name' => $user->pedagogical_name
        ];
    })->values();
    
    $selectedUserData = null;
    if ($selected) {
        $selectedUserData = [
            'id' => $selected->user_id,
            'name' => $selected->name,
            'email' => $selected->email,
            'pedagogical_name' => $selected->pedagogical_name
        ];
    }
@endphp

<script>
    function userSearchSelect{{ Str::camel($name) }}() {
        return {
            searchQuery: '',
            showDropdown: false,
            allUsers: @json($usersData),
            filteredUsers: [],
            selectedUser: @json($selectedUserData),
            selectedUserId: '{{ $selected ? $selected->user_id : '' }}',
            
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
            
            selectUser(user) {
                this.selectedUser = user;
                this.selectedUserId = user.id;
                this.searchQuery = '';
                this.showDropdown = false;
                this.filteredUsers = [];
            },
            
            clearSelection() {
                this.selectedUser = null;
                this.selectedUserId = '';
                this.searchQuery = '';
            }
        }
    }
</script>
