<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('List of all users') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="flex items-center gap-4">
                        <x-primary-button>
                            <a href="{{ route('register') }}" class="w-full">
                                {{ __('Register User') }}
                            </a>
                        </x-primary-button>
                    </div>
                    @if (Auth::user()->isPrivileged())
                        <table class="table-fixed w-full mt-4">
                            <thead>
                                <tr class="bg-gray-100 dark:bg-gray-700">
                                    <th class="px-4 py-2 w-1/6">Name</th>
                                    <th class="px-4 py-2 w-1/6">Email</th>
                                    @if (Auth::user()->role == 'IT administratorius')
                                        <th class="px-4 py-2 w-1/6">Role</th>
                                    @endif
                                    <th class="px-4 py-2 w-1/6">Gender</th>
                                    <th class="px-4 py-2 w-1/6">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($users as $user)
                                    @if (Auth::user()->role == 'IT administratorius' || $user->role == 'Balsuojantysis')
                                        <tr>
                                            <td class="border px-4 py-2">{{ $user->pedagogical_name }} {{ $user->name }}</td>
                                            <td class="border px-4 py-2">{{ $user->email }}</td>
                                            @if (Auth::user()->role == 'IT administratorius')
                                                <td class="border px-4 py-2">{{ $user->role }}</td>
                                            @endif
                                            <td class="border px-4 py-2">{{ $user->gender == '0' ? 'Female' : 'Male' }}</td>
                                            <td class="border px-4 py-2">
                                                <a href="{{ route('users.edit', $user) }}" class="hover:underline"><b>Edit</b></a>
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <p class="text-center text-lg">You are not authorized to view this page.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

