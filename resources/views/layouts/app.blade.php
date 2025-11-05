<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="themeData()" x-bind:class="{ 'dark': darkMode }">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', config('app.name', 'POBIS'))</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        
        <!-- Custom Styles -->
        <style>
            /* Hide scrollbar for Chrome, Safari and Opera */
            .scrollbar-hide::-webkit-scrollbar {
                display: none;
            }
            
            /* Hide scrollbar for IE, Edge and Firefox */
            .scrollbar-hide {
                -ms-overflow-style: none;  /* IE and Edge */
                scrollbar-width: none;  /* Firefox */
            }
            
            /* Alpine.js x-cloak - hide elements until Alpine initializes */
            [x-cloak] {
                display: none !important;
            }
            
            /* Flatpickr Dark Mode Styles */
            .dark .flatpickr-calendar {
                background: #1f2937;
                border-color: #374151;
                box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);
            }
            
            .dark .flatpickr-months {
                background: #1f2937;
            }
            
            .dark .flatpickr-current-month .flatpickr-monthDropdown-months,
            .dark .flatpickr-current-month input.cur-year {
                background: #374151;
                color: #f3f4f6;
            }
            
            .dark .flatpickr-months .flatpickr-month,
            .dark .flatpickr-current-month .flatpickr-monthDropdown-months,
            .dark .flatpickr-weekdays,
            .dark span.flatpickr-weekday {
                background: #1f2937;
                color: #f3f4f6;
            }
            
            .dark .flatpickr-day {
                color: #d1d5db;
            }
            
            .dark .flatpickr-day:hover,
            .dark .flatpickr-day.prevMonthDay:hover,
            .dark .flatpickr-day.nextMonthDay:hover {
                background: #374151;
                border-color: #374151;
            }
            
            .dark .flatpickr-day.today {
                border-color: #6366f1;
            }
            
            .dark .flatpickr-day.selected,
            .dark .flatpickr-day.selected:hover {
                background: #6366f1;
                border-color: #6366f1;
                color: #fff;
            }
            
            .dark .flatpickr-time {
                border-top-color: #374151;
            }
            
            .dark .flatpickr-time input,
            .dark .flatpickr-time .flatpickr-time-separator,
            .dark .flatpickr-time .flatpickr-am-pm {
                color: #f3f4f6;
            }
            
            .dark .flatpickr-time input:hover,
            .dark .flatpickr-time input:focus {
                background: #374151;
            }
            
            .dark .flatpickr-day.prevMonthDay,
            .dark .flatpickr-day.nextMonthDay {
                color: #6b7280;
            }
            
            .dark .flatpickr-months .flatpickr-prev-month:hover svg,
            .dark .flatpickr-months .flatpickr-next-month:hover svg {
                fill: #6366f1;
            }
        </style>
        
        <!-- Theme Management Script -->
        <script>
            // Alpine.js theme component
            function themeData() {
                return {
                    darkMode: localStorage.getItem('theme') === 'dark' || 
                             (!localStorage.getItem('theme') && document.cookie.includes('theme=dark')),
                    
                    init() {
                        // Initialize theme on component load
                        this.applyTheme();
                        
                        // Watch for darkMode changes
                        this.$watch('darkMode', (value) => {
                            this.applyTheme();
                        });
                    },
                    
                    toggleTheme() {
                        this.darkMode = !this.darkMode;
                        this.saveTheme();
                    },
                    
                    applyTheme() {
                        const html = document.documentElement;
                        if (this.darkMode) {
                            html.classList.add('dark');
                        } else {
                            html.classList.remove('dark');
                        }
                    },
                    
                    saveTheme() {
                        const theme = this.darkMode ? 'dark' : 'light';
                        localStorage.setItem('theme', theme);
                        document.cookie = `theme=${theme}; path=/; max-age=31536000`; // 1 year
                    }
                }
            }
            
            // Global toggle function for compatibility
            function toggleTheme() {
                // Find the Alpine component and call its toggle method
                const htmlElement = document.documentElement;
                if (htmlElement._x_dataStack && htmlElement._x_dataStack[0]) {
                    htmlElement._x_dataStack[0].toggleTheme();
                }
            }
        </script>
    </head>
    <body class="font-sans antialiased transition-colors duration-200">
        <div class="min-h-screen bg-gray-100 dark:bg-gray-900 flex flex-col">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white dark:bg-gray-800 shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main class="flex-grow">
                {{ $slot }}
            </main>

            <!-- Footer -->
            <footer class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    <div class="flex flex-col sm:flex-row justify-between items-center text-sm text-gray-600 dark:text-gray-400 space-y-2 sm:space-y-0">
                        <div class="text-center sm:text-left">
                            {{ __('Copyright') }} © 2025 Rokas Stankūnas, {{ __('ISCS22') }}
                        </div>
                        <div class="flex flex-col sm:flex-row items-center space-y-1 sm:space-y-0 sm:space-x-4 text-center">
                            <span>{{ __('Licensed under') }} <a href="https://www.gnu.org/licenses/gpl-3.0.html" target="_blank" rel="noopener noreferrer" class="text-blue-600 dark:text-blue-400 hover:underline">GPLv3</a></span>
                            <span class="hidden sm:inline">•</span>
                            <a href="https://github.com/archlich03/pbpis" target="_blank" rel="noopener noreferrer" class="text-blue-600 dark:text-blue-400 hover:underline">{{ __('View source code') }}</a>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </body>
</html>
