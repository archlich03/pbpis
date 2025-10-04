@props(['name' => 'summary', 'value' => '', 'id' => null])

@php
    $id = $id ?? $name;
    $escapedValue = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
@endphp

<div x-data="tiptapEditor({{ json_encode($value) }}, '{{ $name }}')" x-init="init()" x-on:destroy.window="destroy()">
    <!-- Editor with keyboard shortcuts info -->
    <div class="mb-1">
        <p class="text-xs text-gray-500 dark:text-gray-400">
            {{ __('Keyboard shortcuts') }}: <strong>Ctrl+B</strong> {{ __('bold') }}, <strong>Ctrl+I</strong> {{ __('italic') }}, <strong>Ctrl+Shift+8</strong> {{ __('bullet list') }}, <strong>Ctrl+Shift+7</strong> {{ __('numbered list') }}
        </p>
    </div>
    
    <!-- Editor -->
    <div x-ref="editor" 
         class="border border-gray-300 dark:border-gray-700 rounded-md focus-within:ring-2 focus-within:ring-indigo-500 dark:focus-within:ring-indigo-600">
    </div>
    
    <!-- Hidden input to store the content -->
    <input type="hidden" :name="fieldName" x-ref="hiddenInput" :id="'{{ $id }}'">
</div>
