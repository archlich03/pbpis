<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>PBPIS</title>
    <link href="https://unpkg.com/tailwindcss@^2/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="h-screen flex flex-col justify-center bg-gray-900">
    <div class="max-w-md mx-auto p-4 bg-gray-800 rounded-lg shadow-lg">
        <div class="flex items-center justify-center mb-6">
            <a href="login/">
                <x-application-logo class="block h-12 w-auto fill-current text-gray-200" />
            </a>
        </div>

        <h1 class="text-3xl font-bold text-gray-100 text-center mb-4">
            PBPIS
        </h1>

        <p class="text-gray-300 mb-4">
            {!! __('The Voting and Protocol Management Information System (PBPIS) is designed for the administration of electronic meetings of Study Program Committees at Vilnius University Kaunas Faculty. It assists members in participating in meetings, casting their votes and generating minutes in a convenient, unified platform.') !!}
        </p>

        <p class="text-gray-300 mb-4">
            {{ __('Main functions of PBPIS') }}:
        </p>

        <ul class="list-disc pl-4 text-gray-300 mb-6">
            <li>{{ __('Meeting and body management') }}.</li>
            <li>{{ __('Voting process for questions') }}.</li>
            <li>{{ __('Automated protocol generation') }}.</li>
            <li>{{ __('User and role management') }}.</li>
        </ul>

        <div class="text-center">
            @auth
                <a 
                    href="{{ route('dashboard') }}"
                    class="px-4 py-2 bg-blue-500 text-gray-100 font-semibold rounded-md hover:bg-blue-600 transition">
                    {{ __('Go to Dashboard') }}
                </a>
            @else
                <a 
                    href="{{ route('login') }}"
                    class="px-4 py-2 bg-blue-500 text-gray-100 font-semibold rounded-md hover:bg-blue-600 transition">
                    {{ __('Log in to PBPIS') }}
                </a>
            @endauth
        </div>
    </div>
</body>
</html>

