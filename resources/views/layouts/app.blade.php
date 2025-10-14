<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="themeData()" x-bind:class="{ 'dark': darkMode }">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', config('app.name', 'PBPIS'))</title>

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
        <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
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
            <main>
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
