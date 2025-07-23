import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    safelist: [
        // Audit log badge colors - prevent purging of dynamically generated classes
        'bg-green-200', 'text-green-900', 'dark:bg-green-600', 'dark:text-green-50',
        'bg-blue-200', 'text-blue-900', 'dark:bg-blue-600', 'dark:text-blue-50',
        'bg-red-200', 'text-red-900', 'dark:bg-red-600', 'dark:text-red-50',
        'bg-purple-200', 'text-purple-900', 'dark:bg-purple-600', 'dark:text-purple-50',
        'bg-yellow-200', 'text-yellow-900', 'dark:bg-yellow-600', 'dark:text-yellow-50',
        'bg-orange-200', 'text-orange-900', 'dark:bg-orange-600', 'dark:text-orange-50',
        'bg-gray-200', 'text-gray-900', 'dark:bg-gray-600', 'dark:text-gray-50',
    ],

    darkMode: 'class', // Enable class-based dark mode

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
