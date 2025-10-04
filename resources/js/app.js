import './bootstrap';

import Alpine from 'alpinejs';
import tiptapEditor from './tiptap-editor';

// Register tiptapEditor as an Alpine data component
Alpine.data('tiptapEditor', tiptapEditor);

window.Alpine = Alpine;

Alpine.start();
