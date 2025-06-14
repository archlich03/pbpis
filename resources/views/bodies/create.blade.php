<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <form method="post" action="{{ route('bodies.store') }}" class="p-6 space-y-6">
                    @csrf
                    <x-input-label for="name" value="Name" />
                    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" />

                    <div class="mt-6">
                        <x-input-label for="type" value="Type" class="flex items-center" />
                        <div class="mt-1 flex items-center space-x-4">
                            <div class="flex items-center">
                                <input id="type_0" type="radio" name="type" value="0" class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded" />
                                <label for="type_0" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                                    BA
                                </label>
                            </div>
                            <div class="flex items-center">
                                <input id="type_1" type="radio" name="type" value="1" class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded" />
                                <label for="type_1" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                                    MA
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6">
                        <x-input-label for="chairman_id" value="Chairman" />
                        <select id="chairman_id" name="chairman_id" class="mt-1 block w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            <option value="" selected disabled hidden>Select chairman</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->pedagogical_name }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mt-6">
                        <x-input-label for="members" value="Members" />
                        <div class="mt-1 block w-full space-y-4">
                            @foreach ($users as $user)
                                <div class="flex items-center">
                                    <input type="checkbox" id="member_{{ $user->id }}" name="members[]" value="{{ $user->id }}" class="rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                    <label for="member_{{ $user->id }}" class="ml-2">{{ $user->name }} ({{ $user->pedagogical_name }})</label>
                                </div>
                            @endforeach
                        </div>
                    </div>

                </form>
            </div>
        </div>
    </div>
</x-app-layout>
