<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>PBPIS</title>

    <link href="https://unpkg.com/tailwindcss@^2/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 dark:bg-gray-900 h-screen flex flex-col justify-center">
    <div class="max-w-md mx-auto p-4 bg-white dark:bg-gray-800 rounded-lg shadow-lg">
        <div class="flex items-center justify-center">
            <a href="https://laravel.com">
                <x-application-logo class="block h-12 w-auto fill-current text-gray-800 dark:text-gray-200" />
            </a>
        </div>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 text-center">PBPIS</h1>
        <p class="mt-2 text-gray-800 dark:text-gray-200">Voting and Protocol Management Information System - IS, which is intended for the administration of electronic meetings of the Study Program Committees of Vilnius University Kaunas Faculty and generating protocols. This IS is being developed to fulfill the requirements set by the Information Systems and Cybersecurity "Coursework" module.</p>
        <p class="mt-2 text-gray-800 dark:text-gray-200">IS functions:</p>
        <ul class="list-disc pl-4 text-gray-800 dark:text-gray-200">
            <li>meeting and document management;</li>
            <li>user voting process;</li>
            <li>protocol generation;</li>
            <li>user management.</li>
        </ul>
        <p class="mt-4"><a href="{{ route('login') }}" class="text-blue-500 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-600">Log in</a></p>
    </div>
</body>
</html>

