<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ $body->title }} ({{ $body->is_ba_sp ? 'BA' : 'MA' }})
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-xl font-semibold mb-4">Body information</h3>
                    <ul class="list-disc pl-4">
                        <li><strong>Name:</strong> {{ $body->title }} ({{ $body->is_ba_sp ? 'BA' : 'MA' }})</li>
                        <li><strong>Chairman:</strong> {{ optional($body->chairman)->pedagogical_name ?? '' }} {{ optional($body->chairman)->name ?? '' }}</li>
                        <li><strong>Members:</strong>
                            <ul class="list-disc pl-8">
                                @foreach ($members as $member)
                                    <li>{{ $member->pedagogical_name ?? '' }} {{ $member->name ?? '' }}</li>
                                @endforeach
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

