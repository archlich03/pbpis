@props([
    'label' => '',
    'name',
    'id' => null,
    'options' => [],
    'selected' => null,
])

<div>
    @if ($label)
        <label for="{{ $id ?? $name }}" class="mr-2 text-sm font-medium text-gray-700 dark:text-gray-200">
            {{ $label }}
        </label>
    @endif

    <select
        id="{{ $id ?? $name }}"
        name="{{ $name }}"
        onchange="this.form.submit()"
        class="border border-gray-300 dark:border-gray-600 rounded-lg px-3 pr-5 py-1 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-400 dark:bg-gray-800 dark:text-white"
    >
        @foreach ($options as $value => $display)
            <option value="{{ $value }}" {{ $value == $selected ? 'selected' : '' }}>
                {{ $display }}
            </option>
        @endforeach
    </select>
</div>
