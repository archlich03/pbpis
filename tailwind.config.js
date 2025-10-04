import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    safelist: [
        // Audit log badge colors - prevent purging of dynamically generated classes
        'bg-green-100', 'text-green-700', 'dark:bg-green-700', 'dark:text-green-100',
        'bg-blue-100', 'text-blue-700', 'dark:bg-blue-700', 'dark:text-blue-100',
        'bg-red-100', 'text-red-700', 'dark:bg-red-700', 'dark:text-red-100',
        'bg-purple-100', 'text-purple-700', 'dark:bg-purple-700', 'dark:text-purple-100',
        'bg-yellow-100', 'text-yellow-700', 'dark:bg-yellow-700', 'dark:text-yellow-100',
        'bg-orange-100', 'text-orange-700', 'dark:bg-orange-700', 'dark:text-orange-100',
        'bg-gray-100', 'text-gray-700', 'dark:bg-gray-700', 'dark:text-gray-100',
    ],

    darkMode: 'class', // Enable class-based dark mode

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms, typography],
};
