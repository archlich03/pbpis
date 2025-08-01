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
    <body class="font-sans text-gray-900 dark:text-gray-100 antialiased transition-colors duration-200">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100 dark:bg-gray-900">
            <!-- Theme Toggle Button -->
            <div class="absolute top-4 right-4">
                <button 
                    @click="toggleTheme()" 
                    class="p-2 rounded-lg bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900"
                    :title="darkMode ? '{{ __('Switch to light mode') }}' : '{{ __('Switch to dark mode') }}'"
                >
                    <!-- Sun Icon (Light Mode) -->
                    <svg x-show="darkMode" class="w-5 h-5 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" clip-rule="evenodd" />
                    </svg>
                    
                    <!-- Moon Icon (Dark Mode) -->
                    <svg x-show="!darkMode" class="w-5 h-5 text-gray-700 dark:text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z" />
                    </svg>
                </button>
            </div>
            
            <div>
                <a href="/">
                    <x-application-logo height="100" width="100" class="w-20 h-20 fill-current text-gray-500 dark:text-gray-400" />
                </a>
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white dark:bg-gray-800 shadow-md overflow-hidden sm:rounded-lg">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
