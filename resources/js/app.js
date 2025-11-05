import './bootstrap';

import Alpine from 'alpinejs';
import tiptapEditor from './tiptap-editor';
import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.min.css';

// Register tiptapEditor as an Alpine data component
Alpine.data('tiptapEditor', tiptapEditor);

window.Alpine = Alpine;

Alpine.start();

// Initialize Flatpickr for date and datetime inputs
document.addEventListener('DOMContentLoaded', function() {
    // Date pickers
    flatpickr('input[type="date"]', {
        dateFormat: 'Y-m-d',
        altInput: true,
        altFormat: 'Y-m-d',
        time_24hr: true,
        locale: {
            firstDayOfWeek: 1 // Monday
        }
    });

    // DateTime pickers
    flatpickr('input[type="datetime-local"]', {
        enableTime: true,
        dateFormat: 'Y-m-d H:i',
        altInput: true,
        altFormat: 'Y-m-d H:i',
        time_24hr: true,
        locale: {
            firstDayOfWeek: 1 // Monday
        }
    });
});
